<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;

/**
 * bx:webhook-reg — регистрация и мониториг вебхуков.
 */
class WebhookRegCommand extends BaseCommand
{
    private array $events = [
        'ONCRMDEALADD'    => '/bitrix/webhook/crm-deal',
        'ONCRMDEALUPDATE' => '/bitrix/webhook/crm-deal',
        'ONCRMDEALDELETE' => '/bitrix/webhook/crm-deal',
    ];

    public function handle(array $args): int
    {
        // Если аргументов нет — выводим текущее состояние (для React Polling)
        if (empty($args)) {
            return $this->showStatus();
        }

        // Если есть аргументы (например, 'reg') — запускаем регистрацию
        return $this->registerWebhooks();
    }

    /**
     * Вывод JSON-статуса для фронтенда
     */
    private function showStatus(): int
    {
        try {
            $connection = Application::getConnection();
            
            // 1. Считаем очередь в БД (через ваш PostgresAdapter)
            $queueCount = $connection->queryScalar("SELECT count(*) FROM rolemodel_webhook_queue") ?: 0;

            // 2. Список уже зарегистрированных хуков в Б24 (из таблицы rest_event)
            $registered = $connection->query("SELECT event_name, handler FROM b_rest_event")->fetchAll();

            echo json_encode([
                'status' => 'ok',
                'timestamp' => time(),
                'queue_size' => (int)$queueCount,
                'registered_hooks' => $registered,
                'config_events' => $this->events
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return 0;
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Регистрация хуков через локальный REST-интерфейс
     */
    private function registerWebhooks(): int
    {
        $this->info("Регистрация вебхуков в Битрикс24...");

        $webhookUrl = 'http://127.0.0'; // Внутренний входящий вебхук
        $handlerBase = 'http://127.0.0.1:8080'; // PHP-обработчик на том же сервере

        $report = [];
        $hasErrors = false;

        foreach ($this->events as $event => $path) {
            $handlerUrl = rtrim($handlerBase, '/') . $path;
            
            // Формируем запрос к REST методу event.bind
            $apiUrl = rtrim($webhookUrl, '/') . '/event.bind.json';
            $payload = http_build_query([
                'EVENT'   => $event,
                'HANDLER' => $handlerUrl,
            ]);

            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $payload,
                    'timeout' => 5,
                ],
            ]);

            $response = @file_get_contents($apiUrl, false, $ctx);
            $data = json_decode($response, true);

            $isOk = isset($data['result']) && $data['result'] === true;
            
            $report[$event] = [
                'status'  => $isOk ? 'ok' : 'failed',
                'error'   => $data['error_description'] ?? ($isOk ? null : 'Connection failed')
            ];

            if (!$isOk) {
                $hasErrors = true;
                $this->warn("  [FAIL] {$event}: " . ($data['error_description'] ?? ''));
            } else {
                $this->success("  [OK]   {$event}");
            }
        }

        echo "\n" . json_encode(['status' => $hasErrors ? 'error' : 'ok', 'summary' => $report], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        return $hasErrors ? 1 : 0;
    }
}
