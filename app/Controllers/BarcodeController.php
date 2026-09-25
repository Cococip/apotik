<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Medicine;
use App\Services\AuditLogger;
use App\Services\BarcodeCodeGenerator;

class BarcodeController extends Controller
{
    public function index(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $filter = $request->query('filter', 'all');

        $where = ["m.deleted_at IS NULL"];
        $params = [];
        if ($search) {
            $where[] = '(m.name LIKE :search1 OR m.code LIKE :search2 OR m.barcode LIKE :search3)';
            $like = '%' . $search . '%';
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
        }
        if ($filter === 'missing') {
            $where[] = 'm.barcode IS NULL';
        } elseif ($filter === 'assigned') {
            $where[] = 'm.barcode IS NOT NULL';
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $db = Database::connection();
        $countStmt = $db->prepare("SELECT COUNT(*) AS total FROM medicines m {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['total'];

        $perPage = 15;
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare(
            "SELECT m.id, m.code, m.name, m.barcode, m.selling_price
             FROM medicines m {$whereSql}
             ORDER BY m.name ASC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        $this->view('barcode.index', [
            'title' => 'Daftar Barcode',
            'rows' => $stmt->fetchAll(),
            'pagination' => ['total' => $total, 'page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages],
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function generate(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        $medicine = Medicine::find($id);
        if (!$medicine) {
            Session::flash('error', 'Obat tidak ditemukan.');
            $this->back('/barcode');
            return;
        }

        if ($medicine['barcode']) {
            Session::flash('error', 'Obat ini sudah memiliki barcode.');
            $this->back('/barcode');
            return;
        }

        $code = BarcodeCodeGenerator::next();
        Medicine::update($id, ['barcode' => $code]);
        AuditLogger::log('update', 'medicine_barcode', $id, ['barcode' => null], ['barcode' => $code]);

        Session::flash('success', "Barcode {$code} berhasil dibuat untuk {$medicine['name']}.");
        $this->back('/barcode');
    }

    public function generateBulk(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $db = Database::connection();
        $stmt = $db->query("SELECT id, name FROM medicines WHERE barcode IS NULL AND deleted_at IS NULL ORDER BY id ASC");
        $medicines = $stmt->fetchAll();

        $count = 0;
        foreach ($medicines as $medicine) {
            $code = BarcodeCodeGenerator::next();
            Medicine::update((int) $medicine['id'], ['barcode' => $code]);
            AuditLogger::log('update', 'medicine_barcode', (int) $medicine['id'], ['barcode' => null], ['barcode' => $code]);
            $count++;
        }

        Session::flash('success', $count > 0 ? "{$count} barcode berhasil dibuat untuk obat yang belum memiliki barcode." : 'Semua obat sudah memiliki barcode.');
        $this->redirect('/barcode');
    }

    public function printPage(Request $request): void
    {
        $rawIds = $request->query('ids', '');
        $idList = is_array($rawIds) ? $rawIds : explode(',', (string) $rawIds);
        $ids = array_values(array_filter(array_map('intval', $idList)));
        $size = in_array($request->query('size'), ['small', 'medium', 'large'], true) ? $request->query('size') : 'medium';

        $rows = [];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = Database::connection()->prepare("SELECT id, name, code, barcode, selling_price FROM medicines WHERE id IN ({$placeholders}) AND barcode IS NOT NULL");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll();
        }

        $this->view('barcode.print', [
            'title' => 'Cetak Label Barcode',
            'medicines' => $rows,
            'size' => $size,
        ], null);
    }
}
