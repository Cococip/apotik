<?php

use App\Controllers\DashboardController;
use App\Middleware\AuthMiddleware;

/** @var App\Core\Router $router */

$router->get('/', [DashboardController::class, 'index'], [AuthMiddleware::class]);
