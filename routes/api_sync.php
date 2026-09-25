<?php

use App\Controllers\Api\SyncApiController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->post('/api/sync/push', [SyncApiController::class, 'push'], [[PermissionMiddleware::class, 'sales.create']]);
