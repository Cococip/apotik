<?php
// Used only when running via PHP built-in server:
// php -S 127.0.0.1:6100 -t public public/router.php
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $path;
if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}
require __DIR__ . '/index.php';
