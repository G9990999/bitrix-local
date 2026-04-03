<?php

namespace SnipeIT\Module\Services;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Simple PDO database service (singleton).
 * Replaces Laravel's Eloquent/DB facade for this standalone module.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = self::loadConfig();

        $dsn = match ($config['driver']) {
            'mysql'  => sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'] ?? 3306,
                $config['database'],
                $config['charset'] ?? 'utf8mb4'
            ),
            'pgsql'  => sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $config['host'],
                $config['port'] ?? 5432,
                $config['database']
            ),
            'sqlite' => 'sqlite:' . $config['database'],
            default  => throw new RuntimeException('Unsupported database driver: ' . $config['driver']),
        };

        try {
            self::$instance = new PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }

        return self::$instance;
    }

    private static function loadConfig(): array
    {
        $path = __DIR__ . '/../config/database.php';
        if (!file_exists($path)) {
            throw new RuntimeException('Database config not found. Copy database.example.php to database.php.');
        }
        return require $path;
    }

    /** Execute a query and return all rows */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Execute a query and return the first row */
    public static function fetchOne(string $sql, array $params = []): array|false
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /** Execute a statement (INSERT/UPDATE/DELETE) and return affected rows */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Execute INSERT and return last insert id */
    public static function insert(string $sql, array $params = []): string|false
    {
        self::execute($sql, $params);
        return self::connection()->lastInsertId();
    }
}
