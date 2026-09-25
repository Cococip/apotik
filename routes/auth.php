<?php

use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

/** @var App\Core\Router $router */

$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
