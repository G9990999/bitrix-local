<?php
header('Content-Type: application/json; charset=utf-8');

$dsn = "pgsql:host=127.0.0.1;port=5432;dbname=bitrix";
$user = 'bitrix'; $pass = 'bitrix';
$uri = $_SERVER['REQUEST_URI'];

// 1. POST: Только добавление
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $pdo = new \PDO($dsn, $user, $pass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->prepare("INSERT INTO rolemodel_webhook_queue (event_name, payload) VALUES (?, ?)");
        $stmt->execute([$input['event'] ?? 'UNKNOWN', json_encode($input['data'] ?? [])]);
        echo json_encode(['status' => 'ok', 'action' => 'inserted']);
    }
    exit;
}

// 2. GET /pop: Только извлечение через CLI
if (str_ends_with(rtrim($uri, '/'), 'pop')) {
    $cliPath = realpath(__DIR__ . '/../local/modules/rolemodel.cli/cli.php');
    // Добавим 2>&1 чтобы видеть ошибки PHP в выводе, если они будут
    $output = shell_exec("php $cliPath bx:webhook-pop 2>&1");
    echo $output;
    exit;
}

// 3. GET (default): Просто просмотр
try {
    $pdo = new \PDO($dsn, $user, $pass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    $res = $pdo->query("SELECT id, event_name as event, payload as data, created_at as ts FROM rolemodel_webhook_queue ORDER BY id DESC LIMIT 50")
               ->fetchAll(PDO::FETCH_ASSOC);
    
    // Декодируем payload сразу в PHP, чтобы React не мучался
    foreach ($res as &$row) {
        $row['data'] = json_decode($row['data'], true);
    }

    echo json_encode(['status' => 'ok', 'items' => $res, 'action' => 'list']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
