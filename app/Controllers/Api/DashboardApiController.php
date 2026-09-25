<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Services\DashboardService;

class DashboardApiController extends Controller
{
    public function charts(Request $request): void
    {
        $this->json([
            'success' => true,
            'message' => 'OK',
            'errors' => [],
            'data' => [
                'sales_7d' => DashboardService::salesSeries(7),
                'sales_30d' => DashboardService::salesSeries(30),
                'top_products' => DashboardService::topProducts(),
                'top_categories' => DashboardService::topCategories(),
            ],
        ]);
    }
}
