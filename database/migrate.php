<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$config = require dirname(__DIR__) . '/config/database.php';

$dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s', $config['driver'], $config['host'], $config['port'], $config['database'], $config['charset']);
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
]);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(191) NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        echo "Skipping (already applied): {$name}\n";
        continue;
    }

    echo "Running migration: {$name}\n";
    $sql = file_get_contents($file);

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    while ($stmt->nextRowset()) {
        // drain multi-statement result sets
    }

    $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:m)')->execute(['m' => $name]);
}

echo "Migration completed successfully.\n";
