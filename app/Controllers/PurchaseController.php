<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Medicine;
use App\Models\Supplier;
use App\Services\CheckoutException;
use App\Services\PurchaseService;

class PurchaseController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::connection();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $total = (int) $db->query('SELECT COUNT(*) AS c FROM purchases')->fetch()['c'];
        $totalPages = max(1, (int) ceil($total / $perPage));

        $stmt = $db->query(
            "SELECT p.*, s.name AS supplier_name, u.full_name AS user_name,
                    (SELECT COALESCE(SUM(sp.amount),0) FROM supplier_payments sp WHERE sp.purchase_id = p.id) AS paid_total
             FROM purchases p
             JOIN suppliers s ON s.id = p.supplier_id
             LEFT JOIN users u ON u.id = p.user_id
             ORDER BY p.id DESC LIMIT {$perPage} OFFSET {$offset}"
        );

        $this->view('purchases.index', [
            'title' => 'Pembelian',
            'rows' => $stmt->fetchAll(),
            'pagination' => ['total' => $total, 'page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages],
        ]);
    }

    public function create(Request $request): void
    {
        $poId = $request->query('po_id');
        $po = null;
        $poDetails = [];

        if ($poId) {
            $db = Database::connection();
            $stmt = $db->prepare('SELECT po.*, s.name AS supplier_name FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id WHERE po.id = :id');
            $stmt->execute(['id' => $poId]);
            $po = $stmt->fetch();
            if ($po) {
                $d = $db->prepare('SELECT d.*, m.name AS medicine_name FROM purchase_order_details d JOIN medicines m ON m.id=d.medicine_id WHERE d.purchase_order_id = :id');
                $d->execute(['id' => $poId]);
                $poDetails = $d->fetchAll();
            }
        }

        $this->view('purchases.form', [
            'title' => 'Input Pembelian',
            'suppliers' => Supplier::all('name'),
            'medicines' => Medicine::where(['status' => 'active']),
            'po' => $po,
            'poDetails' => $poDetails,
            'today' => date('Y-m-d'),
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        if (empty($input['supplier_id']) || empty($input['purchase_date'])) {
            Session::flash('error', 'Supplier dan tanggal pembelian wajib diisi.');
            $this->redirect('/purchases/create');
            return;
        }

        $medicineIds = (array) $request->input('medicine_id', []);
        $batchNumbers = (array) $request->input('batch_number', []);
        $expiredDates = (array) $request->input('expired_date', []);
        $productionDates = (array) $request->input('production_date', []);
        $qtys = (array) $request->input('qty', []);
        $purchasePrices = (array) $request->input('purchase_price', []);
        $sellingPrices = (array) $request->input('selling_price', []);
        $discounts = (array) $request->input('discount', []);

        $lines = [];
        foreach ($medicineIds as $i => $medicineId) {
            if (!$medicineId || empty($qtys[$i]) || (float) $qtys[$i] <= 0 || empty($batchNumbers[$i]) || empty($expiredDates[$i])) {
                continue;
            }
            $lines[] = [
                'medicine_id' => $medicineId,
                'batch_number' => $batchNumbers[$i],
                'expired_date' => $expiredDates[$i],
                'production_date' => $productionDates[$i] ?? null,
                'qty' => $qtys[$i],
                'purchase_price' => $purchasePrices[$i] ?? 0,
                'selling_price' => $sellingPrices[$i] ?? 0,
                'discount' => $discounts[$i] ?? 0,
            ];
        }

        $paidNow = (float) ($input['paid_now'] ?? 0);

        try {
            $purchaseId = PurchaseService::create([
                'supplier_id' => $input['supplier_id'],
                'supplier_invoice_number' => $input['supplier_invoice_number'] ?? null,
                'purchase_date' => $input['purchase_date'],
                'due_date' => $input['due_date'] ?? null,
                'notes' => $input['notes'] ?? null,
                'purchase_order_id' => $input['purchase_order_id'] ?? null,
            ], $lines, $paidNow, Auth::id());
        } catch (CheckoutException $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/purchases/create');
            return;
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Purchase create failed: ' . $e->getMessage());
            Session::flash('error', 'Gagal menyimpan pembelian. Periksa kembali data batch (nomor batch mungkin duplikat).');
            $this->redirect('/purchases/create');
            return;
        }

        Session::flash('success', 'Pembelian berhasil disimpan dan stok telah bertambah.');
        $this->redirect('/purchases/' . $purchaseId);
    }

    public function show(Request $request, string $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT p.*, s.name AS supplier_name, u.full_name AS user_name,
                    (SELECT COALESCE(SUM(sp.amount),0) FROM supplier_payments sp WHERE sp.purchase_id = p.id) AS paid_total
             FROM purchases p JOIN suppliers s ON s.id = p.supplier_id LEFT JOIN users u ON u.id = p.user_id WHERE p.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $purchase = $stmt->fetch();

        if (!$purchase) {
            Session::flash('error', 'Pembelian tidak ditemukan.');
            $this->redirect('/purchases');
            return;
        }

        $detailStmt = $db->prepare('SELECT pd.*, m.name AS medicine_name FROM purchase_details pd JOIN medicines m ON m.id = pd.medicine_id WHERE pd.purchase_id = :id');
        $detailStmt->execute(['id' => $id]);

        $this->view('purchases.show', ['title' => 'Pembelian ' . $purchase['purchase_number'], 'purchase' => $purchase, 'details' => $detailStmt->fetchAll()]);
    }
}
