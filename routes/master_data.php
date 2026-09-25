<?php

use App\Controllers\CategoryController;
use App\Controllers\DoctorController;
use App\Controllers\ManufacturerController;
use App\Controllers\MedicineGroupController;
use App\Controllers\MedicineTypeController;
use App\Controllers\PatientController;
use App\Controllers\RackController;
use App\Controllers\SupplierController;
use App\Controllers\UnitController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

foreach ([
    'categories' => CategoryController::class,
    'medicine-types' => MedicineTypeController::class,
    'medicine-groups' => MedicineGroupController::class,
    'units' => UnitController::class,
    'manufacturers' => ManufacturerController::class,
    'racks' => RackController::class,
    'doctors' => DoctorController::class,
    'patients' => PatientController::class,
    'suppliers' => SupplierController::class,
] as $prefix => $controller) {
    $router->get('/' . $prefix, [$controller, 'index'], [[PermissionMiddleware::class, 'master.manage']]);
    $router->post('/' . $prefix, [$controller, 'store'], [[PermissionMiddleware::class, 'master.manage']]);
    $router->put('/' . $prefix . '/{id}', [$controller, 'update'], [[PermissionMiddleware::class, 'master.manage']]);
    $router->delete('/' . $prefix . '/{id}', [$controller, 'destroy'], [[PermissionMiddleware::class, 'master.manage']]);
}
