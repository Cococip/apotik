<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\DashboardService;
use App\Services\ExpiryStatusService;

class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('dashboard.index', [
            'title' => 'Dashboard',
            'stats' => DashboardService::stats(),
            'lowStock' => DashboardService::lowStockWidget(),
            'expiringSoon' => DashboardService::expiringSoonWidget(),
            'recentTransactions' => DashboardService::recentTransactions(),
        ]);
    }
}
