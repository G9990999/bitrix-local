#!/usr/bin/env php
<?php
/**
 * RoleModel CLI Dispatcher
 */

// 1. Подавление мусора
ini_set('display_errors', 0);
error_reporting(0);

// Глобальные установки для Битрикса
$GLOBALS['admin_passwordh'] = '1893456000'; 
$GLOBALS['install_date'] = '1774828800';
$_SERVER['WIZARD_INSTALL_MODE'] = 'Y';
$_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__ . "/../../..");

// ГЛОБАЛЬНЫЙ ФИЛЬТР (Вырезает мусор Битрикса даже при die)
ob_start(function($buffer) {
    $garbage = ['Срок работы пробной версии', '1c-bitrix.ru', 'coupon_activation.php', 'Регистрация'];
    return str_replace($garbage, '', $buffer);
});

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
    'bx:parser'          => \RoleModel\Cli\Commands\ParserCommand::class,
    'bx:role'            => \RoleModel\Cli\Commands\RoleTableCommand::class,
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
    echo "  bx:health            Проверить доступность Dex, Gitea и ядра\n";
    echo "  bx:parser            Управление подписками парсера данных\n";
    echo "                       bx bx:parser             — список подписанных сервисов\n";
    echo "                       bx bx:parser tender-bot  — подписать сервис + webhook\n";
    echo "  bx:role              Управление каталогом должностей и ролей доступа\n";
    echo "                       bx bx:role               — список должностей\n";
    echo "                       bx bx:role install       — загрузить из CSV\n";
    echo "                       bx bx:role generator     — генерация прав через GigaChat\n";
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
    
    // Подключаем ядро только если это НЕ установка и НЕ команды с прямым PostgreSQL
    $noCoreCmds = ['bx:install', 'bx:webhook-reg', 'bx:parser', 'bx:role'];
    if (!in_array($command, $noCoreCmds, true)) {
        
        $prolog = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
        if (file_exists($prolog)) {
            ob_start();
            include_once($prolog);
            ob_clean(); 
        }
    }

    if (class_exists($class)) {
        $instance = new $class();
        $resultCode = $instance->handle($args);
    } else {
        throw new \Exception("Класс {$class} не найден. Проверьте автозагрузку.");
    }
} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]) . "\n";
    $resultCode = 1;
}

// Сбрасываем всё
while (ob_get_level() > 0) ob_end_flush();
exit($resultCode ?? 0);
