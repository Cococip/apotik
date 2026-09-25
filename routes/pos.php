<?php

use App\Controllers\PosController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/pos', [PosController::class, 'index'], [[PermissionMiddleware::class, 'sales.create']]);
$router->get('/pos/receipt/{id}', [PosController::class, 'receipt'], [[PermissionMiddleware::class, 'sales.view']]);
