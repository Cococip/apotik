<?php

use App\Core\Csrf;
use App\Core\Session;
use App\Services\SettingsService;

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = [];
        [$file, $path] = array_pad(explode('.', $key, 2), 2, null);

        if (!isset($cache[$file])) {
            $configPath = dirname(__DIR__, 2) . '/config/' . $file . '.php';
            $cache[$file] = is_file($configPath) ? require $configPath : [];
        }

        if ($path === null) {
            return $cache[$file];
        }

        return $cache[$file][$path] ?? $default;
    }
}

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return SettingsService::get($key, $default);
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        return rtrim(config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = Session::getFlash('_old_input', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('flash_old')) {
    function flash_old(array $data): void
    {
        Session::flash('_old_input', $data);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $default = null): ?string
    {
        return Session::getFlash($key, $default);
    }
}

if (!function_exists('field_error')) {
    function field_error(string $field): ?string
    {
        static $errors = null;
        if ($errors === null) {
            $errors = Session::getFlash('_errors', []);
        }
        return $errors[$field][0] ?? null;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token_value')) {
    function csrf_token_value(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, ?string $format = null): string
    {
        if (!$date) {
            return '-';
        }
        $timestamp = strtotime($date);
        return $timestamp ? date($format ?? config('app.date_format', 'd/m/Y'), $timestamp) : '-';
    }
}

if (!function_exists('format_date_id')) {
    function format_date_id(?string $date): string
    {
        if (!$date) {
            return '-';
        }
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return '-';
        }
        return $days[(int) date('w', $timestamp)] . ', ' . date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $date): string
    {
        return format_date($date, 'd/m/Y H:i');
    }
}

if (!function_exists('format_money')) {
    function format_money(float|int|string|null $amount): string
    {
        return 'Rp ' . number_format((float) ($amount ?? 0), 0, ',', '.');
    }
}

if (!function_exists('days_until')) {
    function days_until(?string $date): ?int
    {
        if (!$date) {
            return null;
        }
        $target = strtotime($date . ' 00:00:00');
        $today = strtotime(date('Y-m-d') . ' 00:00:00');
        return (int) round(($target - $today) / 86400);
    }
}

if (!function_exists('active_menu')) {
    function active_menu(string $path, bool $exact = false): string
    {
        $uri = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';
        if ($exact) {
            return $uri === $path ? 'is-active' : '';
        }
        return ($uri === $path || str_starts_with($uri, $path . '/')) ? 'is-active' : '';
    }
}

if (!function_exists('render_pagination')) {
    /**
     * @param array $pagination Shape from Model::paginate(): total, page, per_page, total_pages
     */
    function render_pagination(array $pagination, string $baseUrl, array $extraParams = []): void
    {
        $page = $pagination['page'];
        $totalPages = $pagination['total_pages'];
        $total = $pagination['total'];
        $perPage = $pagination['per_page'];
        $start = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $end = min($total, $page * $perPage);

        $buildUrl = function (int $p) use ($baseUrl, $extraParams) {
            $params = array_merge($extraParams, ['page' => $p]);
            return $baseUrl . '?' . http_build_query($params);
        };
        ?>
        <div class="pagination-bar">
            <span>Menampilkan <?= $start ?>–<?= $end ?> dari <?= $total ?> data</span>
            <?php if ($totalPages > 1): ?>
            <div class="pagination-links">
                <?php if ($page > 1): ?><a href="<?= e($buildUrl($page - 1)) ?>">&laquo;</a><?php endif; ?>
                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <?php if ($p === $page): ?>
                        <span class="is-current"><?= $p ?></span>
                    <?php else: ?>
                        <a href="<?= e($buildUrl($p)) ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?><a href="<?= e($buildUrl($page + 1)) ?>">&raquo;</a><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('random_token')) {
    function random_token(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }
}
