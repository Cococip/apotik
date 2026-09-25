<?php

namespace App\Core;

class Logger
{
    private static function path(): string
    {
        return dirname(__DIR__, 2) . '/storage/logs/app-' . date('Y-m-d') . '.log';
    }

    private static function write(string $level, string $message): void
    {
        $line = sprintf('[%s] %s: %s%s', date('Y-m-d H:i:s'), $level, $message, PHP_EOL);
        @file_put_contents(self::path(), $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message): void
    {
        self::write('ERROR', $message);
    }

    public static function warning(string $message): void
    {
        self::write('WARNING', $message);
    }

    public static function info(string $message): void
    {
        self::write('INFO', $message);
    }
}
