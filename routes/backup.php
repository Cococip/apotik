<?php

use App\Controllers\BackupController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/backup', [BackupController::class, 'index'], [[PermissionMiddleware::class, 'backup.manage']]);
$router->post('/backup/create', [BackupController::class, 'create'], [[PermissionMiddleware::class, 'backup.manage']]);
$router->get('/backup/download/{filename}', [BackupController::class, 'download'], [[PermissionMiddleware::class, 'backup.manage']]);
