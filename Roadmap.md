# Roadmap — Комплекс автоматизации / Automation Complex Roadmap

## Архитектура комплекса / System Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                     ИНФРАСТРУКТУРА / INFRASTRUCTURE                  │
│                                                                       │
│  ┌──────────────────────────┐   ┌──────────────────────────────────┐ │
│  │     server-ansible        │   │         server-zabbix            │ │
│  │  127.0.0.1                    │   127.0.0.2                  │ │
│  │                           │   │                                  │ │
│  │  ┌─────────────────────┐  │   │  ┌──────────────────────────┐   │ │
│  │  │  Gitea  :3000        │  │   │  │  Zabbix Server  :10051   │   │ │
│  │  │  (Git-сервер)        │  │   │  │  Zabbix Java GW :10052   │   │ │
│  │  ├─────────────────────┤  │   │  ├──────────────────────────┤   │ │
│  │  │  Bitrix-24 Nginx :8080│ │   │  │  Prometheus      :9090   │   │ │
│  │  │  bitrix-php (FPM)    │  │   │  │  Alertmanager    :9093   │   │ │
│  │  │  bitrix-db (MySQL)   │  │   │  ├──────────────────────────┤   │ │
│  │  ├─────────────────────┤  │   │  │  Grafana         :3000   │   │ │
│  │  │  Laravel Nginx  :8000│  │   │  │  Node Exporter   :9100   │   │ │
│  │  │  laravel-app (FPM)   │  │   │  ├──────────────────────────┤   │ │
│  │  │  laravel-db (PgSQL)  │  │   │  │  Zabbix Adapter  :8080   │   │ │
│  │  ├─────────────────────┤  │   │  │  (Go-сервис)             │   │ │
│  │  │  RoleModel UI  :3100  │  │   │  ├──────────────────────────┤   │ │
│  │  │  (React+shadcn)      │  │   │  │  PostgreSQL      :5432   │   │ │
│  │  └─────────────────────┘  │   │  └──────────────────────────┘   │ │
│  │                           │   │                                  │ │
│  │  [Ansible Playbooks]       │   │  [Мониторинг / Monitoring]      │ │
│  └──────────────────────────┘   └──────────────────────────────────┘ │
│                                                                       │
│  ┌───────────────────────────────────────────────────────────────┐   │
│  │                   Поток данных / Data Flow                     │   │
│  │                                                               │   │
│  │  server-ansible                                               │   │
│  │    Ansible (cron/push) ──→ zabbix-adapter:8080 (Go)           │   │
│  │                              │                                │   │
│  │                              ▼                                │   │
│  │                          Prometheus ──→ Grafana               │   │
│  │                              │                                │   │
│  │                              ▼                                │   │
│  │                         Alertmanager ──→ Telegram Bot         │   │
│  └───────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  ┌───────────────────────────────────────────────────────────────┐   │
│  │               CRM / Business Layer                            │   │
│  │                                                               │   │
│  │  Bitrix-24 CRM                                                │   │
│  │    ├── Smart-процессы (Тендеры, Сделки, Задачи)              │   │
│  │    ├── Telegram-бот (уведомления тендерного отдела)           │   │
│  │    └── Выгрузка сотрудников → RoleModel UI                    │   │
│  │                                                               │   │
│  │  RoleModel Service (server-ansible :3100)                     │   │
│  │    ├── React + shadcn/ui интерфейс                            │   │
│  │    ├── Умное поле ввода запроса (sklearn-based parsing) 
        │   │ - ИИ-модуль
│  │    ├── Генерация конфигов прав доступа                        │   │
│  │    └── manager.ts — управление ролями пользователей           │   │
│  └───────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Серверы / Servers

### server-ansible (`127.0.0.1`)
Узел управления Ansible + хостинг веб-сервисов.

| Сервис | Образ | Порт | Назначение |
|--------|-------|------|-----------|
| Gitea | `gitea/gitea:latest` | 3000 | Локальный Git-сервер |
| Bitrix Nginx | `nginx:alpine` | 8080 | Веб-сервер Bitrix-24 |
| Bitrix PHP-FPM | `php:8.2-fpm-alpine` | — | PHP для Bitrix |
| Bitrix DB | `postgresql:latest` | — | PostgreSQL для Bitrix |
| Laravel Nginx | `nginx:alpine` | 8000 | Веб-сервер Laravel |
| Laravel App | `php:8.2-fpm-alpine` | — | PHP-FPM для Laravel |
| Laravel DB | `postgres:16` | — | PostgreSQL для Laravel |
| RoleModel UI | `node:20-alpine` (build) | 3100 | Управление правами |

### server-zabbix (`127.0.0.2`)
Мониторинг и наблюдаемость.

| Сервис | Образ | Порт | Назначение |
|--------|-------|------|-----------|
| PostgreSQL | `postgres:18-alpine` | 5432 | БД Zabbix |
| Zabbix Server | `zabbix-server-pgsql` | 10051 | Сервер мониторинга |
| Zabbix Java GW | `zabbix-java-gateway` | 10052 | JMX-мониторинг Java |
| Zabbix Adapter | Go-сервис (custom) | 8080 | Push-метрики → Prometheus |
| Prometheus | `prom/prometheus` | 9090 | Хранение метрик |
| Alertmanager | `prom/alertmanager` | 9093 | Маршрутизация алертов |
| Grafana | `grafana/grafana` | 3000 | Дашборды |
| Node Exporter | `prom/node-exporter` | 9100 | Метрики хоста |

---

## Поток мониторинга / Monitoring Flow

```
server-ansible (Ansible cron)
    │
    │  ansible-playbook push_metrics.yml
    │  Сбор: cpu_vcpus, mem_free_mb, disk_free_gb, disk_total_gb
    ▼
server-zabbix zabbix-adapter:8080 (Go HTTP API)
    │
    │  POST /metrics
    ▼
Prometheus :9090
    │
    ├── alerts.yml (правила алертов)
    │       └── LowDiskSpace < 15% → FIRING
    │
    ├── Grafana :3000 (визуализация)
    │
    └── Alertmanager :9093
            │
            └── Telegram Bot → chat/group
```

---

## Дорожная карта / Roadmap

### Этап 1 — Базовая инфраструктура ✅ (выполнено)
- [x] server-ansible: Docker compose с Gitea, Bitrix, Laravel
- [x] server-zabbix: Prometheus + Grafana + Alertmanager стек
- [x] Zabbix Go-адаптер (push-метрики)
- [x] Ansible playbook для сбора и отправки метрик
- [x] Алерты диска (<15%) → Telegram

### Этап 2 — Стабилизация и доступность ✅ (выполнено)
- [x] Nginx конфигурации для Bitrix и Laravel (index.php)
- [x] Устранение 404/403 для Laravel и Bitrix
- [x] Health-check для всех Docker-сервисов (gitea, bitrix-php, bitrix-nginx, dex)
- [x] Автоперезапуск контейнеров при сбоях (`restart: unless-stopped`)
- [x] REST-маршрутизация nginx: `event.bind.json` / `event.get.json` → Bitrix REST API
- [x] Исправление Hairpin NAT в WebhookRegCommand (Docker service name вместо IP)
- [x] Исправление логического бага cli.php (|| → &&)
- [ ] Настройка SSL/TLS (Let's Encrypt или self-signed)
- [ ] Prometheus rule_files подключение alerts.yml

### Этап 3 — Телеграм-бот для тендерного отдела ✅ (выполнено)
- [x] Создание Telegram-бота (tender-bot, LangChain + GigaChat)
- [x] Интеграция в docker-compose.yml (порт 3102)
- [x] Шаблонные ответы на 10 сценариев (prompts.py)
- [x] Обработчик вебхуков Bitrix24 (`/bitrix/webhook`)
- [x] Документация Manual-bot.md с методикой тестирования
- [x] Автоматическая регистрация вебхуков при запуске (bitrix-setup)
- [ ] Настройка Telegram-каналов по категориям (требует реального токена)
- [ ] Синхронизация с Google Calendar (опционально)
- [ ] Smart-процессы Bitrix-24 → триггеры уведомлений (расширение)

### Этап 4 — Управление правами доступа (RoleModel) 🔐 (в работе)
- [x] Структура ролей (25+ ролей: Администратор, Бухгалтер, Менеджер, Кладовщик и др.)
- [x] Docker-сервис RoleModel UI (React + shadcn/ui, порт 3100)
- [ ] Умное поле ввода запроса (scikit-learn парсинг)
- [ ] Генерация JSON-конфигов ролевых групп
- [ ] Загрузка через manager.ts + отображение прав
- [ ] Привязка сотрудников из Bitrix-24 CRM к ролям

### Этап 5 — Авторизация через Dex (без Kubernetes) 🔑 (исследование)
- [ ] Эксперимент: Dex standalone (без K8s) как единый OAuth2-провайдер
- [ ] Gitea как Identity Provider (OAuth2)
- [ ] Dex связывает: Gitea → веб-приложения (Grafana, RoleModel)
- [ ] Защита ingress-ресурсов через Dex (nginx auth_request)
- [ ] Документирование результатов в Tasks.md

### Этап 6 — Расширение мониторинга 📊 (запланировано)
- [ ] Node Exporter на все хосты (server-test, server-bitrix)
- [ ] Ansible автоматизация добавления новых хостов
- [ ] Дашборды Grafana: Nginx запросы, PHP-FPM, Laravel метрики
- [ ] Алерты: CPU > 80%, RAM < 10%, сервис недоступен
- [ ] PG Backup автоматизация с ротацией

### Этап 7 — CI/CD и GitOps 🚀 (запланировано)
- [ ] Gitea Actions (аналог GitHub Actions) для auto-deploy
- [ ] Ansible роли вместо playbooks (переработка)
- [ ] Secrets management (Vault или env-файлы с шифрованием)
- [ ] Blue/Green деплой для Laravel и RoleModel

---

## Задачи / Tasks

Подробное описание задач автоматизации тендерного отдела и технические детали реализации модулей — см. [Tasks.md](Tasks.md).

---

## Конфигурационные файлы / Key Config Files

| Файл | Сервер | Назначение |
|------|--------|-----------|
| `server-ansible/home/docker/docker-compose.yml` | ansible | Основные сервисы |
| `server-ansible/home/docker/nginx-bitrix.conf` | ansible | Nginx для Bitrix |
| `server-ansible/home/docker/nginx-laravel.conf` | ansible | Nginx для Laravel |
| `server-ansible/home/docker/rolemodel/docker-compose.yml` | ansible | RoleModel сервис |
| `server-ansible/home/docker/monitoring/push_metrics.yml` | ansible | Ansible push-метрики |
| `server-ansible/home/docker/monitoring/hosts.ini` | ansible | Inventory хостов |
| `server-zabbix/docker/docker-compose.yml` | zabbix | Мониторинг стек |
| `server-zabbix/docker/prometheus.yml` | zabbix | Prometheus конфиг |
| `server-zabbix/docker/alerts.yml` | zabbix | Правила алертов |
| `server-zabbix/docker/alertmanager.yml` | zabbix | Telegram уведомления |
