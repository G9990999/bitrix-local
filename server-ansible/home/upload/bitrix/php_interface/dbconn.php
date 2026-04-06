<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0); // Не выводим мусор в HTTP-ответ

// КРИТИЧНО: Чтобы Битрикс не думал, что установка не завершена
define("DBDebug", false);
define("BX_UTF", true);
define("BX_FILE_PERMISSIONS", 0644);
define("BX_DIR_PERMISSIONS", 0755);
define('CACHED_b_option', false);
define('CACHED_b_lang', false);
define('CACHED_b_user_field', false);
define('BITRIX_SKIP_INSTALL_CHECK', true);

spl_autoload_register(function ($class) {
    $prefix = 'RoleModel\\Cli\\';
    $baseDir = $_SERVER["DOCUMENT_ROOT"] . '/local/modules/rolemodel.cli/lib/';

    if (str_contains($class, $prefix)) {
        $relativeClass = str_replace([$prefix, '\\'], ['', '/'], $class);
        $file = $baseDir . $relativeClass . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Для Postgres в старом ядре Битрикса используем эти параметры
$DBType = "pgsql"; // В некоторых версиях именно pgsql активирует нужный класс
$DBHost = "127.0.0.1";
$DBLogin = "bitrix";
$DBPassword = "bitrix";
$DBName = "bitrix";

// Если вы используете свой адаптер, можно форсировать тип БД
$GLOBALS["DBType"] = "pgsql";
