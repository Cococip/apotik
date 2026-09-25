<?php

namespace App\Services;

use App\Core\Database;

class SettingsService
{
    private static ?array $cache = null;

    private static function load(): array
    {
        if (self::$cache === null) {
            $stmt = Database::connection()->query('SELECT `key`, `value` FROM settings');
            self::$cache = [];
            foreach ($stmt->fetchAll() as $row) {
                self::$cache[$row['key']] = $row['value'];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $values = self::load();
        return $values[$key] ?? $default;
    }

    public static function all(): array
    {
        return self::load();
    }

    public static function set(string $key, string $value, string $group = 'general'): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO settings (`key`, `value`, `group`, created_at, updated_at) VALUES (:key, :value, :group, NOW(), NOW())
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()'
        );
        $stmt->execute(['key' => $key, 'value' => $value, 'group' => $group]);
        self::$cache = null;
    }

    public static function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            self::set($key, (string) $value, $group);
        }
    }
}
