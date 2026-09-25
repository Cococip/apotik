<?php

use App\Controllers\SaleReturnController;
use App\Controllers\SalesController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/sales', [SalesController::class, 'index'], [[PermissionMiddleware::class, 'sales.view']]);
$router->get('/sales/{id}', [SalesController::class, 'show'], [[PermissionMiddleware::class, 'sales.view']]);

$router->get('/sale-returns', [SaleReturnController::class, 'index'], [[PermissionMiddleware::class, 'sales.refund']]);
$router->get('/sale-returns/create', [SaleReturnController::class, 'create'], [[PermissionMiddleware::class, 'sales.refund']]);
$router->post('/sale-returns', [SaleReturnController::class, 'store'], [[PermissionMiddleware::class, 'sales.refund']]);
