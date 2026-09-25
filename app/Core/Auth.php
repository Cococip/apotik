<?php

namespace App\Core;

class Auth
{
    private static ?array $userCache = null;
    private static ?array $permissionsCache = null;

    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public static function isLockedOut(string $ip): ?int
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM login_throttle WHERE ip_address = :ip LIMIT 1');
        $stmt->execute(['ip' => $ip]);
        $row = $stmt->fetch();

        if (!$row || !$row['locked_until']) {
            return null;
        }

        $remaining = strtotime($row['locked_until']) - time();
        return $remaining > 0 ? (int) ceil($remaining / 60) : null;
    }

    private static function registerFailedAttempt(string $ip): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM login_throttle WHERE ip_address = :ip LIMIT 1');
        $stmt->execute(['ip' => $ip]);
        $row = $stmt->fetch();

        if (!$row) {
            $db->prepare('INSERT INTO login_throttle (ip_address, attempt_count, last_attempt_at) VALUES (:ip, 1, NOW())')
                ->execute(['ip' => $ip]);
            return;
        }

        $count = (int) $row['attempt_count'] + 1;
        $lockedUntil = null;
        if ($count >= self::MAX_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + self::LOCKOUT_MINUTES * 60);
            $count = 0;
        }

        $db->prepare('UPDATE login_throttle SET attempt_count = :count, last_attempt_at = NOW(), locked_until = :locked WHERE ip_address = :ip')
            ->execute(['count' => $count, 'locked' => $lockedUntil, 'ip' => $ip]);
    }

    private static function clearAttempts(string $ip): void
    {
        Database::connection()
            ->prepare('DELETE FROM login_throttle WHERE ip_address = :ip')
            ->execute(['ip' => $ip]);
    }

    public static function attempt(string $username, string $password, string $ip = '0.0.0.0'): array
    {
        if (self::isLockedOut($ip) !== null) {
            $minutes = self::isLockedOut($ip);
            return ['success' => false, 'message' => "Terlalu banyak percobaan gagal. Coba lagi dalam {$minutes} menit."];
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT u.*, r.name AS role_name, r.slug AS role_slug
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE (u.username = :username OR u.email = :email) AND u.status = "active" AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['username' => $username, 'email' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            self::registerFailedAttempt($ip);
            return ['success' => false, 'message' => 'Username atau password salah.'];
        }

        self::clearAttempts($ip);
        Session::regenerate(true);

        Session::set('user_id', (int) $user['id']);
        Session::set('role_id', (int) $user['role_id']);
        Session::set('role_slug', $user['role_slug']);
        Session::set('role_name', $user['role_name']);
        Session::set('full_name', $user['full_name']);
        Session::set('logged_in_at', time());

        $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $user['id']]);

        self::$userCache = null;
        self::$permissionsCache = null;

        return ['success' => true, 'user' => $user];
    }

    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function roleSlug(): ?string
    {
        return Session::get('role_slug');
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        if (self::$userCache !== null) {
            return self::$userCache;
        }

        $stmt = Database::connection()->prepare(
            'SELECT u.id, u.username, u.email, u.full_name, u.phone, u.photo, u.status, u.role_id,
                    r.name AS role_name, r.slug AS role_slug
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => self::id()]);
        $user = $stmt->fetch();

        self::$userCache = $user ?: null;

        return self::$userCache;
    }

    public static function isSuperAdmin(): bool
    {
        return self::roleSlug() === 'super_admin';
    }

    public static function can(string $permissionSlug): bool
    {
        if (!self::check()) {
            return false;
        }

        if (self::isSuperAdmin()) {
            return true;
        }

        if (self::$permissionsCache === null) {
            $stmt = Database::connection()->prepare(
                'SELECT p.slug FROM permissions p
                 JOIN role_permissions rp ON rp.permission_id = p.id
                 WHERE rp.role_id = :role_id'
            );
            $stmt->execute(['role_id' => Session::get('role_id')]);
            self::$permissionsCache = array_column($stmt->fetchAll(), 'slug');
        }

        return in_array($permissionSlug, self::$permissionsCache, true);
    }

    public static function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array(self::roleSlug(), $roles, true);
    }

    public static function logout(): void
    {
        self::$userCache = null;
        self::$permissionsCache = null;
        Session::destroy();
    }
}
