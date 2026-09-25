<?php

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use Dotenv\Dotenv;

if (PHP_SAPI === 'cli-server') {
    $requestedPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $filePath = __DIR__ . $requestedPath;
    if ($requestedPath !== '/' && is_file($filePath)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/Helpers/functions.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Asia/Jakarta');

error_reporting(E_ALL);
ini_set('display_errors', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN) ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/storage/logs/php-error.log');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=()');

Session::start();

$router = new Router();
require dirname(__DIR__) . '/routes/web.php';
require dirname(__DIR__) . '/routes/api.php';

$request = new Request();

try {
    $router->dispatch($request);
} catch (\Throwable $e) {
    \App\Core\Logger::error('[UNCAUGHT] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    if (str_starts_with($request->uri(), '/api/')) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN) ? $e->getMessage() : 'Terjadi kesalahan. Silakan coba lagi.',
            'data' => null,
            'errors' => [],
        ]);
        exit;
    }

    http_response_code(500);
    if (filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
        echo '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
    } else {
        require dirname(__DIR__) . '/app/Views/errors/500.php';
    }
}
