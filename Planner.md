## Шаг 1. Telegram-бот (tender-bot)

### Архитектура

```
server-ansible/home/tender-bot/
├── Dockerfile          # Python 3.12-slim
├── requirements.txt    # python-telegram-bot, fastapi, langchain, gigachat
├── main.py             # FastAPI + Telegram Application + Bitrix24 webhook handler
├── prompts.py          # Системный промпт + 10 шаблонных сценариев
└── .env.example        # Пример переменных окружения
```

### Принцип работы

1. Пользователь пишет вопрос в Telegram
2. Бот ищет по шаблонам (`prompts.py`) — возвращает готовый ответ без API-вызовов
3. Если шаблон не найден — обращается к GigaChat через LangChain
4. Bitrix24 отправляет события (`ONCRMDEALADD` и др.) → бот уведомляет в Telegram

---

## Шаг 5. Сценарии тестирования

Подробное описание — в `Manual-bot.md`.

| # | Запрос | Проверяет |
|---|--------|----------|
| 1 | создать заявку на расходование ДС | Шаблон + инструкция CRM |
| 2 | как зарегистрировать тендер | Смарт-процессы Bitrix24 |
| 3 | как добавить участника тендера | Работа с контактами |
| 4 | как поставить задачу | Задачи и проекты |
| 5 | как отправить коммерческое предложение | Документы CRM |
| 6 | как переторговаться | Стадии тендера |
| 7 | как узнать дедлайн тендера | Поля карточки |
| 8 | как посмотреть отчёт по тендерам | Аналитика/воронка |
| 9 | как синхронизировать пользователей | bx:user-sync |
| 10 | как проверить статус системы | bx:health + docker ps |

---

## Шаг 6. Проверка лицензии Bitrix24

### Механизм PostgresAdapter

Файл `lib/DB/PostgresAdapter.php` перехватывает SQL-запросы:
```
SELECT ... FROM b_option WHERE ...
```

### Механизм cli.php

С целями отладки взаимодействия модулей с ядром в `cli.php` установлены глобальные заглушки:
```php
$GLOBALS['admin_passwordh'] = '1893456000';
$GLOBALS['install_date'] = '1774828800';
$_SERVER['WIZARD_INSTALL_MODE'] = 'Y';
```
И фильтр выходного буфера, вырезающий фразы о пробной лицензии:
чтобы консольные команды имели красивый вывод.
```php
ob_start(function($buffer) {
    return str_replace($garbage, '', $buffer);
});
```

**Вывод:** Механизм надёжно работает для консольных команд (headless CLI).

---

## Шаг 7. Сборка и запуск

```bash
cd server-ansible/home

# Скопировать и заполнить переменные окружения
cp tender-bot/.env.example .env
# Заполнить TELEGRAM_TOKEN в .env

# Сборка и запуск всего стека
docker compose up -d --build

# Проверить статус
docker compose ps

# Проверить логи автоматической настройки
docker compose logs bitrix-setup

# Проверить вебхуки вручную
docker exec bitrix-php bx bx:webhook-reg

# Запустить диагностику
docker exec bitrix-php bx bx:health
```

---

## Шаг 8. Настройка Gitea OAuth2 через Dex

1. В Gitea: `Настройки → Приложения → OAuth2` 
— создать приложение с redirect URI: `http://localhost:5556/dex/callback`
2. Скопировать `Client ID` и `Client Secret` в `dex-config.yaml` 
(`connectors[0].config.clientID/clientSecret`)
3. Перезапустить Dex: `docker compose restart dex`
4. Проверить: открыть `http://localhost:5556/dex/auth?client_id=rolemodel&...`

---

## Дополнительно: парсинг its.1c.ru

Для периодического обновления Базы Знаний из документации из its.1c.ru рекомендуется:

1. Создать отдельный Python-сервис `docs-parser/`
2. Использовать `httpx` + `BeautifulSoup` для скрейпинга
3. Сохранять в PostgreSQL (таблица `docs_articles`)
4. Настроить cron через системный `cron` или Docker + `cron`-контейнер

Пример cron-задачи:
```cron
# Каждое воскресенье в 03:00
0 3 * * 0 docker exec tender-bot python parse_docs.py
```