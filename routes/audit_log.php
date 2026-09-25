<?php

use App\Controllers\AuditLogController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/audit-log', [AuditLogController::class, 'index'], [[PermissionMiddleware::class, 'audit.view']]);
