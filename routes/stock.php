<?php

use App\Controllers\BatchController;
use App\Controllers\StockAdjustmentController;
use App\Controllers\StockController;
use App\Controllers\StockOpnameController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/stock', [StockController::class, 'index'], [[PermissionMiddleware::class, 'inventory.view']]);
$router->get('/stock/card/{id}', [StockController::class, 'card'], [[PermissionMiddleware::class, 'inventory.view']]);

$router->get('/stock/batches', [BatchController::class, 'index'], [[PermissionMiddleware::class, 'inventory.view']]);
$router->post('/batches/{id}/status', [BatchController::class, 'updateStatus'], [[PermissionMiddleware::class, 'medicine.batch.manage']]);

$router->get('/stock/opname', [StockOpnameController::class, 'index'], [[PermissionMiddleware::class, 'inventory.opname']]);
$router->get('/stock/opname/create', [StockOpnameController::class, 'create'], [[PermissionMiddleware::class, 'inventory.opname']]);
$router->post('/stock/opname', [StockOpnameController::class, 'store'], [[PermissionMiddleware::class, 'inventory.opname']]);
$router->get('/stock/opname/{id}', [StockOpnameController::class, 'show'], [[PermissionMiddleware::class, 'inventory.opname']]);

$router->get('/stock/adjustment', [StockAdjustmentController::class, 'index'], [[PermissionMiddleware::class, 'inventory.adjust']]);
$router->post('/stock/adjustment', [StockAdjustmentController::class, 'store'], [[PermissionMiddleware::class, 'inventory.adjust']]);
$router->get('/api/medicines/{id}/batches', [StockAdjustmentController::class, 'batchesForMedicine'], [[PermissionMiddleware::class, 'inventory.adjust']]);
