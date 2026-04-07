<?php
/**
 * RoleModel Headless API Entry Point
 * Идеально согласованная среда для Bitrix24 + PostgreSQL + Vite Proxy
 */

// 1. ПРЕДВАРИТЕЛЬНАЯ НАСТРОЙКА СРЕДЫ
mb_internal_encoding("UTF-8");
error_reporting(0);
ini_set('display_errors', 0);

if (str_contains($_SERVER['REQUEST_URI'], '/api/')) {
    define("NOSESSION", true);
    define("BX_SKIP_SESSION_EXPAND", true);
    // Принудительно очищаем куки в текущем запросе, чтобы ядро их не нашло
    unset($_COOKIE[session_name()]);
}

// Нормализация путей и эмуляция корня (убивает ошибку Root Directory)
$basePath = str_replace('\\', '/', realpath(__DIR__ . "/.."));
$_SERVER["DOCUMENT_ROOT"]   = $basePath;
$_SERVER["PHP_SELF"]        = "/index.php"; 
$_SERVER["SCRIPT_NAME"]     = "/index.php";
$_SERVER["SCRIPT_FILENAME"] = $basePath . "/index.php";

// Согласование хостов (убирает подозрения ядра на прокси Vite)
$_SERVER["HTTP_HOST"]       = "localhost:8080";
$_SERVER["SERVER_NAME"]     = "localhost";
$_SERVER["SERVER_PORT"]     = "8080";
if (isset($_SERVER['HTTP_REFERER'])) {
    $_SERVER['HTTP_REFERER'] = "http://localhost:8080/";
}

// 2. КОНСТАНТЫ-УСЫПИТЕЛИ (включаем до загрузки ядра)
define('BITRIX_INSTALL_DONE', true);
define('BITRIX_SKIP_INSTALL_CHECK', true);
define('B_SKIP_ROOT_CHECK', true);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_CRONTAB', true);
define('ADMIN_SECTION', true); // Отключает редиректы на регистрацию/визард

// Запускаем буферизацию для перехвата любого мусора
//ob_start();

ob_start(function($buffer) {
    $start = strpos($buffer, '{');
    $end = strrpos($buffer, '}');
    if ($start !== false && $end !== false) {
        return substr($buffer, $start, $end - $start + 1);
    }
    return ""; // Если JSON не найден, не выводим ничего (включая "Регистрацию")
});

// 3. СПАСАТЕЛЬНЫЙ КРУГ (сработает при внезапном die() или exit() ядра)
register_shutdown_function(function() {
    if (!defined('API_DONE')) {
        // Очищаем все уровни буферов от "предсмертных" криков Битрикса
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        try {
            $controller = new \RoleModel\Cli\Controllers\EventController();
            $uri = $_SERVER['REQUEST_URI'];
            $res = str_contains($uri, '/pop') ? $controller->popAction() : $controller->listAction();
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
});

// 4. ЗАГРУЗКА ЯДРА С ПОДАВЛЕНИЕМ ОШИБОК PHP 8.4
set_error_handler(function() { return true; });
try {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
} finally {
    restore_error_handler();
}

// Если ядро успешно прогрузилось - выкидываем всё, что оно успело написать
if (ob_get_level() > 0) {
    ob_clean();
}
define('API_DONE', true);

// 5. ФИНАЛЬНЫЙ ВЫВОД JSON
header('Content-Type: application/json; charset=utf-8');
$controller = new \RoleModel\Cli\Controllers\EventController();
$uri = $_SERVER['REQUEST_URI'];
$res = str_contains($uri, '/pop') ? $controller->popAction() : $controller->listAction();

echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Принудительный выход, чтобы не сработали лишние события эпилога
exit;
