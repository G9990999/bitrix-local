"""
docs-parser — периодический парсер документации its.1c.ru.

Назначение:
  Обновляет Базу Знаний из официальной документации 1C:Bitrix, сохраняя
  статьи в PostgreSQL (таблица docs_articles).

Запуск:
  python main.py              — однократный запуск парсера
  python main.py --serve      — HTTP-сервер + cron-расписание (каждые 24 часа)

Переменные окружения:
  DATABASE_URL      — PostgreSQL DSN (postgresql://user:pass@host:5432/dbname)
  ITS_BASE_URL      — Базовый URL для парсинга (по умолчанию: https://dev.1c-bitrix.ru/api_help/main/)
  PARSE_DELAY       — Задержка между запросами в секундах (по умолчанию: 1.5)
  MAX_PAGES         — Максимальное число страниц за один запуск (по умолчанию: 200)
"""

import asyncio
import logging
import os
import time
from datetime import datetime
from urllib.parse import urljoin, urlparse

import httpx
from bs4 import BeautifulSoup
import psycopg2
from psycopg2.extras import execute_values

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)
logger = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Конфигурация
# ---------------------------------------------------------------------------

DATABASE_URL = os.getenv(
    "DATABASE_URL",
    "postgresql://bitrix:bitrix@bitrix-db:5432/bitrix",
)
ITS_BASE_URL = os.getenv(
    "ITS_BASE_URL",
    "https://dev.1c-bitrix.ru/api_help/main/",
)
PARSE_DELAY = float(os.getenv("PARSE_DELAY", "1.5"))
MAX_PAGES   = int(os.getenv("MAX_PAGES", "200"))

HEADERS = {
    "User-Agent": "Mozilla/5.0 (compatible; BitrixDocsParser/1.0; +https://github.com/G9990999/bitrix-local)",
    "Accept-Language": "ru-RU,ru;q=0.9",
}


# ---------------------------------------------------------------------------
# База данных
# ---------------------------------------------------------------------------

def get_db_connection():
    """Создаёт подключение к PostgreSQL."""
    return psycopg2.connect(DATABASE_URL)


def ensure_table(conn) -> None:
    """Создаёт таблицу docs_articles, если не существует."""
    with conn.cursor() as cur:
        cur.execute("""
            CREATE TABLE IF NOT EXISTS docs_articles (
                id          SERIAL PRIMARY KEY,
                url         TEXT NOT NULL UNIQUE,
                title       TEXT,
                content     TEXT,
                section     VARCHAR(200),
                parsed_at   TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
            );
            CREATE INDEX IF NOT EXISTS docs_articles_section_idx ON docs_articles (section);
            CREATE INDEX IF NOT EXISTS docs_articles_parsed_at_idx ON docs_articles (parsed_at DESC);
        """)
    conn.commit()
    logger.info("Таблица docs_articles готова.")


def upsert_articles(conn, articles: list[dict]) -> int:
    """Вставляет или обновляет статьи в БД. Возвращает кол-во затронутых строк."""
    if not articles:
        return 0

    with conn.cursor() as cur:
        execute_values(
            cur,
            """
            INSERT INTO docs_articles (url, title, content, section, parsed_at, updated_at)
            VALUES %s
            ON CONFLICT (url) DO UPDATE SET
                title      = EXCLUDED.title,
                content    = EXCLUDED.content,
                section    = EXCLUDED.section,
                updated_at = NOW()
            """,
            [
                (a["url"], a["title"], a["content"], a["section"], datetime.utcnow(), datetime.utcnow())
                for a in articles
            ],
        )
    conn.commit()
    return len(articles)


# ---------------------------------------------------------------------------
# Парсинг
# ---------------------------------------------------------------------------

class ItsParser:
    """Парсер документации 1C:Bitrix с сайта dev.1c-bitrix.ru / its.1c.ru."""

    def __init__(self, base_url: str = ITS_BASE_URL):
        self.base_url   = base_url
        self.visited    = set()
        self.to_visit   = [base_url]
        self.articles   = []

    def _extract_section(self, url: str) -> str:
        """Извлекает раздел из URL."""
        parsed = urlparse(url)
        parts  = [p for p in parsed.path.strip("/").split("/") if p]
        return parts[1] if len(parts) > 1 else parts[0] if parts else "general"

    def _parse_page(self, html: str, url: str) -> dict | None:
        """Парсит страницу и возвращает статью."""
        soup = BeautifulSoup(html, "html.parser")

        # Извлекаем заголовок
        title_tag = soup.find("h1") or soup.find("title")
        title     = title_tag.get_text(strip=True) if title_tag else "Без заголовка"

        # Извлекаем основной контент
        content_tag = (
            soup.find("div", class_="wiki-content")
            or soup.find("div", class_="article-content")
            or soup.find("div", id="content")
            or soup.find("main")
            or soup.find("article")
        )

        if not content_tag:
            return None

        # Очищаем от скриптов и стилей
        for tag in content_tag.find_all(["script", "style", "nav", "footer"]):
            tag.decompose()

        content = content_tag.get_text(separator="\n", strip=True)

        # Отфильтровываем слишком короткие страницы
        if len(content) < 100:
            return None

        return {
            "url":     url,
            "title":   title[:500],
            "content": content[:50000],  # Ограничиваем размер
            "section": self._extract_section(url),
        }

    def _collect_links(self, html: str, current_url: str) -> list[str]:
        """Собирает внутренние ссылки со страницы."""
        soup   = BeautifulSoup(html, "html.parser")
        links  = []
        base   = urlparse(self.base_url)

        for a_tag in soup.find_all("a", href=True):
            href = a_tag["href"].strip()
            if not href or href.startswith("#") or href.startswith("javascript:"):
                continue

            full_url = urljoin(current_url, href)
            parsed   = urlparse(full_url)

            # Только страницы того же домена и того же раздела
            if parsed.netloc != base.netloc:
                continue
            if not parsed.path.startswith(base.path):
                continue
            # Убираем якоря и параметры
            clean_url = parsed._replace(fragment="", query="").geturl()
            if clean_url not in self.visited:
                links.append(clean_url)

        return links

    def run(self, max_pages: int = MAX_PAGES) -> list[dict]:
        """Синхронный запуск парсера."""
        logger.info(f"Начало парсинга: {self.base_url} (max_pages={max_pages})")

        with httpx.Client(headers=HEADERS, timeout=20, follow_redirects=True, verify=False) as client:
            count = 0

            while self.to_visit and count < max_pages:
                url = self.to_visit.pop(0)
                if url in self.visited:
                    continue

                self.visited.add(url)

                try:
                    response = client.get(url)
                    if response.status_code != 200:
                        logger.warning(f"  [{response.status_code}] {url}")
                        continue

                    html    = response.text
                    article = self._parse_page(html, url)

                    if article:
                        self.articles.append(article)
                        logger.info(f"  [{count + 1}] {article['title'][:60]} — {url}")
                        count += 1

                    # Собираем новые ссылки
                    new_links = self._collect_links(html, url)
                    for link in new_links:
                        if link not in self.visited and link not in self.to_visit:
                            self.to_visit.append(link)

                    time.sleep(PARSE_DELAY)

                except httpx.RequestError as e:
                    logger.error(f"  Ошибка запроса {url}: {e}")
                except Exception as e:
                    logger.error(f"  Непредвиденная ошибка {url}: {e}")

        logger.info(f"Парсинг завершён. Собрано статей: {len(self.articles)}")
        return self.articles


# ---------------------------------------------------------------------------
# Основной сценарий
# ---------------------------------------------------------------------------

def run_parser() -> None:
    """Запускает парсер и сохраняет результаты в БД."""
    logger.info("=" * 60)
    logger.info("docs-parser запущен")
    logger.info(f"Цель: {ITS_BASE_URL}")

    parser   = ItsParser(ITS_BASE_URL)
    articles = parser.run(MAX_PAGES)

    if not articles:
        logger.warning("Статьи не найдены. Проверьте URL и доступность сайта.")
        return

    try:
        conn = get_db_connection()
        ensure_table(conn)
        saved = upsert_articles(conn, articles)
        conn.close()
        logger.info(f"Сохранено/обновлено статей: {saved}")
    except Exception as e:
        logger.error(f"Ошибка БД: {e}")
        raise


def serve() -> None:
    """Запускает HTTP-сервер с ендпоинтами для управления парсером + cron."""
    from fastapi import FastAPI
    import uvicorn
    import threading

    app = FastAPI(title="docs-parser", description="Парсер документации its.1c.ru")

    @app.get("/health")
    def health():
        return {"status": "ok", "service": "docs-parser"}

    @app.post("/parse")
    def trigger_parse():
        """Ручной запуск парсера (в фоне)."""
        thread = threading.Thread(target=run_parser, daemon=True)
        thread.start()
        return {"status": "started", "message": "Парсинг запущен в фоне"}

    @app.get("/stats")
    def stats():
        """Статистика по собранным статьям."""
        try:
            conn = get_db_connection()
            with conn.cursor() as cur:
                cur.execute("SELECT COUNT(*), MAX(updated_at) FROM docs_articles")
                row = cur.fetchone()
            conn.close()
            return {
                "total_articles": row[0] if row else 0,
                "last_update":    str(row[1]) if row and row[1] else None,
            }
        except Exception as e:
            return {"error": str(e)}

    # Планировщик: запуск каждые 24 часа
    def cron_loop():
        while True:
            logger.info("cron: запуск планового парсинга")
            run_parser()
            interval = 24 * 60 * 60  # 24 часа
            logger.info(f"cron: следующий запуск через {interval // 3600} часов")
            time.sleep(interval)

    cron_thread = threading.Thread(target=cron_loop, daemon=True)
    cron_thread.start()

    uvicorn.run(app, host="0.0.0.0", port=8001)


# ---------------------------------------------------------------------------
# Точка входа
# ---------------------------------------------------------------------------

if __name__ == "__main__":
    import sys

    if "--serve" in sys.argv:
        serve()
    else:
        run_parser()
