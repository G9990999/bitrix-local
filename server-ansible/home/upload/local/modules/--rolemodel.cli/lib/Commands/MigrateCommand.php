<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;

/**
 * bx:migrate — применяет SQL-миграции из директории lib/migrations/.
 *
 * Миграции — это PHP-файлы вида YYYYMMDD_HHMMSS_description.php,
 * каждый из которых содержит функцию migrate_up(): string возвращающую SQL.
 */
class MigrateCommand extends BaseCommand
{
    private string $migrationsDir;
    private string $migrationsTable = 'b_rolemodel_migrations';

    public function handle(array $args): int
    {
        $this->info("Запуск миграций...");

        $root = $_SERVER["DOCUMENT_ROOT"];
        $this->migrationsDir = __DIR__ . "/../../migrations";

        if (!is_dir($this->migrationsDir)) {
            mkdir($this->migrationsDir, 0755, true);
            $this->info("Директория миграций создана: {$this->migrationsDir}");
        }

        $connection = Application::getConnection();
        $this->ensureMigrationsTable($connection);

        $applied = $this->getAppliedMigrations($connection);
        $files   = glob($this->migrationsDir . "/*.php") ?: [];
        sort($files);

        $count = 0;
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $applied, true)) {
                continue;
            }

            $this->info("  Применяем: {$name}");
            require_once $file;
            $sql = migrate_up();

            try {
                $connection->query($sql);
                $connection->query(
                    "INSERT INTO {$this->migrationsTable} (name, applied_at) VALUES ('" .
                    $connection->getSqlHelper()->forSql($name) . "', NOW())"
                );
                $this->success("  Применено: {$name}");
                $count++;
            } catch (SqlQueryException $e) {
                $this->error("  Ошибка в {$name}: " . $e->getMessage());
                return 1;
            }
        }

        if ($count === 0) {
            $this->info("Нет новых миграций.");
        } else {
            $this->success("Применено миграций: {$count}");
        }
        return 0;
    }

    private function ensureMigrationsTable($connection): void
    {
        $connection->query(
            "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL
            )"
        );
    }

    private function getAppliedMigrations($connection): array
    {
        $result = $connection->query("SELECT name FROM {$this->migrationsTable}");
        $applied = [];
        while ($row = $result->fetch()) {
            $applied[] = $row['name'];
        }
        return $applied;
    }
}
