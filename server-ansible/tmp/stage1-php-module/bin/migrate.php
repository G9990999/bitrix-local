#!/usr/bin/env php
<?php

/**
 * Migration runner for Snipe-IT Standalone PHP Module.
 * Usage: php bin/migrate.php
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use SnipeIT\Module\Services\Database;

$migrationsDir = dirname(__DIR__) . '/src/Migrations';

$files = glob($migrationsDir . '/*.sql');
sort($files);

$pdo = Database::connection();

// Ensure migrations table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

foreach ($files as $file) {
    $name = basename($file, '.sql');

    $exists = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = ?');
    $exists->execute([$name]);
    if ((int) $exists->fetchColumn() > 0) {
        echo "  skip   $name (already applied)\n";
        continue;
    }

    echo "  apply  $name ... ";
    $sql = file_get_contents($file);

    try {
        $pdo->exec($sql);
        $pdo->prepare('INSERT IGNORE INTO migrations (migration) VALUES (?)')->execute([$name]);
        echo "OK\n";
    } catch (Throwable $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\nMigrations complete.\n";
