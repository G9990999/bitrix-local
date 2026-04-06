<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;
use RoleModel\Cli\DB\PostgresHelper;
use Bitrix\Main\Application;

class InitCommand extends BaseCommand
{
    // local/modules/rolemodel.cli/lib/Commands/InitCommand.php

  public function handle(array $args): int
  {
    $this->info("Холодная инициализация БД...");

    // 1. Берем конфиг напрямую из файла, так как ядро не загружено
    $settings = include $_SERVER["DOCUMENT_ROOT"] . '/bitrix/.settings.php';
    $db = $settings['connections']['value']['default'];

    try {
        // Используем PDO для PostgreSQL (убедитесь, что extension=pdo_pgsql включен в php.ini)
        $dsn = "pgsql:host={$db['host']};port=5432;dbname={$db['database']}";
        $pdo = new \PDO($dsn, $db['login'], $db['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        
        $this->success("Соединение установлено (PDO).");

        $dumpPath = $_SERVER["DOCUMENT_ROOT"] . '/local/database/migrations/dump.sql';
        $sqlContent = file_get_contents($dumpPath);
        $cleanSql = PostgresHelper::transformSql($sqlContent);

        // Разделяем запросы (улучшенный regex, чтобы не ломать строки с ;)
        $queries = preg_split("/;(?=(?:[^']*'[^']*')*[^']*$)/", $cleanSql);
        $successCount = 0;

        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query)) continue;

            try {
                $pdo->exec($query);
                $successCount++;
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'already exists')) {
                    $this->warn("Ошибка SQL: " . $e->getMessage());
                }
            }
        }

        $this->success("Таблицы созданы. Теперь ядро Битрикса сможет запуститься.");
        return 0;

      } catch (\Exception $e) {
        $this->error("Ошибка подключения: " . $e->getMessage());
        return 1;
      }
    }

}
