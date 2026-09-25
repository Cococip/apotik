<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\CsvExporter;
use App\Services\ExpiryStatusService;

class ReportController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('reports.index', ['title' => 'Laporan']);
    }

    /** Export requires the separate report.export permission (§25 granular). */
    private function wantsExport(Request $request): bool
    {
        if ($request->query('export') !== 'csv') {
            return false;
        }
        if (!Auth::can('report.export')) {
            \App\Core\Response::forbidden();
            exit;
        }
        return true;
    }

    private function range(Request $request): array
    {
        return [
            $request->query('date_from', date('Y-m-01')),
            $request->query('date_to', date('Y-m-d')),
        ];
    }

    public function sales(Request $request): void
    {
        [$from, $to] = $this->range($request);
        $db = Database::connection();

        $stmt = $db->prepare(
            "SELECT s.invoice_number, s.transaction_date, u.full_name AS cashier_name, s.payment_method, s.grand_total, s.paid_amount,
                    (SELECT COALESCE(SUM(qty),0) FROM sale_details WHERE sale_id = s.id) AS item_qty
             FROM sales s JOIN users u ON u.id = s.cashier_id
             WHERE s.status = 'completed' AND DATE(s.transaction_date) BETWEEN :from AND :to
             ORDER BY s.transaction_date ASC"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);
        $rows = $stmt->fetchAll();

        if ($this->wantsExport($request)) {
            CsvExporter::stream('laporan-penjualan.csv', ['Invoice', 'Tanggal', 'Kasir', 'Metode', 'Item', 'Total', 'Dibayar'], array_map(
                fn($r) => [$r['invoice_number'], $r['transaction_date'], $r['cashier_name'], $r['payment_method'], $r['item_qty'], $r['grand_total'], $r['paid_amount']],
                $rows
            ));
            return;
        }

        $summary = [
            'total' => array_sum(array_column($rows, 'grand_total')),
            'count' => count($rows),
            'items' => array_sum(array_column($rows, 'item_qty')),
        ];

        $this->view('reports.sales', ['title' => 'Laporan Penjualan', 'rows' => $rows, 'summary' => $summary, 'dateFrom' => $from, 'dateTo' => $to]);
    }

    public function purchases(Request $request): void
    {
        [$from, $to] = $this->range($request);
        $db = Database::connection();

        $stmt = $db->prepare(
            "SELECT p.purchase_number, p.purchase_date, s.name AS supplier_name, p.total, p.payment_status,
                    (SELECT COALESCE(SUM(qty),0) FROM purchase_details WHERE purchase_id = p.id) AS item_qty
             FROM purchases p JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.purchase_date BETWEEN :from AND :to
             ORDER BY p.purchase_date ASC"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);
        $rows = $stmt->fetchAll();

        if ($this->wantsExport($request)) {
            CsvExporter::stream('laporan-pembelian.csv', ['No. Pembelian', 'Tanggal', 'Supplier', 'Item', 'Total', 'Status Bayar'], array_map(
                fn($r) => [$r['purchase_number'], $r['purchase_date'], $r['supplier_name'], $r['item_qty'], $r['total'], $r['payment_status']],
                $rows
            ));
            return;
        }

        $summary = ['total' => array_sum(array_column($rows, 'total')), 'count' => count($rows)];
        $this->view('reports.purchases', ['title' => 'Laporan Pembelian', 'rows' => $rows, 'summary' => $summary, 'dateFrom' => $from, 'dateTo' => $to]);
    }

    public function stock(Request $request): void
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT m.code, m.name, c.name AS category_name, u.symbol,
                    m.minimum_stock, m.maximum_stock,
                    COALESCE((SELECT SUM(mb.available_qty) FROM medicine_batches mb WHERE mb.medicine_id = m.id AND mb.status = 'active'), 0) AS stock
             FROM medicines m
             LEFT JOIN categories c ON c.id = m.category_id
             LEFT JOIN units u ON u.id = m.unit_id
             WHERE m.deleted_at IS NULL
             ORDER BY m.name ASC"
        );
        $rows = $stmt->fetchAll();

        if ($this->wantsExport($request)) {
            CsvExporter::stream('laporan-stok.csv', ['Kode', 'Nama Obat', 'Kategori', 'Stok', 'Satuan', 'Min', 'Maks'], array_map(
                fn($r) => [$r['code'], $r['name'], $r['category_name'], $r['stock'], $r['symbol'], $r['minimum_stock'], $r['maximum_stock']],
                $rows
            ));
            return;
        }

        $this->view('reports.stock', ['title' => 'Laporan Stok', 'rows' => $rows]);
    }

    public function expired(Request $request): void
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT mb.batch_number, mb.expired_date, mb.available_qty, m.name AS medicine_name, m.code
             FROM medicine_batches mb JOIN medicines m ON m.id = mb.medicine_id
             WHERE mb.status = 'active' AND mb.available_qty > 0
             ORDER BY mb.expired_date ASC"
        );
        $rows = array_map(function ($r) {
            $r['expiry_status'] = ExpiryStatusService::statusFor($r['expired_date']);
            return $r;
        }, $stmt->fetchAll());

        if ($this->wantsExport($request)) {
            CsvExporter::stream('laporan-expired.csv', ['Kode', 'Obat', 'Batch', 'Expired', 'Qty', 'Status'], array_map(
                fn($r) => [$r['code'], $r['medicine_name'], $r['batch_number'], $r['expired_date'], $r['available_qty'], $r['expiry_status']['label']],
                $rows
            ));
            return;
        }

        $this->view('reports.expired', ['title' => 'Laporan Expired', 'rows' => $rows]);
    }

    public function returns(Request $request): void
    {
        [$from, $to] = $this->range($request);
        $db = Database::connection();

        $saleReturns = $db->prepare(
            "SELECT sr.return_number, sr.return_date, s.invoice_number, sr.reason, sr.total
             FROM sale_returns sr JOIN sales s ON s.id = sr.sale_id
             WHERE sr.return_date BETWEEN :from AND :to ORDER BY sr.return_date ASC"
        );
        $saleReturns->execute(['from' => $from, 'to' => $to]);

        $purchaseReturns = $db->prepare(
            "SELECT pr.return_number, pr.return_date, p.purchase_number, s.name AS supplier_name, pr.reason, pr.total
             FROM purchase_returns pr JOIN purchases p ON p.id = pr.purchase_id JOIN suppliers s ON s.id = pr.supplier_id
             WHERE pr.return_date BETWEEN :from AND :to ORDER BY pr.return_date ASC"
        );
        $purchaseReturns->execute(['from' => $from, 'to' => $to]);

        $this->view('reports.returns', [
            'title' => 'Laporan Retur',
            'saleReturns' => $saleReturns->fetchAll(),
            'purchaseReturns' => $purchaseReturns->fetchAll(),
            'dateFrom' => $from, 'dateTo' => $to,
        ]);
    }

    public function profit(Request $request): void
    {
        [$from, $to] = $this->range($request);
        $db = Database::connection();

        $stmt = $db->prepare(
            "SELECT DATE(s.transaction_date) AS d,
                    COALESCE(SUM(sd.subtotal),0) AS revenue,
                    COALESCE(SUM(sd.qty * mb.purchase_price),0) AS cogs
             FROM sales s
             JOIN sale_details sd ON sd.sale_id = s.id
             LEFT JOIN medicine_batches mb ON mb.id = sd.batch_id
             WHERE s.status = 'completed' AND DATE(s.transaction_date) BETWEEN :from AND :to
             GROUP BY DATE(s.transaction_date) ORDER BY d ASC"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['profit'] = $r['revenue'] - $r['cogs'];
        }
        unset($r);

        if ($this->wantsExport($request)) {
            CsvExporter::stream('laporan-laba.csv', ['Tanggal', 'Omzet', 'Modal', 'Laba Kotor'], array_map(
                fn($r) => [$r['d'], $r['revenue'], $r['cogs'], $r['profit']], $rows
            ));
            return;
        }

        $totals = [
            'revenue' => array_sum(array_column($rows, 'revenue')),
            'cogs' => array_sum(array_column($rows, 'cogs')),
            'profit' => array_sum(array_column($rows, 'profit')),
        ];

        $this->view('reports.profit', ['title' => 'Laporan Laba', 'rows' => $rows, 'totals' => $totals, 'dateFrom' => $from, 'dateTo' => $to]);
    }

    public function debt(Request $request): void
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT p.purchase_number, s.name AS supplier_name, p.total, p.due_date,
                    (SELECT COALESCE(SUM(sp.amount),0) FROM supplier_payments sp WHERE sp.purchase_id = p.id) AS paid_total
             FROM purchases p JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.payment_status != 'paid'
             HAVING (p.total - paid_total) > 0
             ORDER BY p.due_date IS NULL, p.due_date ASC"
        );
        $rows = $stmt->fetchAll();

        if ($this->wantsExport($request)) {
            CsvExporter::stream('laporan-hutang.csv', ['No. Pembelian', 'Supplier', 'Total', 'Dibayar', 'Sisa', 'Jatuh Tempo'], array_map(
                fn($r) => [$r['purchase_number'], $r['supplier_name'], $r['total'], $r['paid_total'], $r['total'] - $r['paid_total'], $r['due_date']], $rows
            ));
            return;
        }

        $this->view('reports.debt', ['title' => 'Laporan Hutang', 'rows' => $rows, 'total' => array_sum(array_map(fn($r) => $r['total'] - $r['paid_total'], $rows))]);
    }

    public function finance(Request $request): void
    {
        [$from, $to] = $this->range($request);
        $db = Database::connection();

        $sql = "
            SELECT 'Penjualan (tunai diterima)' AS category, COALESCE(SUM(paid_amount),0) AS amount FROM sales WHERE status='completed' AND DATE(transaction_date) BETWEEN :f1 AND :t1
            UNION ALL SELECT 'Kas Masuk Manual', COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='cash_in' AND DATE(created_at) BETWEEN :f2 AND :t2
            UNION ALL SELECT 'Kas Keluar Manual', COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='cash_out' AND DATE(created_at) BETWEEN :f3 AND :t3
            UNION ALL SELECT 'Pengeluaran Operasional', COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN :f4 AND :t4
            UNION ALL SELECT 'Pembayaran Hutang Supplier', COALESCE(SUM(amount),0) FROM supplier_payments WHERE payment_date BETWEEN :f5 AND :t5
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute(['f1' => $from, 't1' => $to, 'f2' => $from, 't2' => $to, 'f3' => $from, 't3' => $to, 'f4' => $from, 't4' => $to, 'f5' => $from, 't5' => $to]);
        $rows = $stmt->fetchAll();

        if ($this->wantsExport($request)) {
            CsvExporter::stream('laporan-keuangan.csv', ['Kategori', 'Jumlah'], array_map(fn($r) => [$r['category'], $r['amount']], $rows));
            return;
        }

        $this->view('reports.finance', ['title' => 'Laporan Keuangan', 'rows' => $rows, 'dateFrom' => $from, 'dateTo' => $to]);
    }
}
