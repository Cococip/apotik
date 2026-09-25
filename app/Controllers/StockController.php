<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Category;
use App\Services\MedicineService;
use App\Services\StockLedgerService;

class StockController extends Controller
{
    public function index(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id') ? (int) $request->query('category_id') : null;

        $result = MedicineService::paginateList($page, 15, $search ?: null, $categoryId, 'active');

        $this->view('stock.index', [
            'title' => 'Stok',
            'rows' => $result['data'],
            'pagination' => $result,
            'search' => $search,
            'categoryId' => $categoryId,
            'categories' => Category::all('name'),
        ]);
    }

    public function card(Request $request, string $id): void
    {
        $medicine = MedicineService::findDetailed((int) $id);
        if (!$medicine) {
            Session::flash('error', 'Obat tidak ditemukan.');
            $this->redirect('/stock');
            return;
        }

        $this->view('stock.card', [
            'title' => 'Kartu Stok — ' . $medicine['name'],
            'medicine' => $medicine,
            'movements' => StockLedgerService::ledgerFor((int) $id, 300),
        ]);
    }
}
