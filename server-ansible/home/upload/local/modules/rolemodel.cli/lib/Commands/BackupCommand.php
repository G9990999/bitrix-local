<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;

/**
 * bx:backup — создаёт резервную копию ядра Битрикс и дамп базы данных.
 */
class BackupCommand extends BaseCommand
{
    public function handle(array $args): int
    {
        $this->info("Создание резервной копии...");

        $root      = $_SERVER["DOCUMENT_ROOT"];
        $timestamp = date('Y-m-d_H-i-s');
        $backupDir = getenv('BACKUP_DIR') ?: '/tmp/bitrix-backups';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // 1. Archive /bitrix directory (excluding cache)
        $archivePath = "{$backupDir}/bitrix-core_{$timestamp}.tar.gz";
        $this->info("Архивирование ядра: {$archivePath}");
        $cmd = sprintf(
            'tar -czf %s --exclude=%s/bitrix/cache --exclude=%s/bitrix/stack_cache -C %s bitrix 2>&1',
            escapeshellarg($archivePath),
            escapeshellarg($root),
            escapeshellarg($root),
            escapeshellarg($root)
        );
        exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            $this->error("Ошибка архивирования: " . implode("\n", $output));
            return 1;
        }
        $this->success("Архив создан: {$archivePath}");

        // 2. MySQL dump via Bitrix DB settings
        $dbDumpPath = "{$backupDir}/bitrix-db_{$timestamp}.sql.gz";
        $this->info("Дамп базы данных: {$dbDumpPath}");

        try {
            $connection = \Bitrix\Main\Application::getConnection();
            $dbConfig   = $connection->getConfiguration();
            $host     = $dbConfig['host'] ?? 'localhost';
            $database = $dbConfig['database'] ?? 'bitrix';
            $login    = $dbConfig['login'] ?? 'bitrix';
            $password = $dbConfig['password'] ?? '';

            $dumpCmd = sprintf(
                'mysqldump -h %s -u %s -p%s %s | gzip > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($login),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($dbDumpPath)
            );
            exec($dumpCmd, $dumpOutput, $dumpExit);
            if ($dumpExit !== 0) {
                $this->warn("Ошибка дампа БД: " . implode("\n", $dumpOutput));
            } else {
                $this->success("Дамп БД создан: {$dbDumpPath}");
            }
        } catch (\Exception $e) {
            $this->warn("Не удалось получить параметры БД: " . $e->getMessage());
        }

        $this->success("Резервное копирование завершено. Файлы: {$backupDir}");
        return 0;
    }
}
