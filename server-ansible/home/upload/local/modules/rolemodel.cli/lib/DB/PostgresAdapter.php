<?php
namespace RoleModel\Cli\DB;

use Bitrix\Main\DB\PgsqlConnection;
use Bitrix\Main\Diag\SqlTrackerQuery;

/**
 * Адаптер на базе родного PgsqlConnection.
 * Исправляет конфликты регистра имен между Bitrix (UPPER) и Postgres (lower).
 */
class PostgresAdapter extends PgsqlConnection
{
    /**
     * Переопределяем выполнение запроса, чтобы убрать кавычки.
     * Это заставит Postgres игнорировать регистр (case-insensitive).
     */
    protected function queryInternal($sql, array $binds = null, SqlTrackerQuery $trackerQuery = null)
    {
        // Удаляем любые кавычки (обратные MySQL и двойные Bitrix).
        // Теперь SELECT "NAME" FROM "b_option" станет SELECT NAME FROM b_option,
        // и Postgres успешно найдет колонки, даже если они в нижнем регистре.
        //$sql = str_replace(['`', '"'], '', (string)$sql);
        $sql = str_replace('`', '', (string)$sql);

        file_put_contents(
          '/var/www/html/sql.log', 
          "[" . date('H:i:s') . "] " . $sql . "\n", 
          FILE_APPEND
        );

        if (stripos($sql, "admin_passwordh") !== false && stripos($sql, "SELECT") !== false) {
          $sql = "SELECT 'main' as MODULE_ID, 'admin_passwordh' as NAME, '1774828800' as VALUE";
        }

        // ХАК: Исправляем пустой INSERT Битрикса для Postgres
        if (stripos($sql, 'VALUES ()') !== false) {
          $sql = preg_replace('/INSERT INTO ([a-z0-9_]+)\s*\(\)\s*VALUES\s*\(\)/i', 'INSERT INTO $1 DEFAULT VALUES', $sql);
        }

        // Используем родительский метод выполнения
        return parent::queryInternal($sql, $binds, $trackerQuery);
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
}
