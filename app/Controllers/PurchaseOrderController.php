<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Medicine;
use App\Models\Supplier;
use App\Services\AuditLogger;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT po.*, s.name AS supplier_name, u.full_name AS user_name,
                    (SELECT COUNT(*) FROM purchase_order_details d WHERE d.purchase_order_id = po.id) AS item_count
             FROM purchase_orders po
             JOIN suppliers s ON s.id = po.supplier_id
             LEFT JOIN users u ON u.id = po.user_id
             ORDER BY po.id DESC LIMIT 30"
        );

        $this->view('purchase_orders.index', ['title' => 'Purchase Order', 'rows' => $stmt->fetchAll()]);
    }

    public function create(Request $request): void
    {
        $this->view('purchase_orders.form', [
            'title' => 'Buat Purchase Order',
            'suppliers' => Supplier::all('name'),
            'medicines' => Medicine::where(['status' => 'active']),
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        $medicineIds = (array) $request->input('medicine_id', []);
        $qtys = (array) $request->input('qty', []);
        $prices = (array) $request->input('unit_price', []);

        if (empty($input['supplier_id']) || empty($medicineIds)) {
            Session::flash('error', 'Supplier dan minimal satu item wajib diisi.');
            $this->redirect('/purchase-orders/create');
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $poNumber = 'PO-' . date('Ymd-His');
            $stmt = $db->prepare(
                'INSERT INTO purchase_orders (po_number, supplier_id, order_date, expected_date, status, notes, user_id)
                 VALUES (:num, :supplier_id, CURDATE(), :expected, "sent", :notes, :user_id)'
            );
            $stmt->execute([
                'num' => $poNumber,
                'supplier_id' => $input['supplier_id'],
                'expected' => $input['expected_date'] ?: null,
                'notes' => $input['notes'] ?? null,
                'user_id' => Auth::id(),
            ]);
            $poId = (int) $db->lastInsertId();

            $insertDetail = $db->prepare(
                'INSERT INTO purchase_order_details (purchase_order_id, medicine_id, qty, unit_price, subtotal) VALUES (:po_id, :medicine_id, :qty, :price, :subtotal)'
            );
            foreach ($medicineIds as $i => $medicineId) {
                $qty = (float) ($qtys[$i] ?? 0);
                $price = (float) ($prices[$i] ?? 0);
                if ($qty <= 0 || !$medicineId) {
                    continue;
                }
                $insertDetail->execute(['po_id' => $poId, 'medicine_id' => $medicineId, 'qty' => $qty, 'price' => $price, 'subtotal' => $qty * $price]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Gagal membuat purchase order.');
            $this->redirect('/purchase-orders/create');
            return;
        }

        AuditLogger::log('create', 'purchase_order', $poId, null, ['po_number' => $poNumber]);
        Session::flash('success', 'Purchase Order berhasil dibuat.');
        $this->redirect('/purchase-orders/' . $poId);
    }

    public function show(Request $request, string $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT po.*, s.name AS supplier_name FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id WHERE po.id = :id');
        $stmt->execute(['id' => $id]);
        $po = $stmt->fetch();

        if (!$po) {
            Session::flash('error', 'Purchase Order tidak ditemukan.');
            $this->redirect('/purchase-orders');
            return;
        }

        $detailStmt = $db->prepare('SELECT d.*, m.name AS medicine_name FROM purchase_order_details d JOIN medicines m ON m.id = d.medicine_id WHERE d.purchase_order_id = :id');
        $detailStmt->execute(['id' => $id]);

        $this->view('purchase_orders.show', ['title' => 'PO ' . $po['po_number'], 'po' => $po, 'details' => $detailStmt->fetchAll()]);
    }

    public function cancel(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }
        $db = Database::connection();
        $db->prepare("UPDATE purchase_orders SET status = 'cancelled' WHERE id = :id AND status != 'completed'")->execute(['id' => $id]);
        AuditLogger::log('update', 'purchase_order', (int) $id, null, ['status' => 'cancelled']);
        Session::flash('success', 'Purchase Order dibatalkan.');
        $this->redirect('/purchase-orders/' . $id);
    }
}
