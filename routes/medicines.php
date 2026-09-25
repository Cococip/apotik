<?php

use App\Controllers\BatchController;
use App\Controllers\MedicineController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/medicines', [MedicineController::class, 'index'], [[PermissionMiddleware::class, 'medicine.view']]);
$router->get('/medicines/create', [MedicineController::class, 'create'], [[PermissionMiddleware::class, 'medicine.create']]);
$router->post('/medicines', [MedicineController::class, 'store'], [[PermissionMiddleware::class, 'medicine.create']]);
$router->get('/medicines/{id}/edit', [MedicineController::class, 'edit'], [[PermissionMiddleware::class, 'medicine.view']]);
$router->put('/medicines/{id}', [MedicineController::class, 'update'], [[PermissionMiddleware::class, 'medicine.update']]);
$router->delete('/medicines/{id}', [MedicineController::class, 'destroy'], [[PermissionMiddleware::class, 'medicine.delete']]);

$router->post('/medicines/{id}/batches', [BatchController::class, 'store'], [[PermissionMiddleware::class, 'medicine.batch.manage']]);
$router->post('/medicines/{id}/unit-conversions', [MedicineController::class, 'storeUnitConversion'], [[PermissionMiddleware::class, 'medicine.update']]);
$router->delete('/unit-conversions/{id}', [MedicineController::class, 'destroyUnitConversion'], [[PermissionMiddleware::class, 'medicine.update']]);
