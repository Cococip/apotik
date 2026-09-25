<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__, 2) . '/config/database.php';

            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                Logger::error('DB connection failed: ' . $e->getMessage());
                throw new RuntimeException('Koneksi database gagal.');
            }
        }

        return self::$instance;
    }

    public static function beginTransaction(): bool
    {
        return self::connection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::connection()->commit();
    }

    public static function rollBack(): bool
    {
        if (self::connection()->inTransaction()) {
            return self::connection()->rollBack();
        }

        return false;
    }

    public static function inTransaction(): bool
    {
        return self::connection()->inTransaction();
    }
}
