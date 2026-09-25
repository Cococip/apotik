<?php

use App\Controllers\PurchaseController;
use App\Controllers\PurchaseOrderController;
use App\Controllers\PurchaseReturnController;
use App\Controllers\SupplierPaymentController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/purchase-orders', [PurchaseOrderController::class, 'index'], [[PermissionMiddleware::class, 'purchase.view']]);
$router->get('/purchase-orders/create', [PurchaseOrderController::class, 'create'], [[PermissionMiddleware::class, 'purchase.create']]);
$router->post('/purchase-orders', [PurchaseOrderController::class, 'store'], [[PermissionMiddleware::class, 'purchase.create']]);
$router->get('/purchase-orders/{id}', [PurchaseOrderController::class, 'show'], [[PermissionMiddleware::class, 'purchase.view']]);
$router->post('/purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'], [[PermissionMiddleware::class, 'purchase.approve']]);

$router->get('/purchases', [PurchaseController::class, 'index'], [[PermissionMiddleware::class, 'purchase.view']]);
$router->get('/purchases/create', [PurchaseController::class, 'create'], [[PermissionMiddleware::class, 'purchase.create']]);
$router->post('/purchases', [PurchaseController::class, 'store'], [[PermissionMiddleware::class, 'purchase.create']]);
$router->get('/purchases/{id}', [PurchaseController::class, 'show'], [[PermissionMiddleware::class, 'purchase.view']]);

$router->get('/purchase-returns', [PurchaseReturnController::class, 'index'], [[PermissionMiddleware::class, 'purchase.view']]);
$router->get('/purchase-returns/create', [PurchaseReturnController::class, 'create'], [[PermissionMiddleware::class, 'purchase.create']]);
$router->post('/purchase-returns', [PurchaseReturnController::class, 'store'], [[PermissionMiddleware::class, 'purchase.create']]);

$router->get('/supplier-payments', [SupplierPaymentController::class, 'index'], [[PermissionMiddleware::class, 'purchase.view']]);
$router->post('/supplier-payments', [SupplierPaymentController::class, 'store'], [[PermissionMiddleware::class, 'purchase.create']]);
$router->get('/api/purchases/outstanding/{supplierId}', [SupplierPaymentController::class, 'outstandingForSupplier'], [[PermissionMiddleware::class, 'purchase.view']]);
