<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;
use Bitrix\Main\Application;

/**
 * bx:webhook-pop — извлекает накопленные хуки из очереди и очищает её.
 */
class PopCommand extends BaseCommand
{
    public function handle(array $args): int
    {
        try {
            $connection = Application::getConnection();
            
            // 1. Извлекаем все записи из очереди
            $sql = "SELECT id, event_name, payload, created_at FROM rolemodel_webhook_queue ORDER BY id ASC";
            $result = $connection->query($sql);
            $items = [];
            $ids = [];

            while ($row = $result->fetch()) {
              // ВНИМАНИЕ: Используем нижний регистр ключей!
              $id = $row['id'] ?? $row['ID']; 
              $event = $row['event_name'] ?? $row['EVENT_NAME'];
              $payload = $row['payload'] ?? $row['PAYLOAD'];
              $ts = $row['created_at'] ?? $row['CREATED_AT'];

              $items[] = [
                  'id' => (int)$id,
                  'event' => $event,
                  'data' => json_decode((string)$payload, true),
                  'ts' => $ts
                ];
                if ($id) $ids[] = (int)$id;
            }

            // 2. Если есть что удалять — удаляем атомарно
            if (!empty($ids)) {
                $connection->queryExecute("DELETE FROM rolemodel_webhook_queue WHERE id IN (" . implode(',', $ids) . ")");
            }

            // 3. Выводим чистый JSON для Vitejs/React
            echo json_encode([
                'status' => 'ok',
                'count' => count($items),
                'items' => $items
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return 0;

        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            return 1;
        }
    }
}
