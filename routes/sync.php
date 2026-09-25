<?php

use App\Controllers\SyncController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/sync', [SyncController::class, 'index'], [[PermissionMiddleware::class, 'sync.manage']]);
$router->post('/sync/{id}/retry', [SyncController::class, 'retry'], [[PermissionMiddleware::class, 'sync.manage']]);
