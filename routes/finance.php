<?php

use App\Controllers\FinanceController;
use App\Middleware\PermissionMiddleware;

/** @var App\Core\Router $router */

$router->get('/finance/cash-in', [FinanceController::class, 'cashIn'], [[PermissionMiddleware::class, 'finance.view']]);
$router->post('/finance/cash-in', [FinanceController::class, 'storeCashIn'], [[PermissionMiddleware::class, 'finance.manage']]);
$router->get('/finance/cash-out', [FinanceController::class, 'cashOut'], [[PermissionMiddleware::class, 'finance.view']]);
$router->post('/finance/cash-out', [FinanceController::class, 'storeCashOut'], [[PermissionMiddleware::class, 'finance.manage']]);
$router->get('/finance/expenses', [FinanceController::class, 'expenses'], [[PermissionMiddleware::class, 'finance.view']]);
$router->post('/finance/expenses', [FinanceController::class, 'storeExpense'], [[PermissionMiddleware::class, 'finance.manage']]);
$router->get('/finance/piutang', [FinanceController::class, 'piutang'], [[PermissionMiddleware::class, 'finance.view']]);
$router->post('/finance/piutang/pay', [FinanceController::class, 'payPiutang'], [[PermissionMiddleware::class, 'finance.manage']]);
$router->get('/finance/recap', [FinanceController::class, 'recap'], [[PermissionMiddleware::class, 'finance.view']]);
