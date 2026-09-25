<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Supplier;
use App\Services\AuditLogger;

class SupplierPaymentController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::connection();

        $outstanding = $db->query(
            "SELECT p.*, s.name AS supplier_name,
                    (SELECT COALESCE(SUM(sp.amount),0) FROM supplier_payments sp WHERE sp.purchase_id = p.id) AS paid_total
             FROM purchases p JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.payment_status != 'paid'
             HAVING (p.total - paid_total) > 0
             ORDER BY p.due_date IS NULL, p.due_date ASC"
        )->fetchAll();

        $recentPayments = $db->query(
            "SELECT sp.*, s.name AS supplier_name, p.purchase_number, u.full_name AS user_name
             FROM supplier_payments sp
             JOIN suppliers s ON s.id = sp.supplier_id
             LEFT JOIN purchases p ON p.id = sp.purchase_id
             LEFT JOIN users u ON u.id = sp.user_id
             ORDER BY sp.id DESC LIMIT 20"
        )->fetchAll();

        $this->view('supplier_payments.index', [
            'title' => 'Hutang Supplier',
            'outstanding' => $outstanding,
            'recentPayments' => $recentPayments,
            'preselectPurchaseId' => $request->query('purchase_id'),
        ]);
    }

    public function outstandingForSupplier(Request $request, string $supplierId): void
    {
        $stmt = Database::connection()->prepare(
            "SELECT p.id, p.purchase_number, p.total,
                    (SELECT COALESCE(SUM(sp.amount),0) FROM supplier_payments sp WHERE sp.purchase_id = p.id) AS paid_total
             FROM purchases p WHERE p.supplier_id = :id AND p.payment_status != 'paid'"
        );
        $stmt->execute(['id' => $supplierId]);
        $rows = array_values(array_filter($stmt->fetchAll(), fn($r) => ((float) $r['total'] - (float) $r['paid_total']) > 0));

        $this->json(['success' => true, 'message' => 'OK', 'errors' => [], 'data' => $rows]);
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $purchaseId = (int) $request->input('purchase_id');
        $amount = (float) $request->input('amount', 0);
        $method = $request->input('payment_method', 'Cash');

        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT p.*, (SELECT COALESCE(SUM(sp.amount),0) FROM supplier_payments sp WHERE sp.purchase_id = p.id) AS paid_total
             FROM purchases p WHERE p.id = :id"
        );
        $stmt->execute(['id' => $purchaseId]);
        $purchase = $stmt->fetch();

        if (!$purchase) {
            Session::flash('error', 'Data pembelian tidak ditemukan.');
            $this->redirect('/supplier-payments');
            return;
        }

        $remaining = (float) $purchase['total'] - (float) $purchase['paid_total'];
        if ($amount <= 0 || $amount > $remaining + 0.01) {
            Session::flash('error', 'Jumlah pembayaran tidak valid (melebihi sisa hutang atau nol).');
            $this->redirect('/supplier-payments');
            return;
        }

        $db->beginTransaction();
        try {
            $db->prepare(
                'INSERT INTO supplier_payments (payment_number, supplier_id, purchase_id, payment_date, amount, payment_method, note, user_id)
                 VALUES (:num, :supplier_id, :purchase_id, CURDATE(), :amount, :method, :note, :user_id)'
            )->execute([
                'num' => 'PAY-' . date('Ymd-His'),
                'supplier_id' => $purchase['supplier_id'],
                'purchase_id' => $purchaseId,
                'amount' => $amount,
                'method' => $method,
                'note' => $request->input('note') ?: null,
                'user_id' => Auth::id(),
            ]);

            $newPaidTotal = (float) $purchase['paid_total'] + $amount;
            $newStatus = $newPaidTotal >= (float) $purchase['total'] - 0.01 ? 'paid' : 'partial';
            $db->prepare('UPDATE purchases SET payment_status = :status WHERE id = :id')->execute(['status' => $newStatus, 'id' => $purchaseId]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Gagal menyimpan pembayaran.');
            $this->redirect('/supplier-payments');
            return;
        }

        AuditLogger::log('create', 'supplier_payment', $purchaseId, null, ['amount' => $amount]);
        Session::flash('success', 'Pembayaran hutang berhasil dicatat.');
        $this->redirect('/supplier-payments');
    }
}
