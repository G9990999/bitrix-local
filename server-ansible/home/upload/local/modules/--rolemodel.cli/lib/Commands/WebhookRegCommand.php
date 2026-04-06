<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;

/**
 * bx:webhook-reg — регистрирует входящие вебхуки в Битрикс24 через REST.
 *
 * Использование:
 *   bx bx:webhook-reg                  — список всех зарегистрированных вебхуков
 *   bx bx:webhook-reg 'ONCRMDEALADD'   — зарегистрировать конкретный вебхук по имени события
 */
class WebhookRegCommand extends BaseCommand
{
    /**
     * Карта событий: ключ — событие Б24, значение — путь обработчика
     */
    private array $events = [
        'ONCRMDEALADD'    => '/bitrix/webhook/crm-deal',
        'ONCRMDEALUPDATE' => '/bitrix/webhook/crm-deal',
        'ONCRMDEALDELETE' => '/bitrix/webhook/crm-deal',
    ];

    public function handle(array $args): int
    {
        // Используем имя Docker-сервиса вместо внешнего IP (решение проблемы Hairpin NAT)
        $webhookUrl  = getenv('BITRIX_WEBHOOK_URL') ?: 'http://bitrix-nginx';
        $handlerBase = getenv('HANDLER_BASE_URL')   ?: 'http://127.0.0.1:3101';

        // Без аргументов — показываем список зарегистрированных вебхуков
        if (empty($args)) {
            return $this->listWebhooks($webhookUrl);
        }

        // С аргументом — регистрируем указанный вебхук
        $eventName = strtoupper(trim($args[0]));
        return $this->registerWebhook($webhookUrl, $handlerBase, $eventName);
    }

    /**
     * Выводит список всех зарегистрированных вебхуков через event.get
     */
    private function listWebhooks(string $webhookUrl): int
    {
        $this->info("Получение списка зарегистрированных вебхуков...");

        $apiUrl = rtrim($webhookUrl, '/') . '/event.get.json';

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => '',
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($apiUrl, false, $ctx);

        if ($response === false) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Connection failed',
                'url'     => $apiUrl,
            ], JSON_UNESCAPED_UNICODE) . "\n";
            return 1;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            echo json_encode([
                'status'  => 'error',
                'message' => $data['error_description'] ?? $data['error'],
                'url'     => $apiUrl,
            ], JSON_UNESCAPED_UNICODE) . "\n";
            return 1;
        }

        $events = $data['result'] ?? [];

        if (empty($events)) {
            echo json_encode([
                'status'  => 'ok',
                'message' => 'Вебхуки не зарегистрированы',
                'events'  => [],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            return 0;
        }

        echo json_encode([
            'status' => 'ok',
            'total'  => count($events),
            'events' => $events,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        return 0;
    }

    /**
     * Регистрирует вебхук для указанного события
     */
    private function registerWebhook(string $webhookUrl, string $handlerBase, string $eventName): int
    {
        $this->info("Регистрация вебхука: {$eventName}");

        // Если имя события из нашей карты — используем заданный путь, иначе общий
        $path       = $this->events[$eventName] ?? '/bitrix/webhook/generic';
        $handlerUrl = rtrim($handlerBase, '/') . $path;
        $apiUrl     = rtrim($webhookUrl, '/') . '/event.bind.json';

        $payload = http_build_query([
            'EVENT'   => $eventName,
            'HANDLER' => $handlerUrl,
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $payload,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($apiUrl, false, $ctx);

        if ($response === false) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Connection failed',
                'url'     => $apiUrl,
            ], JSON_UNESCAPED_UNICODE) . "\n";
            return 1;
        }

        $data   = json_decode($response, true);
        $isOk   = isset($data['result']) && $data['result'] === true;

        echo json_encode([
            'status'  => $isOk ? 'ok' : 'error',
            'event'   => $eventName,
            'handler' => $handlerUrl,
            'message' => $isOk
                ? 'Вебхук зарегистрирован'
                : ($data['error_description'] ?? 'Ошибка регистрации'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        return $isOk ? 0 : 1;
    }
}
