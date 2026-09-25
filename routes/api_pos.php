<?php

use App\Controllers\Api\PosApiController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/pos/search', [PosApiController::class, 'search'], [[PermissionMiddleware::class, 'sales.create']]);
$router->get('/api/pos/catalog', [PosApiController::class, 'catalog'], [[PermissionMiddleware::class, 'sales.create']]);
$router->get('/api/pos/barcode/{code}', [PosApiController::class, 'barcodeLookup'], [[PermissionMiddleware::class, 'sales.create']]);
$router->post('/api/pos/checkout', [PosApiController::class, 'checkout'], [[PermissionMiddleware::class, 'sales.create']]);
