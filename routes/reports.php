<?php

use App\Controllers\ReportController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/reports', [ReportController::class, 'index'], [[PermissionMiddleware::class, 'report.view']]);
$router->get('/reports/sales', [ReportController::class, 'sales'], [[PermissionMiddleware::class, 'report.view']]);
$router->get('/reports/purchases', [ReportController::class, 'purchases'], [[PermissionMiddleware::class, 'report.view']]);
$router->get('/reports/stock', [ReportController::class, 'stock'], [[PermissionMiddleware::class, 'report.view']]);
$router->get('/reports/expired', [ReportController::class, 'expired'], [[PermissionMiddleware::class, 'report.view']]);
$router->get('/reports/returns', [ReportController::class, 'returns'], [[PermissionMiddleware::class, 'report.view']]);
$router->get('/reports/profit', [ReportController::class, 'profit'], [[PermissionMiddleware::class, 'report.view']]);
$router->get('/reports/debt', [ReportController::class, 'debt'], [[PermissionMiddleware::class, 'report.view']]);
$router->get('/reports/finance', [ReportController::class, 'finance'], [[PermissionMiddleware::class, 'report.view']]);
