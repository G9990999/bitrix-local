<?php
// 1. Глушим мусор на выходе
ob_start(function($buffer) {
    $garbage = ['Срок работы пробной версии', '1c-bitrix.ru', 'купить полнофункциональную версию'];
    return str_ireplace($garbage, '', $buffer);
});

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_CRONTAB', true);

// 2. РЕГИСТРИРУЕМ ЗАПИСЬ В ОЧЕРЕДЬ ПЕРЕД ВЫХОДОМ
register_shutdown_function(function() {
    $eventName = $_POST['event'] ?? 'UNKNOWN';
    $data = $_POST['data'] ?? '';

    // Прямое подключение к PostgreSQL через PDO (минуя ядро Битрикса)
    try {
        $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=bitrix";
        $pdo = new \PDO($dsn, 'bitrix', 'bitrix', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        
        $stmt = $pdo->prepare("INSERT INTO rolemodel_webhook_queue (event_name, payload) VALUES (?, ?)");
        $stmt->execute([$eventName, json_encode($data)]);
        
        // Логируем успех для себя в файл
        file_put_contents($_SERVER["DOCUMENT_ROOT"].'/webhook_debug.log', "[".date('H:i:s')."] SAVED: $eventName\n", FILE_APPEND);
    } catch (\Exception $e) {
        file_put_contents($_SERVER["DOCUMENT_ROOT"].'/webhook_debug.log', "[".date('H:i:s')."] PDO ERROR: ".$e->getMessage()."\n", FILE_APPEND);
    }
});

// 3. ЗАПУСКАЕМ ЯДРО
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Если ядро не сделало die(), помечаем, что всё ок
define('CORE_FINISHED', true);
