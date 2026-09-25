<?php

use App\Controllers\Api\NotificationApiController;
use App\Middleware\AuthMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/notifications', [NotificationApiController::class, 'index'], [AuthMiddleware::class]);
$router->post('/api/notifications/{id}/read', [NotificationApiController::class, 'markRead'], [AuthMiddleware::class]);
