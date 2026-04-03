# Tests.md — Методика тестирования

Методика установки, настройки и тестирования всех компонентов `bitrix-local`.

---

## 1. Предварительные требования

| Требование | Версия |
|------------|--------|
| Docker Engine | 24+ |
| Docker Compose | v2+ |
| Python | 3.11+ (для тестов бота и docs-parser) |
| Node.js | 20+ (для сборки admin-dashboard) |
| Файл `upload.tar` | Дистрибутив ядра Bitrix24 |
| Файл `local.tar` | Архив папки `local/` с модулями |

---

## 2. Установка и запуск

### 2.1 Клонирование и настройка

```bash
git clone https://github.com/G9990999/bitrix-local
cd bitrix-local/server-ansible/home

# Создать .env файл
cp .env.example .env 2>/dev/null || cat > .env <<EOF
TELEGRAM_TOKEN=your_telegram_bot_token
GIGACHAT_CREDENTIALS=         # base64-encoded GigaChat token (опционально)
GIGACHAT_SCOPE=GIGACHAT_API_PERS
EOF
```

### 2.2 Запуск стека

```bash
docker compose up -d --build

# Дождаться завершения инициализации
docker compose logs -f bitrix-setup
# Ожидаемый вывод: --- SETUP DONE ---
```

### 2.3 Проверка всех сервисов

```bash
docker compose ps
# Все сервисы должны быть в статусе "running" (кроме bitrix-setup, bitrix-init — "exited 0")
```

---

## 3. Тестирование CLI-команд (bx)

### 3.1 Базовые команды

```bash
# Здоровье системы
docker exec bitrix-php bx bx:health
# Ожидается: [OK] для Bitrix Core, DB, Dex, Gitea

# Список вебхуков
docker exec bitrix-php bx bx:webhook-reg
# Ожидается: JSON с 3 вебхуками (ONCRMDEALADD, ONCRMDEALUPDATE, ONCRMDEALDELETE)
```

### 3.2 bx:parser — управление парсером

```bash
# Список подписанных сервисов (изначально пусто)
docker exec bitrix-php bx bx:parser
# {"status":"ok","message":"Нет подписанных сервисов...","services":[]}

# Подписать tender-bot
docker exec bitrix-php bx bx:parser tender-bot
# Ожидается: {"status":"ok","service":"tender-bot","message":"Сервис подписан..."}

# Проверить список после подписки
docker exec bitrix-php bx bx:parser
# Ожидается: 1 сервис в списке

# Повторная подписка (idempotent)
docker exec bitrix-php bx bx:parser tender-bot
# Ожидается: [WARN] "Сервис уже подписан. Обновляем конфигурацию..."
```

### 3.3 bx:role — управление ролями

```bash
# Список должностей (изначально пусто)
docker exec bitrix-php bx bx:role
# {"status":"ok","message":"Должности не найдены. Выполните: bx bx:role install"}

# Загрузить из CSV (24 должности из roles-list.csv)
docker exec bitrix-php bx bx:role install
# Ожидается: "Загрузка завершена. Добавлено: 24, пропущено: 0"

# Повторная загрузка (idempotent)
docker exec bitrix-php bx bx:role install
# Ожидается: "Добавлено: 0, пропущено: 24"

# Список должностей после загрузки
docker exec bitrix-php bx bx:role
# Ожидается: JSON со всеми должностями

# Генерация матрицы прав (без GigaChat — использует check-roles.csv)
docker exec bitrix-php bx bx:role generator
# Ожидается: JSON с 10 должностями и матрицей прав доступа
```

### 3.4 Проверка PostgreSQL

```bash
# Подключиться к БД
docker exec -it bitrix-db psql -U bitrix -d bitrix

# Проверить созданные таблицы
\dt b_*
# Ожидается: b_parser_subscriptions, b_roles, b_role_access

# Проверить данные
SELECT name, department FROM b_roles LIMIT 5;
SELECT name, webhook_url FROM b_parser_subscriptions;
SELECT r.name, COUNT(ra.id) FROM b_roles r LEFT JOIN b_role_access ra ON ra.role_id = r.id GROUP BY r.name;
\q
```

---

## 4. Тестирование chatbot.test (Telegram-бот)

### 4.1 Unit-тесты (без реального бота)

```bash
cd server-ansible/home/upload/local/modules/chatbot.test
pip install -r requirements.txt

# Запуск unit-тестов
pytest tests/test_tender_bot.py -v -k "not integration"
```

**Ожидаемые результаты:**
```
tests/test_tender_bot.py::TestScenarios::test_scenarios_not_empty PASSED
tests/test_tender_bot.py::TestScenarios::test_find_scenario_zaявка PASSED
tests/test_tender_bot.py::TestScenarios::test_find_scenario_tender PASSED
tests/test_tender_bot.py::TestScenarios::test_find_scenario_no_match PASSED
tests/test_tender_bot.py::TestScenarios::test_scenario_keys_are_strings PASSED
tests/test_tender_bot.py::TestScenarios::test_scenario_values_are_strings PASSED
tests/test_tender_bot.py::TestScenarios::test_system_prompt_exists PASSED
tests/test_tender_bot.py::TestBitrixWebhookParsing::test_event_labels_mapping PASSED
tests/test_tender_bot.py::TestBitrixWebhookParsing::test_unknown_event_fallback PASSED
```

### 4.2 Интеграционные тесты (требуют запущенного стека)

```bash
# Убедиться что tender-bot запущен
docker compose ps tender-bot

# Запустить интеграционные тесты
TENDER_BOT_URL=http://localhost:3102 pytest tests/test_tender_bot.py -v -m integration
```

### 4.3 Ручное тестирование через curl

```bash
# Health-check
curl http://localhost:3102/health
# {"status":"ok"}

# Имитация события Bitrix24
curl -X POST http://localhost:3102/bitrix/webhook \
  -d "event=ONCRMDEALADD&data[FIELDS][ID]=123"
# {"status":"ok"} или {"status":"ok","note":"TELEGRAM_CHAT_ID не настроен"}
```

---

## 5. Тестирование docs-parser

### 5.1 Проверка сервиса

```bash
# Health-check
curl http://localhost:8001/health
# {"status":"ok","service":"docs-parser"}

# Статистика собранных статей
curl http://localhost:8001/stats
# {"total_articles": N, "last_update": "..."}

# Ручной запуск парсинга
curl -X POST http://localhost:8001/parse
# {"status":"started","message":"Парсинг запущен в фоне"}
```

### 5.2 Проверка данных в PostgreSQL

```bash
docker exec -it bitrix-db psql -U bitrix -d bitrix -c "
  SELECT section, COUNT(*) as cnt
  FROM docs_articles
  GROUP BY section
  ORDER BY cnt DESC
  LIMIT 10;
"
```

---

## 6. Тестирование Admin Dashboard

### 6.1 Проверка доступности

```bash
# Dashboard должен открываться на порту 5173
curl -s http://localhost:5173/ | grep -o '<title>.*</title>'
# <title>Bitrix Admin Dashboard</title>

# Или в браузере:
open http://localhost:5173
```

### 6.2 Тестируемые страницы

| URL | Ожидаемое содержимое |
|-----|---------------------|
| `http://localhost:5173/` | Главный дашборд с 5 карточками |
| `http://localhost:5173/assets` | Таблица активов SnipeIT |
| `http://localhost:5173/licenses` | Таблица лицензий с прогресс-барами |
| `http://localhost:5173/users` | Список пользователей Bitrix24 |
| `http://localhost:5173/roles` | Каталог должностей |
| `http://localhost:5173/audit` | История назначений |

---

## 7. Тестирование snipeit.itrix модуля

### 7.1 PHP unit-тест (требует Bitrix24 ядро)

```bash
# Выполняется через CLI внутри bitrix-php
docker exec bitrix-php php /var/www/html/local/modules/snipeit.itrix/tests/run_tests.php
```

### 7.2 Ручное тестирование через Bitrix Admin

```
http://localhost:8080/bitrix/admin/snipeit_assets.php
```

Проверить:
1. Создание актива (нажать "+ Добавить актив")
2. Список активов с фильтрацией по статусу
3. Мягкое удаление (ACTIVE = N)

---

## 8. Полный smoke-тест стека

Выполните последовательно:

```bash
# 1. Запуск
docker compose up -d --build
sleep 60  # дождаться инициализации

# 2. Проверка сервисов
docker compose ps

# 3. CLI диагностика
docker exec bitrix-php bx bx:health

# 4. Загрузка должностей
docker exec bitrix-php bx bx:role install

# 5. Генерация прав
docker exec bitrix-php bx bx:role generator

# 6. Подписка парсера
docker exec bitrix-php bx bx:parser tender-bot

# 7. Проверка webhook
curl http://localhost:3102/health

# 8. Проверка docs-parser
curl http://localhost:8001/health

# 9. Проверка Dashboard
curl -s -o /dev/null -w "%{http_code}" http://localhost:5173/
# Ожидается: 200

# 10. Все вебхуки Bitrix
docker exec bitrix-php bx bx:webhook-reg

echo "=== SMOKE TEST PASSED ==="
```

---

## 9. Сброс окружения

```bash
# Остановить и удалить контейнеры + volumes
docker compose down -v

# Пересобрать с нуля
docker compose up -d --build --force-recreate
```
