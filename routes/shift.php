<?php

use App\Controllers\CashShiftController;
use App\Middleware\AuthMiddleware;

/** @var App\Core\Router $router */

$router->get('/shift', [CashShiftController::class, 'index'], [AuthMiddleware::class]);
$router->post('/shift/open', [CashShiftController::class, 'open'], [AuthMiddleware::class]);
$router->post('/shift/{id}/close', [CashShiftController::class, 'close'], [AuthMiddleware::class]);
