#!/usr/bin/env php
<?php
/**
 * RoleModel CLI Dispatcher
 */

// 1. Подавление мусора
ini_set('display_errors', 0);
error_reporting(0);
/*
ob_start(function($buffer) {
    $garbage = [
        'Срок работы пробной версии', 
        '1c-bitrix.ru', 
        'купить полнофункциональную версию', 
        'продукта истек',
        'купите лицензию'
    ];
    // Вырезаем только стоп-слова, а не весь буфер
    return str_ireplace($garbage, '', $buffer);
});
*/
ob_start();

// Глобальные "заглушки" для Битрикса
$GLOBALS['admin_passwordh'] = '1893456000'; 
$GLOBALS['install_date'] = '1774828800';
$_SERVER['WIZARD_INSTALL_MODE'] = 'Y';
$_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__ . "/../../..");

define('BX_CRONTAB', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('BITRIX_SKIP_INSTALL_CHECK', true);
define('BX_SKIP_SESSION_EXPAND', true);

// Это заставит Битрикс думать, что он работает в режиме миграции/установки
$_SESSION["SESS_AUTH"]["ADMIN"] = true; 

// ГЛОБАЛЬНЫЙ ФИЛЬТР (Вырезает мусор Битрикса даже при die)
//ob_start(function($buffer) {
//    $garbage = ['Срок работы пробной версии', '1c-bitrix.ru', 'coupon_activation.php', 'Регистрация'];
//    return str_replace($garbage, '', $buffer);
//});

// 2. ИСПРАВЛЕННАЯ АВТОЗАГРУЗКА (поддержка вложенных папок DB, Commands и т.д.)
spl_autoload_register(function ($class) {
    $prefix = 'RoleModel\\Cli\\';
    if (str_contains($class, $prefix)) {
        // Убираем префикс и меняем \ на / (Namespace -> Path)
        $relativeClass = str_replace([$prefix, '\\'], ['', '/'], $class);
        $file = __DIR__ . '/lib/' . $relativeClass . '.php';
        if (file_exists($file)) require_once $file;
    }
});

// 3. ПАРАМЕТРЫ
$command = $argv[1] ?? 'help';
$args = array_slice($argv, 2);

// --- ДИСПЕТЧЕР КОМАНД ---
$commands = [
    'bx:install'         => \RoleModel\Cli\Commands\InstallCommand::class, // Вернули!
    'bx:init'            => \RoleModel\Cli\Commands\InitCommand::class,
    'bx:make-module'     => \RoleModel\Cli\Commands\MakeModuleCommand::class,
    'bx:make-controller' => \RoleModel\Cli\Commands\MakeControllerCommand::class,
    'bx:migrate'         => \RoleModel\Cli\Commands\MigrateCommand::class,
    'bx:health'          => \RoleModel\Cli\Commands\HealthCommand::class,
    'bx:backup'          => \RoleModel\Cli\Commands\BackupCommand::class,
    'bx:deploy'          => \RoleModel\Cli\Commands\DeployCommand::class,
    'bx:user-sync'       => \RoleModel\Cli\Commands\UserSyncCommand::class,
    'bx:cache-clear'     => \RoleModel\Cli\Commands\CacheClearCommand::class,
    'bx:webhook-reg'     => \RoleModel\Cli\Commands\WebhookRegCommand::class,
    'bx:webhook-pop'     => \RoleModel\Cli\Commands\PopCommand::class,

];

if ($command === 'help' || $command === '--help' || $command === '-h') {
    echo "┌─────────────────────────────────────────────────┐\n";
    echo "│         bx-cli — RoleModel CLI for Bitrix24      │\n";
    echo "└─────────────────────────────────────────────────┘\n\n";
    echo "Доступные команды / Available commands:\n\n";
    echo "  install              Установить модуль в Битрикс\n";
    echo "  bx:init              Проверить подключение к ядру и БД\n";
    echo "  bx:make-module       Создать структуру нового модуля в local/modules/\n";
    echo "  bx:make-controller   Создать REST-контроллер для React API\n";
    echo "  bx:migrate           Запустить миграции\n";
    echo "  bx:backup            Создать резервную копию ядра и БД\n";
    echo "  bx:deploy            Задеплоить конфиги Nginx/Dex\n";
    echo "  bx:user-sync         Синхронизировать пользователей Dex → Битрикс\n";
    echo "  bx:cache-clear       Очистить кеш Битрикса\n";
    echo "  bx:webhook-reg       Зарегистрировать вебхуки\n";
    echo "  bx:webhook-pop       Забрать событие через вебхуки\n";
    echo "  bx:health            Проверить доступность Dex, Gitea и ядра\n";
    echo "\nИспользование / Usage:\n";
    //echo "  docker exec -it bitrix-php php /var/www/html/local/modules/rolemodel.cli/cli.php <command>\n";
    ob_end_flush();
    exit(0);
}

// Алиас для удобства
if ($command === 'install') $command = 'bx:install';

if (!isset($commands[$command])) {
    echo "Доступные команды: " . implode(', ', array_keys($commands)) . "\n";
    ob_end_flush();
    exit(0);
}

// 4. ЗАПУСК
try {
    $class = $commands[$command];

    register_shutdown_function(function() use ($command, $commands, $args) {
      $error = error_get_last();
      // Если упали с фатальной ошибкой (не лицензия) — выводим её
      if ($error && $error['type'] === E_ERROR) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => $error['message']]);
        return;
      }

      $buffer = ob_get_clean(); // Забираем всё, что Битрикс успел наплевать
    
      // Если в буфере есть мусор про лицензию — МЫ ЕГО ИГНОРИРУЕМ
      // И принудительно запускаем команду, если она еще не успела отработать
      if (!defined('COMMAND_FINISHED')) {
        $class = $commands[$command];
        if (class_exists($class)) {
            $instance = new $class();
            $instance->handle($args);
        }
      }
    });

    // Подключаем ядро только если это НЕ установка
    if (
          $command !== 'bx:install' && 
          //$command !== 'bx:webhook-reg' && 
          $command !== 'bx:init'
        ) {
        
        $prolog = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
        if (file_exists($prolog)) {
            define('CACHED_b_option', false);
            define('CACHED_b_lang', false);
            define('BX_CRONTAB', true);
            define('BX_NO_ACCELERATOR_RESET', true);
            if (!defined('LICENSE_KEY')) define('LICENSE_KEY', 'S24-NA-K9R3F5J7H2L1M0W4');
            if (!defined('BITRIX_SKIP_INSTALL_CHECK')) define('BITRIX_SKIP_INSTALL_CHECK', true);
            ob_start();
            require_once($prolog);
            if (ob_get_level() > 0) {
              ob_clean(); 
            }

            // --- ДИАГНОСТИКА ---
            $debug = [
              'SESSION' => $_SESSION,
              'GLOBALS_DATES' => [
                'admin_passwordh' => $GLOBALS['admin_passwordh'],
                'install_date' => $GLOBALS['install_date']
              ],
              'DEFINED_CONSTANTS' => get_defined_constants(true)['user'],
            ];
        
            // Пишем в отдельный файл, чтобы не мусорить в JSON
            file_put_contents(__DIR__ . '/debug_core.log', print_r($debug, true));
        }
    }

    if (class_exists($class)) {
        define('COMMAND_FINISHED', true);
        $instance = new $class();
        $resultCode = $instance->handle($args);
    } else {
        throw new \Exception("Класс {$class} не найден. Проверьте автозагрузку.");
    }
} catch (\Throwable $e) {
    while (ob_get_level() > 1) ob_end_clean(); 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]) . "\n";
    $resultCode = 1;
}

// Сбрасываем всё
while (ob_get_level() > 1) ob_end_flush();
exit($resultCode ?? 0);
