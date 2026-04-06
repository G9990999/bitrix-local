<?php
namespace RoleModel\Cli\DB;

class PostgresHelper
{
    public static function transformSql(string $sql): string
    {
    $replacements = [
        // 1. Сначала УДАЛЯЕМ только то, что реально мешает (MySQL специфику)
        '/ENGINE=[^; ]+/i'         => '',
        '/DEFAULT CHARSET=[^; ]+/i' => '',
        '/AUTO_INCREMENT=\d+/i'    => '',
        
        // 2. Правим типы
        '/int\(\d+\) NOT NULL AUTO_INCREMENT/i' => 'SERIAL PRIMARY KEY',
        '/int\(\d+\)/i' => 'integer',
        '/longtext/i'   => 'text',
        '/mediumtext/i' => 'text',
        '/tinyint\(1\)/i' => 'smallint',
        '/\s+datetime/i' => ' timestamp', 
        
        // 3. Убираем обратные кавычки
        '/`/' => '', 
    ];

    $sql = preg_replace(array_keys($replacements), array_values($replacements), $sql);
    
    // ВАЖНО: Если вы используете mb_strtolower($sql), убедитесь, что в базе 
    // названия колонок действительно в нижнем регистре. 
    // Битрикс (D7) через PgsqlConnection умеет работать с нижним регистром.
    //return mb_strtolower($sql);
    return $sql;
    }

    public static function extractIndexes(string $sql, string $tableName): array
    {
        $indexes = [];
        // Берем содержимое из комментариев /* INDEX: ... */
        if (preg_match_all('/\/\* INDEX: (.*?) \*\//i', $sql, $matches)) {
            foreach ($matches[1] as $idxQuery) {
                $indexes[] = str_replace('__TABLE__', mb_strtolower($tableName), $idxQuery);
            }
        }
        return $indexes;
    }
}
