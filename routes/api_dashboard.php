<?php

use App\Controllers\Api\DashboardApiController;
use App\Middleware\AuthMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/dashboard/charts', [DashboardApiController::class, 'charts'], [AuthMiddleware::class]);
