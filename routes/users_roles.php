<?php

use App\Controllers\RoleController;
use App\Controllers\UserController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/users', [UserController::class, 'index'], [[PermissionMiddleware::class, 'user.view']]);
$router->post('/users', [UserController::class, 'store'], [[PermissionMiddleware::class, 'user.create']]);
$router->put('/users/{id}', [UserController::class, 'update'], [[PermissionMiddleware::class, 'user.update']]);
$router->delete('/users/{id}', [UserController::class, 'destroy'], [[PermissionMiddleware::class, 'user.delete']]);

$router->get('/roles', [RoleController::class, 'index'], [[PermissionMiddleware::class, 'role.manage']]);
$router->post('/roles', [RoleController::class, 'store'], [[PermissionMiddleware::class, 'role.manage']]);
$router->get('/roles/{id}/edit', [RoleController::class, 'edit'], [[PermissionMiddleware::class, 'role.manage']]);
$router->put('/roles/{id}', [RoleController::class, 'update'], [[PermissionMiddleware::class, 'role.manage']]);
$router->delete('/roles/{id}', [RoleController::class, 'destroy'], [[PermissionMiddleware::class, 'role.manage']]);
