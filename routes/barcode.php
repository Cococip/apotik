<?php

use App\Controllers\BarcodeController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/barcode', [BarcodeController::class, 'index'], [[PermissionMiddleware::class, 'barcode.view']]);
$router->post('/barcode/generate/{id}', [BarcodeController::class, 'generate'], [[PermissionMiddleware::class, 'barcode.generate']]);
$router->post('/barcode/generate-bulk', [BarcodeController::class, 'generateBulk'], [[PermissionMiddleware::class, 'barcode.generate']]);
$router->get('/barcode/print', [BarcodeController::class, 'printPage'], [[PermissionMiddleware::class, 'barcode.print']]);
