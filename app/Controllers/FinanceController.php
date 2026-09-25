<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditLogger;
use App\Services\CashShiftService;

class FinanceController extends Controller
{
    private function listCashTransactions(string $type): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT ct.*, u.full_name AS user_name FROM cash_transactions ct
             LEFT JOIN users u ON u.id = ct.user_id
             WHERE ct.type = :type ORDER BY ct.id DESC LIMIT 50"
        );
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }

    public function cashIn(Request $request): void
    {
        $this->view('finance.cash_in', ['title' => 'Kas Masuk', 'rows' => $this->listCashTransactions('cash_in')]);
    }

    public function storeCashIn(Request $request): void
    {
        $this->storeCashTransaction($request, 'cash_in', '/finance/cash-in');
    }

    public function cashOut(Request $request): void
    {
        $this->view('finance.cash_out', ['title' => 'Kas Keluar', 'rows' => $this->listCashTransactions('cash_out')]);
    }

    public function storeCashOut(Request $request): void
    {
        $this->storeCashTransaction($request, 'cash_out', '/finance/cash-out');
    }

    private function storeCashTransaction(Request $request, string $type, string $redirectTo): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $amount = (float) $request->input('amount', 0);
        $category = trim((string) $request->input('category', ''));
        $description = trim((string) $request->input('description', ''));

        if ($amount <= 0 || $category === '') {
            Session::flash('error', 'Kategori dan jumlah wajib diisi.');
            $this->redirect($redirectTo);
            return;
        }

        $shift = CashShiftService::currentOpenShift(Auth::id());

        $id = Database::connection()->prepare(
            'INSERT INTO cash_transactions (shift_id, type, category, amount, description, user_id) VALUES (:shift_id, :type, :category, :amount, :description, :user_id)'
        );
        $id->execute([
            'shift_id' => $shift['id'] ?? null,
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'description' => $description ?: null,
            'user_id' => Auth::id(),
        ]);

        AuditLogger::log('create', 'cash_transaction', (int) Database::connection()->lastInsertId(), null, ['type' => $type, 'amount' => $amount]);
        Session::flash('success', 'Transaksi kas berhasil dicatat.');
        $this->redirect($redirectTo);
    }

    public function expenses(Request $request): void
    {
        $stmt = Database::connection()->query(
            "SELECT e.*, u.full_name AS user_name FROM expenses e LEFT JOIN users u ON u.id = e.user_id ORDER BY e.id DESC LIMIT 50"
        );
        $this->view('finance.expenses', ['title' => 'Pengeluaran', 'rows' => $stmt->fetchAll()]);
    }

    public function storeExpense(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $amount = (float) $request->input('amount', 0);
        $category = trim((string) $request->input('category', ''));

        if ($amount <= 0 || $category === '') {
            Session::flash('error', 'Kategori dan jumlah wajib diisi.');
            $this->redirect('/finance/expenses');
            return;
        }

        $db = Database::connection();
        $expenseNumber = 'EXP-' . date('Ymd-His');
        $db->prepare(
            'INSERT INTO expenses (expense_number, category, description, amount, expense_date, user_id) VALUES (:num, :category, :description, :amount, :date, :user_id)'
        )->execute([
            'num' => $expenseNumber,
            'category' => $category,
            'description' => $request->input('description') ?: null,
            'amount' => $amount,
            'date' => $request->input('expense_date') ?: date('Y-m-d'),
            'user_id' => Auth::id(),
        ]);

        AuditLogger::log('create', 'expense', (int) $db->lastInsertId(), null, ['category' => $category, 'amount' => $amount]);
        Session::flash('success', 'Pengeluaran berhasil dicatat.');
        $this->redirect('/finance/expenses');
    }

    public function piutang(Request $request): void
    {
        $stmt = Database::connection()->query(
            "SELECT s.*, c.name AS customer_name, c.phone AS customer_phone
             FROM sales s LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.paid_amount < s.grand_total AND s.status = 'completed'
             ORDER BY s.transaction_date ASC"
        );
        $rows = $stmt->fetchAll();
        $totalOutstanding = array_sum(array_map(fn($r) => (float) $r['grand_total'] - (float) $r['paid_amount'], $rows));

        $this->view('finance.piutang', ['title' => 'Piutang', 'rows' => $rows, 'totalOutstanding' => $totalOutstanding]);
    }

    public function payPiutang(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $saleId = (int) $request->input('sale_id');
        $amount = (float) $request->input('amount', 0);

        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM sales WHERE id = :id AND status = 'completed'");
        $stmt->execute(['id' => $saleId]);
        $sale = $stmt->fetch();

        if (!$sale) {
            Session::flash('error', 'Transaksi tidak ditemukan.');
            $this->redirect('/finance/piutang');
            return;
        }

        $remaining = (float) $sale['grand_total'] - (float) $sale['paid_amount'];
        if ($amount <= 0 || $amount > $remaining + 0.01) {
            Session::flash('error', 'Jumlah pembayaran tidak valid.');
            $this->redirect('/finance/piutang');
            return;
        }

        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO payments (sale_id, method, amount, paid_at) VALUES (:sale_id, "Cash", :amount, NOW())')
                ->execute(['sale_id' => $saleId, 'amount' => $amount]);

            $newPaid = (float) $sale['paid_amount'] + $amount;
            $db->prepare('UPDATE sales SET paid_amount = :paid WHERE id = :id')->execute(['paid' => $newPaid, 'id' => $saleId]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Gagal mencatat pembayaran piutang.');
            $this->redirect('/finance/piutang');
            return;
        }

        AuditLogger::log('update', 'sale_payment', $saleId, ['paid_amount' => $sale['paid_amount']], ['paid_amount' => $newPaid]);
        Session::flash('success', 'Pembayaran piutang berhasil dicatat.');
        $this->redirect('/finance/piutang');
    }

    public function recap(Request $request): void
    {
        $dateFrom = $request->query('date_from', date('Y-m-01'));
        $dateTo = $request->query('date_to', date('Y-m-d'));

        $db = Database::connection();

        $salesCash = $db->prepare(
            "SELECT COALESCE(SUM(paid_amount),0) AS total FROM sales WHERE status='completed' AND DATE(transaction_date) BETWEEN :from AND :to"
        );
        $salesCash->execute(['from' => $dateFrom, 'to' => $dateTo]);

        $cashInManual = $db->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM cash_transactions WHERE type='cash_in' AND DATE(created_at) BETWEEN :from AND :to");
        $cashInManual->execute(['from' => $dateFrom, 'to' => $dateTo]);

        $cashOutManual = $db->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM cash_transactions WHERE type='cash_out' AND DATE(created_at) BETWEEN :from AND :to");
        $cashOutManual->execute(['from' => $dateFrom, 'to' => $dateTo]);

        $expensesTotal = $db->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE expense_date BETWEEN :from AND :to");
        $expensesTotal->execute(['from' => $dateFrom, 'to' => $dateTo]);

        $purchasePayments = $db->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM supplier_payments WHERE payment_date BETWEEN :from AND :to");
        $purchasePayments->execute(['from' => $dateFrom, 'to' => $dateTo]);

        $salesTotal = $db->prepare("SELECT COALESCE(SUM(grand_total),0) AS revenue, COUNT(*) AS trx FROM sales WHERE status='completed' AND DATE(transaction_date) BETWEEN :from AND :to");
        $salesTotal->execute(['from' => $dateFrom, 'to' => $dateTo]);
        $salesRow = $salesTotal->fetch();

        $cogs = $db->prepare(
            "SELECT COALESCE(SUM(sd.qty * mb.purchase_price),0) AS total
             FROM sale_details sd JOIN sales s ON s.id = sd.sale_id LEFT JOIN medicine_batches mb ON mb.id = sd.batch_id
             WHERE s.status='completed' AND DATE(s.transaction_date) BETWEEN :from AND :to"
        );
        $cogs->execute(['from' => $dateFrom, 'to' => $dateTo]);

        $data = [
            'sales_cash_in' => (float) $salesCash->fetch()['total'],
            'manual_cash_in' => (float) $cashInManual->fetch()['total'],
            'manual_cash_out' => (float) $cashOutManual->fetch()['total'],
            'expenses' => (float) $expensesTotal->fetch()['total'],
            'purchase_payments' => (float) $purchasePayments->fetch()['total'],
            'revenue' => (float) $salesRow['revenue'],
            'transaction_count' => (int) $salesRow['trx'],
            'cogs' => (float) $cogs->fetch()['total'],
        ];
        $data['gross_profit'] = $data['revenue'] - $data['cogs'];
        $data['total_cash_in'] = $data['sales_cash_in'] + $data['manual_cash_in'];
        $data['total_cash_out'] = $data['manual_cash_out'] + $data['expenses'] + $data['purchase_payments'];
        $data['net_cash_flow'] = $data['total_cash_in'] - $data['total_cash_out'];

        $this->view('finance.recap', ['title' => 'Rekap Keuangan', 'data' => $data, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo]);
    }
}
