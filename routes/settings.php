<?php

use App\Controllers\SettingsController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/settings/pharmacy', [SettingsController::class, 'pharmacy'], [[PermissionMiddleware::class, 'settings.manage']]);
$router->post('/settings/pharmacy', [SettingsController::class, 'updatePharmacy'], [[PermissionMiddleware::class, 'settings.manage']]);

$router->get('/settings/system', [SettingsController::class, 'system'], [[PermissionMiddleware::class, 'settings.manage']]);
$router->post('/settings/system', [SettingsController::class, 'updateSystem'], [[PermissionMiddleware::class, 'settings.manage']]);

$router->get('/settings/inventory', [SettingsController::class, 'inventory'], [[PermissionMiddleware::class, 'settings.manage']]);
$router->post('/settings/inventory', [SettingsController::class, 'updateInventory'], [[PermissionMiddleware::class, 'settings.manage']]);

$router->get('/settings/printer', [SettingsController::class, 'printer'], [[PermissionMiddleware::class, 'settings.manage']]);
$router->post('/settings/printer', [SettingsController::class, 'updatePrinter'], [[PermissionMiddleware::class, 'settings.manage']]);
