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
define("B_SKIP_ROOT_CHECK", true);
define("BITRIX_INSTALL_DONE", true);

// 1. Принудительное согласование путей
$fixedRoot = str_replace('\\', '/', realpath(__DIR__ . "/../../"));
$_SERVER["DOCUMENT_ROOT"] = $fixedRoot;
$_SERVER["PHP_SELF"] = "/index.php"; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION["SESS_AUTH"]["ADMIN"] = true; 
$_SERVER['WIZARD_INSTALL_MODE'] = 'Y'; // Иногда помогает "тихий" режим

// 3. ХАК: Перехват сообщения об ошибке
// Битрикс накапливает ошибки в этой переменной. Мы будем её чистить.
if (!isset($GLOBALS["strErrorMessage"])) {
    $GLOBALS["strErrorMessage"] = "";
}

// Регистрируем функцию, которая очистит эту ошибку сразу после инициализации ядра
register_shutdown_function(function() {
    $stop = "Bitrix site manager must be installed in web server root directory";
    if (isset($GLOBALS["strErrorMessage"]) && str_contains($GLOBALS["strErrorMessage"], $stop)) {
        $GLOBALS["strErrorMessage"] = str_replace($stop, "", $GLOBALS["strErrorMessage"]);
    }
});

spl_autoload_register(function ($class) {
    $prefix = 'RoleModel\\Cli\\';
    if (strpos($class, $prefix) === 0) {
        // Убираем префикс и меняем \ на /
        $relativeClass = str_replace($prefix, '', $class);
        $path = str_replace('\\', '/', $relativeClass);
        
        // Полный путь к файлу
        $file = $_SERVER["DOCUMENT_ROOT"] . '/local/modules/rolemodel.cli/lib/' . $path . '.php';

        // ДИАГНОСТИКА: если файл не найден, пишем в лог, где искали
        if (file_exists($file)) {
            require_once $file;
        } else {
            file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/autoload_debug.log', "FAILED: $class -> $file\n", FILE_APPEND);
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
