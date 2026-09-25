<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Services\AuditLogger;
use App\Services\ExpiryStatusService;
use App\Services\StockLedgerService;

class BatchController extends Controller
{
    public function index(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $filter = $request->query('filter', 'all');

        $where = [];
        $params = [];
        if ($search) {
            $where[] = '(m.name LIKE :search1 OR mb.batch_number LIKE :search2)';
            $params['search1'] = '%' . $search . '%';
            $params['search2'] = '%' . $search . '%';
        }
        if ($filter === 'expiring') {
            $where[] = "mb.status = 'active' AND mb.available_qty > 0 AND mb.expired_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
        } elseif ($filter === 'expired') {
            $where[] = "mb.expired_date < CURDATE()";
        } elseif ($filter === 'active') {
            $where[] = "mb.status = 'active' AND mb.available_qty > 0 AND mb.expired_date >= CURDATE()";
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $db = Database::connection();

        $countStmt = $db->prepare("SELECT COUNT(*) AS total FROM medicine_batches mb JOIN medicines m ON m.id = mb.medicine_id {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['total'];

        $perPage = 15;
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare(
            "SELECT mb.*, m.name AS medicine_name, m.code AS medicine_code
             FROM medicine_batches mb JOIN medicines m ON m.id = mb.medicine_id
             {$whereSql}
             ORDER BY mb.expired_date ASC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $this->view('batches.index', [
            'title' => 'Batch & Expired',
            'rows' => $rows,
            'pagination' => ['total' => $total, 'page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages],
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function store(Request $request, string $medicineId): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $medicineId = (int) $medicineId;
        $medicine = Medicine::find($medicineId);
        if (!$medicine) {
            Session::flash('error', 'Obat tidak ditemukan.');
            $this->redirect('/medicines');
            return;
        }

        $input = $request->all();
        $validator = $this->validate($input, [
            'batch_number' => 'required|max:60',
            'expired_date' => 'required|date',
            'received_date' => 'required|date',
            'initial_qty' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $this->withOldAndErrors($input, $validator);
            $this->redirect('/medicines/' . $medicineId . '/edit');
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $batchId = MedicineBatch::create([
                'medicine_id' => $medicineId,
                'batch_number' => $input['batch_number'],
                'received_date' => $input['received_date'],
                'production_date' => $input['production_date'] ?: null,
                'expired_date' => $input['expired_date'],
                'purchase_price' => $input['purchase_price'] ?: $medicine['purchase_price'],
                'selling_price' => $input['selling_price'] ?: $medicine['selling_price'],
                'initial_qty' => $input['initial_qty'],
                'status' => 'active',
            ]);

            StockLedgerService::move(
                $medicineId,
                $batchId,
                'opening_balance',
                (float) $input['initial_qty'],
                0,
                'batch',
                $batchId,
                'Stok awal batch ' . $input['batch_number'],
                \App\Core\Auth::id()
            );

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Gagal menyimpan batch: nomor batch mungkin sudah ada.');
            $this->redirect('/medicines/' . $medicineId . '/edit');
            return;
        }

        AuditLogger::log('create', 'medicine_batch', $batchId, null, $input);
        Session::flash('success', 'Batch berhasil ditambahkan dan stok awal tercatat.');
        $this->redirect('/medicines/' . $medicineId . '/edit');
    }

    public function updateStatus(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        $batch = MedicineBatch::find($id);
        if (!$batch) {
            Session::flash('error', 'Batch tidak ditemukan.');
            $this->back('/stock/batches');
            return;
        }

        $reason = $request->input('status');
        if (!in_array($reason, ['damaged', 'recalled'], true)) {
            Session::flash('error', 'Status tidak valid.');
            $this->back('/stock/batches');
            return;
        }
        // medicine_batches.status only allows active/expired/depleted/recalled (§27);
        // "damaged" is tracked via the stock_movements.movement_type instead.
        $batchStatus = 'recalled';

        $db = Database::connection();
        $db->beginTransaction();
        try {
            if ((float) $batch['available_qty'] > 0) {
                StockLedgerService::move(
                    (int) $batch['medicine_id'],
                    $id,
                    $reason === 'damaged' ? 'damaged' : 'adjustment',
                    0,
                    (float) $batch['available_qty'],
                    'batch_status_change',
                    $id,
                    'Batch ditandai ' . ($reason === 'damaged' ? 'rusak' : 'ditarik') . ' — ' . $request->input('note', ''),
                    \App\Core\Auth::id()
                );
            }
            MedicineBatch::update($id, ['status' => $batchStatus]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            \App\Core\Logger::error('BatchController::updateStatus failed: ' . $e->getMessage());
            Session::flash('error', 'Gagal memperbarui status batch.');
            $this->back('/stock/batches');
            return;
        }

        AuditLogger::log('update', 'medicine_batch', $id, $batch, ['status' => $batchStatus, 'reason' => $reason]);
        Session::flash('success', 'Batch ditandai ' . ($reason === 'damaged' ? 'rusak' : 'ditarik') . ' dan stok telah disesuaikan.');
        $this->back('/stock/batches');
    }
}
