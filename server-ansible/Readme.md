# server-ansible

**IP-адреса / IP Addresses:**
- Внутренний / Internal: `127.0.0.1:22`

  - Последняя версия документации к системе доступна по адресу
  - http://www.1c-bitrix.ru/sitemanager/doc.php

## Архитектура / Architecture

Сервер управляет инфраструктурой через Ansible и предоставляет следующие сервисы в Docker:

```
server-ansible
├── Gitea          (локальный Git-сервер / local Git server)        :3000
├── Bitrix CLI     (инструменты Bitrix / Bitrix tools)               :—
└── Laravel        (PHP-приложение / PHP application)                :8000
```

## Сервисы / Services

### Gitea
Локальный Git-сервис, аналог GitHub — для хранения и управления репозиториями внутри инфраструктуры.
- Образ / Image: `gitea/gitea:latest`
- Порт / Port: `3000`
- Документация: https://docs.gitea.com/installation/install-with-docker

### Bitrix CLI
Инструменты командной строки для работы с проектами на 1C-Bitrix.
- Образ / Image: `bitnami/php-fpm` (с установленным bitrix-env)
- Применение: инициализация, миграции, обслуживание Bitrix-проектов

### Laravel
PHP-приложение на фреймворке Laravel, развёрнутое в Docker.
- Образ / Image: `php:8.2-fpm` + nginx
- Порт / Port: `8000`
- База данных: PostgreSQL или MySQL (опционально)

## Ansible
Данный сервер является управляющим узлом Ansible. Плейбуки расположены в директории `playbooks/` и применяются к хостам из файла `hosts`.

### Файл hosts

## Запуск / Startup

```bash
# Запуск всех сервисов
cd home/docker
docker compose up -d

# Проверка статуса
docker compose ps

# Логи сервиса
docker compose logs -f <service-name>
```

## Тестирование / Testing

```bash
# Проверка доступности Gitea
curl -s http://localhost:3000/api/healthz

# Проверка Laravel
curl -s http://localhost:8000

# Ansible ping всех хостов
ansible all -i hosts -m ping
```