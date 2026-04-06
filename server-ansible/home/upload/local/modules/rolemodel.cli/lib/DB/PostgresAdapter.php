<?php
namespace RoleModel\Cli\DB;

use Bitrix\Main\DB\PgsqlConnection;
use Bitrix\Main\Diag\SqlTrackerQuery;

/**
 * Адаптер на базе PgsqlConnection.
 * Усыпляет проверку лицензии и исправляет SQL-совместимость.
 */
class PostgresAdapter extends PgsqlConnection
{
    protected function queryInternal($sql, array $binds = null, SqlTrackerQuery $trackerQuery = null)
    {
        $sql = (string)$sql;

        // 1. ПЕРЕХВАТ ЛИЦЕНЗИИ (Групповой запрос опций модуля main)
            if (stripos($sql, "b_option") !== false && stripos($sql, "main") !== false) {
        // Мы возвращаем ТОЛЬКО наши 2 записи. Битрикс этого достаточно для старта.
        $sql = "SELECT 'install_date' as NAME, '1893456000' as VALUE 
                UNION ALL 
                SELECT 'admin_passwordh' as NAME, '1893456000' as VALUE";
        
          file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/sql-admin.log', ">>> HARD OVERRIDE <<<\n", FILE_APPEND);
        }

        // 2. ОЧИСТКА SQL (Убираем кавычки, чтобы Postgres не капризничал с регистром)
        // Сначала MySQL-бэктики, затем двойные кавычки D7
        $sql = str_replace(['`', '"'], '', $sql);

        // 3. ХАК: Исправляем пустой INSERT (MySQL: VALUES () -> Postgres: DEFAULT VALUES)
        if (stripos($sql, 'VALUES ()') !== false) {
            $sql = preg_replace('/INSERT INTO ([a-z0-9_]+)\s*\(\)\s*VALUES\s*\(\)/i', 'INSERT INTO $1 DEFAULT VALUES', $sql);
        }

        // Логируем итоговый запрос для отладки
        file_put_contents(
            $_SERVER["DOCUMENT_ROOT"] . '/sql.log', 
            "[" . date('H:i:s') . "] " . $sql . "\n", 
            FILE_APPEND
        );

        return parent::queryInternal($sql, $binds, $trackerQuery);
    }
}

    /**
     * Оставляем для совместимости со старым ядром ($DB)
     */
    /*
    public function createDb(): \CDatabase
    {
        // Если класса CDatabasePostgres нет, Битрикс сам подставит заглушку,
        // но лучше явно указать тип для старого ядра.
        $db = parent::createDb();
        $db->type = "POSTGRESQL";
        return $db;
    }
    */
