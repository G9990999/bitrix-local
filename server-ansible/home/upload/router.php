<?php
// 1. Начинаем захват всего вывода СРАЗУ
// C:/home/Php/router.php

ob_start(function($buffer) {
    // Список точных фраз, которые мы вырезаем
    $garbage = [
        'Bitrix site manager must be installed in web server root directory.',
        'Please modify the server\'s configuration or contact administrator of your hosting.',
        '<font color="#FF0000">',
        '</font>',
        '<br />',
        'Срок работы пробной версии продукта истек.',
        'Вы можете купить полнофункциональную версию продукта на сайте',
        'www.1c-bitrix.ru'
    ];
    
    // Просто вырезаем мусор, сохраняя JSON
    return str_ireplace($garbage, '', $buffer);
});

// 2. Исправляем окружение ПЕРЕД любыми инклудами
$_SERVER["DOCUMENT_ROOT"] = str_replace('\\', '/', __DIR__);
$_SERVER["PHP_SELF"] = "/index.php";
$_SERVER["HTTP_HOST"] = "localhost:8080";

if (str_contains($_SERVER['REQUEST_URI'], '/api/')) {
    define("NOSESSION", true);
    define("BX_SKIP_SESSION_EXPAND", true);
    // Принудительно очищаем куки в текущем запросе, чтобы ядро их не нашло
    unset($_COOKIE[session_name()]);
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    ob_end_flush();
    return false;
}

if (strpos($uri, '/api/') === 0) {
    include_once(__DIR__ . '/api/index.php');
    exit;
}

include_once(__DIR__ . '/index.php');
