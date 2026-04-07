<?php
namespace RoleModel\Cli\Controllers;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Application;

class EventController extends Controller
{
    // Отключаем проверку CSRF и сессии для простоты интеграции с Vite
    protected function init()
    {
        parent::init();
        header('Content-Type: application/json');
    }

    public function configureActions()
    {
        return [
            'list' => ['prefilters' => []], // Без авторизации для теста
        ];
    }

    public function listAction()
    {
        $connection = Application::getConnection();
        
        // Запрос к нашей очереди (или таблице активов, если она есть)
        // Если данных нет, вернем тестовый массив для фронтенда
        $sql = "SELECT id as \"ID\", event_name as \"NAME\", payload as \"DATA\" FROM rolemodel_webhook_queue LIMIT 50";
        $res = $connection->query($sql);
        $items = [];

        while ($row = $res->fetch()) {
            $data = json_decode($row['DATA'], true);
            $items[] = [
                'ID' => (int)$row['ID'],
                'NAME' => $row['NAME'],
                'ASSET_TAG' => $data['tag'] ?? 'WEBHOOK-' . $row['ID'],
                'SERIAL' => $data['serial'] ?? 'SN-' . time(),
                'STATUS' => 'active',
                'DATE_CREATE' => date('Y-m-d H:i:s')
            ];
        }

        return [
            'status' => 'ok',
            'items' => $items
        ];
    }

    // local/modules/rolemodel.cli/lib/Controllers/EventController.php

    public function popAction()
    {
      $connection = \Bitrix\Main\Application::getConnection();
    
      // 1. Получаем данные
      $res = $connection->query("SELECT id, event_name, payload FROM rolemodel_webhook_queue ORDER BY id ASC");
      $items = [];
      $ids = [];

      while ($row = $res->fetch()) {
        $id = $row['id'] ?? $row['ID'];
        $items[] = [
            'ID' => (int)$id,
            'NAME' => $row['event_name'] ?? $row['EVENT_NAME'],
            'STATUS' => 'active',
            'DATE_CREATE' => date('Y-m-d')
        ];
        $ids[] = (int)$id;
      }

      // 2. Очищаем очередь
      if (!empty($ids)) {
        $connection->queryExecute("DELETE FROM rolemodel_webhook_queue WHERE id IN (" . implode(',', $ids) . ")");
      }

      return ['items' => $items];
    }

}
