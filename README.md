# bitrix-local — Bitrix24 Automation Sandbox

Песочница для разработки и тестирования модулей 1C Bitrix24 в Docker-среде.
Включает Headless CLI (`bx`), Telegram-бот тендерного отдела, Dex SSO и Gitea.

---

## Архитектура

```
server-ansible (127.0.0.1)
│ - Ansible shell
├── bitrix-nginx     :8080   — Nginx для Bitrix24
├── bitrix-php       (FPM)   — PHP 8.2 с CLI-оболочкой bx
├── bitrix-db        :5432   — PostgreSQL
├── gitea            :3000   — Локальный Git-сервер
├── dex              :5556   — OAuth2/OIDC SSO-провайдер
├── tender-bot       :3102   — Telegram-бот (LangChain + GigaChat)
├── docs-parser      :8001   — Парсер документации its.1c.ru → PostgreSQL
├── admin-dashboard  :5173   — Vite+React+HeroUI панель администратора
└── bitrix-setup     (init)  — Автоматическая инициализация
```
server-zabbix (127.0.0.2)
│
├── zabbix     :   — система мониторинга ПАК для Bitrix24
├── grafana    :   — панель отображения мониторинга
---

## Быстрый старт

### Требования

- Docker Engine 24+
- Docker Compose v2
- Telegram-бот токен (получить у @BotFather)

### 1. Клонировать репозиторий

```bash
git clone https://github.com/G9990999/bitrix-local
cd bitrix-local/server-ansible/home
```

### 2. Настроить переменные окружения

```bash
cp tender-bot/.env.example .env
```

Заполните `.env`:
```env
TELEGRAM_TOKEN=your_telegram_bot_token
GIGACHAT_CREDENTIALS=your_gigachat_credentials_base64  # необязательно
```

### 3. Подготовить архивы Bitrix24

Положите в `server-ansible/tmp/`:
- `upload.tar` — дистрибутив ядра Bitrix24
   - содержимое архива /bitrix и другие папки
- `local.tar` — архив директории `local/` с модулями

### 4. Запустить стек

```bash
docker compose up -d --build
```

Контейнер `bitrix-setup` автоматически выполнит:
- `bx bx:install` — установку модуля rolemodel.cli
- `bx bx:webhook-reg ONCRMDEALADD/ONCRMDEALUPDATE/ONCRMDEALDELETE` — регистрацию вебхуков

### 5. Проверить работоспособность

```bash
# Статус всех сервисов
docker compose ps

# Диагностика Bitrix + DB + Dex + Gitea
docker exec bitrix-php bx bx:health

# Список зарегистрированных вебхуков
docker exec bitrix-php bx bx:webhook-reg
```

---

## CLI — команды `bx`

Выполняются внутри контейнера `bitrix-php`:

```bash
docker exec bitrix-php bx bx:help
docker exec bitrix-php bx <команда>
```

| Команда | Описание |
|---------|----------|
| `bx:install` | Установить модуль rolemodel.cli в Bitrix24 |
| `bx:init` | Проверить подключение к ядру и БД |
| `bx:health` | Проверить доступность Dex, Gitea, Core, DB |
| `bx:webhook-reg` | Показать список зарегистрированных вебхуков |
| `bx:webhook-reg EVENT_NAME` | Зарегистрировать вебхук для EVENT_NAME |
| `bx:user-sync` | Синхронизировать пользователей Dex → Bitrix24 |
| `bx:cache-clear` | Очистить кеш Bitrix24 |
| `bx:migrate` | Запустить миграции БД |
| `bx:backup` | Создать резервную копию |
| `bx:deploy` | Задеплоить конфиги Nginx/Dex |
| `bx:parser` | Список подписанных сервисов на парсинг |
| `bx:parser tender-bot` | Подписать сервис tender-bot + зарегистрировать webhook |
| `bx:role` | Список всех должностей и прикреплённых ролей |
| `bx:role install` | Загрузить должности из CSV в PostgreSQL |
| `bx:role generator` | Сгенерировать матрицу доступов через GigaChat |

### Примеры

```bash
# Зарегистрировать вебхук для события добавления сделки
docker exec bitrix-php bx bx:webhook-reg ONCRMDEALADD

# Список всех вебхуков
docker exec bitrix-php bx bx:webhook-reg

# Полная диагностика
docker exec bitrix-php bx bx:health

# Подписать tender-bot на парсинг данных
docker exec bitrix-php bx bx:parser tender-bot

# Загрузить каталог должностей из CSV
docker exec bitrix-php bx bx:role install

# Сгенерировать матрицу прав для 10 должностей
docker exec bitrix-php bx bx:role generator
```

## Admin Dashboard

Веб-панель администратора Bitrix24 доступна на порту **5173**:

```
http://localhost:5173
```

Страницы:
- `/` — Главный дашборд с быстрыми ссылками и командами CLI
- `/assets` — Учёт ИТ-активов (SnipeIT)
- `/licenses` — Управление лицензиями с индикатором использования
- `/users` — Пользователи Bitrix24
- `/roles` — Каталог должностей и матрица прав доступа
- `/audit` — Аудит: кто имеет доступ к каким ресурсам

## Модули

### rolemodel.cli

CLI-модуль для автоматизации Bitrix24.

**Новые команды (issue #1):**
- `bx:parser` — управление подписками сервисов на парсинг данных
- `bx:role` — каталог должностей и ролей доступа через GigaChat

### snipeit.itrix

Bitrix-модуль учёта ИТ-активов и лицензий.
- D7 ORM: `AssetTable`, `LicenseTable`, `UserAssignmentTable`
- Сервисный слой: `AssetService`, `LicenseService`, `AssignmentService`
- Аудит назначений (кто имеет доступ к чему)
- PostgreSQL

### chatbot.test

Модуль тестирования tender-bot.

```bash
cd server-ansible/home/upload/local/modules/chatbot.test
pip install -r requirements.txt
pytest tests/test_tender_bot.py -v -k "not integration"
```

### docs-parser

Python-сервис периодического обновления Базы Знаний из документации its.1c.ru.
- httpx + BeautifulSoup для скрейпинга
- PostgreSQL (таблица `docs_articles`)
- Cron через Docker + FastAPI эндпоинт `/parse`

---

## Telegram-бот (tender-bot)

Бот для отдела по обработке тендерных заявок на базе LangChain + GigaChat.
База знаний.

**Запросы** → инструкции по работе в Bitrix24:
- `создать заявку на расходование ДС`
- `как зарегистрировать тендер`
- `как поставить задачу`
- `как посмотреть отчёт по тендерам`
- и другие (10+ шаблонов)

**Описание, сценарии и методика тестирования:** см. [Manual-bot.md](Manual-bot.md)

**Эндпоинты:**
- `POST /webhook/telegram` — входящие обновления от Telegram
- `POST /bitrix/webhook` — события из Bitrix24 CRM
- `GET /health` — проверка работоспособности

---

## REST API вебхуки Bitrix24

Nginx настроен маршрутизировать REST-запросы через `/bitrix/services/rest/index.php`:

```
GET/POST /event.get.json   → bx bx:webhook-reg (список)
GET/POST /event.bind.json  → bx bx:webhook-reg EVENT (регистрация)
GET/POST /rest/*           → Bitrix24 REST API
```

---

## Dex SSO

| Ресурс | URL |
|--------|-----|
| Discovery endpoint | http://localhost:5556/dex/.well-known/openid-configuration |
| Auth endpoint | http://localhost:5556/dex/auth |
| Gitea → Dex OAuth2 | Настраивается в `dex-config.yaml` |

Зарегистрированные клиенты: `grafana`, `rolemodel`, `bitrix-client-id`.

---

## Структура репозитория

```
bitrix-sandbox/
├── README.md              — Этот файл
├── Roadmap.md             — Дорожная карта
├── Planner.md             — Пошаговый план выполнения задач
├── Manual-bot.md          — Документация Telegram-бота
├── Result.md              — Результаты анализа и интеграции
├── Tasks.md               — Задачи автоматизации тендерного отдела
└── server-ansible/
    └── home/
        ├── docker-compose.yml    — Главный compose-файл
        ├── Dockerfile.php        — PHP 8.2 с bx CLI
        ├── nginx-bitrix.conf     — Nginx для Bitrix24 (с REST)
        ├── dex-config.yaml       — Конфигурация Dex
        ├── tender-bot/           — Telegram-бот
        │   ├── main.py
        │   ├── prompts.py
        │   ├── requirements.txt
        │   └── Dockerfile
        ├── docker/               — Расширенный compose с Laravel
        │   ├── docker-compose.yml
        │   ├── nginx-bitrix.conf
        │   ├── nginx-laravel.conf
        │   └── init/
        └── tmp/                  — Ядро Bitrix24 + local модули
            ├── bitrix/           — Ядро Bitrix24
            └── local/modules/rolemodel.cli/
                ├── cli.php       — CLI-диспетчер
                └── lib/
                    ├── Commands/ — Команды bx
                    └── DB/       — PostgresAdapter
```

---

## Методика тестирования для стороннего разработчика

### Предусловия

1. Установлен Docker Engine 24+, Docker Compose v2
2. Есть архивы `upload.tar` и `local.tar` с дистрибутивом Bitrix24

### Проверка модулей

```bash
# 1. Запустить стек
docker compose up -d --build

# 2. Дождаться завершения bitrix-setup
docker compose logs -f bitrix-setup

# 3. Проверить список вебхуков
docker exec bitrix-php bx bx:webhook-reg
# Ожидается JSON с 3 зарегистрированными вебхуками

# 4. Проверить диагностику
docker exec bitrix-php bx bx:health
# Ожидается [OK] для всех компонентов

# 5. Проверить Gitea
open http://localhost:3000

# 6. Проверить Dex
curl http://localhost:5556/dex/healthz
```

### Проверка Telegram-бота

```bash
# Health endpoint
curl http://localhost:3102/health
# {"status":"ok"}

# Имитация события Bitrix24
curl -X POST http://localhost:3102/bitrix/webhook \
  -d "event=ONCRMDEALADD&data[FIELDS][ID]=42"
```

Полная методика тестирования бота: [Manual-bot.md](Manual-bot.md)
