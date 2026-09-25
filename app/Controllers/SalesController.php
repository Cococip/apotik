<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;

class SalesController extends Controller
{
    public function index(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from', '');
        $dateTo = $request->query('date_to', '');
        $paymentMethod = $request->query('payment_method', '');

        $where = [];
        $params = [];
        if ($search) {
            $where[] = 's.invoice_number LIKE :search';
            $params['search'] = '%' . $search . '%';
        }
        if ($dateFrom) {
            $where[] = 's.transaction_date >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo) {
            $where[] = 's.transaction_date <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }
        if ($paymentMethod) {
            $where[] = 's.payment_method = :payment_method';
            $params['payment_method'] = $paymentMethod;
        }
        if (!Auth::can('sales.view') || !in_array(Auth::roleSlug(), ['super_admin', 'owner', 'apoteker'], true)) {
            // Kasir only sees their own transactions unless elevated.
            if (Auth::roleSlug() === 'kasir') {
                $where[] = 's.cashier_id = :cashier_id';
                $params['cashier_id'] = Auth::id();
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $db = Database::connection();

        $countStmt = $db->prepare("SELECT COUNT(*) AS total FROM sales s {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['total'];

        $perPage = 15;
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare(
            "SELECT s.*, u.full_name AS cashier_name, c.name AS customer_name,
                    (SELECT COUNT(*) FROM sale_returns sr WHERE sr.sale_id = s.id) AS return_count
             FROM sales s
             JOIN users u ON u.id = s.cashier_id
             LEFT JOIN customers c ON c.id = s.customer_id
             {$whereSql}
             ORDER BY s.transaction_date DESC, s.id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        $this->view('sales.index', [
            'title' => 'Penjualan',
            'rows' => $stmt->fetchAll(),
            'pagination' => ['total' => $total, 'page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages],
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'paymentMethod' => $paymentMethod,
        ]);
    }

    public function show(Request $request, string $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT s.*, u.full_name AS cashier_name, c.name AS customer_name, d.name AS doctor_name
             FROM sales s
             JOIN users u ON u.id = s.cashier_id
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN doctors d ON d.id = s.doctor_id
             WHERE s.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $sale = $stmt->fetch();

        if (!$sale) {
            Session::flash('error', 'Transaksi tidak ditemukan.');
            $this->redirect('/sales');
            return;
        }

        $detailStmt = $db->prepare(
            'SELECT sd.*, m.name AS medicine_name, mb.batch_number,
                    (SELECT COALESCE(SUM(srd.qty),0) FROM sale_return_details srd WHERE srd.sale_detail_id = sd.id) AS returned_qty
             FROM sale_details sd
             JOIN medicines m ON m.id = sd.medicine_id
             LEFT JOIN medicine_batches mb ON mb.id = sd.batch_id
             WHERE sd.sale_id = :id'
        );
        $detailStmt->execute(['id' => $id]);

        $this->view('sales.show', [
            'title' => 'Detail Transaksi ' . $sale['invoice_number'],
            'sale' => $sale,
            'details' => $detailStmt->fetchAll(),
        ]);
    }
}
