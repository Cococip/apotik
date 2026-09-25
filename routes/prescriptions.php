<?php

use App\Controllers\PrescriptionController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/prescriptions', [PrescriptionController::class, 'index'], [[PermissionMiddleware::class, 'prescription.view']]);
$router->get('/prescriptions/create', [PrescriptionController::class, 'create'], [[PermissionMiddleware::class, 'prescription.create']]);
$router->post('/prescriptions', [PrescriptionController::class, 'store'], [[PermissionMiddleware::class, 'prescription.create']]);
$router->get('/prescriptions/{id}', [PrescriptionController::class, 'show'], [[PermissionMiddleware::class, 'prescription.view']]);
$router->post('/prescriptions/{id}/transition', [PrescriptionController::class, 'transition'], [[PermissionMiddleware::class, 'prescription.view']]);
