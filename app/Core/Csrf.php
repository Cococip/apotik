<?php

namespace App\Core;

class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::KEY)) {
            Session::set(self::KEY, bin2hex(random_bytes(32)));
        }

        return Session::get(self::KEY);
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    public static function verify(?string $token): bool
    {
        if (!$token || !Session::has(self::KEY)) {
            return false;
        }

        return hash_equals(Session::get(self::KEY), $token);
    }
}
