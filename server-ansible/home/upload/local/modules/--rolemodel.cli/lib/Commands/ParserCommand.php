<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;

/**
 * bx:parser — управляет подписками сервисов на парсинг данных и регистрирует вебхуки.
 *
 * Использование:
 *   bx bx:parser                  — список всех подписанных сервисов
 *   bx bx:parser tender-bot       — подписать сервис и зарегистрировать webhook для парсера
 *
 * Пример:
 *   bx bx:parser tender-bot
 *
 * Переменные окружения:
 *   BITRIX_WEBHOOK_URL  — URL Bitrix24 REST API (Docker-сервис bitrix-nginx)
 *   HANDLER_BASE_URL    — Базовый URL обработчика вебхуков
 *   DB_HOST, DB_NAME, DB_USER, DB_PASSWORD — данные PostgreSQL
 */
class ParserCommand extends BaseCommand
{
    /**
     * Список известных сервисов и их конфигурация парсера
     */
    private array $knownServices = [
        'tender-bot' => [
            'description' => 'Тендерный бот — парсинг данных тендеров из Bitrix24 CRM',
            'webhook_path' => '/bitrix/webhook/parser/tender-bot',
            'events'       => ['ONCRMDEALADD', 'ONCRMDEALUPDATE'],
        ],
        'its-parser' => [
            'description' => 'Парсер документации its.1c.ru — обновление Базы Знаний',
            'webhook_path' => '/bitrix/webhook/parser/its',
            'events'       => [],
        ],
    ];

    public function handle(array $args): int
    {
        if (empty($args)) {
            return $this->listServices();
        }

        $serviceName = strtolower(trim($args[0]));
        return $this->subscribeService($serviceName);
    }

    /**
     * Выводит список всех подписанных сервисов из БД
     */
    private function listServices(): int
    {
        $this->info("Список сервисов, подписанных на парсинг данных:");

        $conn = $this->getDbConnection();
        if (!$conn) {
            return 1;
        }

        $this->ensureParserTable($conn);

        $result = pg_query($conn, "SELECT name, description, webhook_url, created_at FROM b_parser_subscriptions ORDER BY created_at DESC");
        if (!$result) {
            $this->error("Ошибка запроса к БД: " . pg_last_error($conn));
            pg_close($conn);
            return 1;
        }

        $rows = [];
        while ($row = pg_fetch_assoc($result)) {
            $rows[] = $row;
        }
        pg_close($conn);

        if (empty($rows)) {
            echo json_encode([
                'status'   => 'ok',
                'message'  => 'Нет подписанных сервисов. Используйте: bx bx:parser ИМЯ_СЕРВИСА',
                'services' => [],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            return 0;
        }

        echo json_encode([
            'status'   => 'ok',
            'total'    => count($rows),
            'services' => $rows,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        return 0;
    }

    /**
     * Подписывает сервис на парсинг и регистрирует webhook в Bitrix24
     */
    private function subscribeService(string $serviceName): int
    {
        $this->info("Подписка сервиса: {$serviceName}");

        $webhookUrl  = getenv('BITRIX_WEBHOOK_URL') ?: 'http://bitrix-nginx';
        $handlerBase = getenv('HANDLER_BASE_URL')   ?: 'http://127.0.0.1:3101';

        // Определяем конфигурацию сервиса
        $config = $this->knownServices[$serviceName] ?? [
            'description' => "Пользовательский сервис: {$serviceName}",
            'webhook_path' => "/bitrix/webhook/parser/{$serviceName}",
            'events'       => ['ONCRMDEALADD', 'ONCRMDEALUPDATE'],
        ];

        $handlerUrl = rtrim($handlerBase, '/') . $config['webhook_path'];

        // 1. Сохраняем подписку в PostgreSQL
        $conn = $this->getDbConnection();
        if (!$conn) {
            return 1;
        }

        $this->ensureParserTable($conn);

        // Проверяем, не существует ли уже подписка
        $existing = pg_query_params(
            $conn,
            "SELECT id FROM b_parser_subscriptions WHERE name = $1",
            [$serviceName]
        );

        if (pg_num_rows($existing) > 0) {
            $this->warn("Сервис '{$serviceName}' уже подписан. Обновляем конфигурацию...");
            pg_query_params(
                $conn,
                "UPDATE b_parser_subscriptions SET description = $1, webhook_url = $2, updated_at = NOW() WHERE name = $3",
                [$config['description'], $handlerUrl, $serviceName]
            );
        } else {
            pg_query_params(
                $conn,
                "INSERT INTO b_parser_subscriptions (name, description, webhook_url, created_at, updated_at) VALUES ($1, $2, $3, NOW(), NOW())",
                [$serviceName, $config['description'], $handlerUrl]
            );
        }

        if (pg_last_error($conn)) {
            $this->error("Ошибка сохранения подписки: " . pg_last_error($conn));
            pg_close($conn);
            return 1;
        }

        $this->success("Подписка сохранена в БД.");
        pg_close($conn);

        // 2. Регистрируем webhooks в Bitrix24 для каждого события
        $registeredEvents = [];
        foreach ($config['events'] as $eventName) {
            $result = $this->registerBitrixWebhook($webhookUrl, $handlerUrl, $eventName);
            $registeredEvents[] = [
                'event'   => $eventName,
                'status'  => $result ? 'ok' : 'error',
                'handler' => $handlerUrl,
            ];
        }

        echo json_encode([
            'status'      => 'ok',
            'service'     => $serviceName,
            'description' => $config['description'],
            'handler_url' => $handlerUrl,
            'events'      => $registeredEvents,
            'message'     => 'Сервис подписан. Webhook зарегистрирован.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        return 0;
    }

    /**
     * Регистрирует webhook в Bitrix24 через REST API
     */
    private function registerBitrixWebhook(string $webhookUrl, string $handlerUrl, string $eventName): bool
    {
        $this->info("  Регистрация события {$eventName} → {$handlerUrl}");

        $apiUrl  = rtrim($webhookUrl, '/') . '/event.bind.json';
        $payload = http_build_query([
            'EVENT'   => $eventName,
            'HANDLER' => $handlerUrl,
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => 'Content-Type: application/x-www-form-urlencoded',
                'content'       => $payload,
                'timeout'       => 10,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($apiUrl, false, $ctx);

        if ($response === false) {
            $this->warn("  Не удалось подключиться к Bitrix24 ({$apiUrl}) — сохраняем подписку локально.");
            return false;
        }

        $data = json_decode($response, true);
        $isOk = isset($data['result']) && $data['result'] === true;

        if ($isOk) {
            $this->success("  Webhook {$eventName} зарегистрирован.");
        } else {
            $this->warn("  Webhook {$eventName}: " . ($data['error_description'] ?? 'нет ответа от Bitrix'));
        }

        return $isOk;
    }

    /**
     * Создаёт таблицу подписок, если она не существует
     */
    private function ensureParserTable($conn): void
    {
        pg_query($conn, "
            CREATE TABLE IF NOT EXISTS b_parser_subscriptions (
                id          SERIAL PRIMARY KEY,
                name        VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                webhook_url VARCHAR(500),
                created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
            )
        ");
    }

    /**
     * Возвращает подключение к PostgreSQL из настроек Bitrix
     */
    private function getDbConnection()
    {
        // Пробуем прочитать настройки из .settings.php Битрикса
        $settingsFile = ($_SERVER["DOCUMENT_ROOT"] ?? '') . '/bitrix/.settings.php';
        if (file_exists($settingsFile)) {
            $settings = include $settingsFile;
            $db = $settings['connections']['value']['default'] ?? null;
            if ($db) {
                $conn = pg_connect(
                    "host={$db['host']} dbname={$db['database']} user={$db['login']} password={$db['password']}"
                );
                if ($conn) return $conn;
            }
        }

        // Fallback: переменные окружения
        $host     = getenv('DB_HOST')     ?: 'bitrix-db';
        $dbname   = getenv('DB_NAME')     ?: 'bitrix';
        $user     = getenv('DB_USER')     ?: 'bitrix';
        $password = getenv('DB_PASSWORD') ?: 'bitrix';

        $conn = pg_connect("host={$host} dbname={$dbname} user={$user} password={$password}");
        if (!$conn) {
            $this->error("Не удалось подключиться к PostgreSQL.");
            return null;
        }

        return $conn;
    }
}
