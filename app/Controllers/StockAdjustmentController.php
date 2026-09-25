<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditLogger;
use App\Services\StockLedgerService;

class StockAdjustmentController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT sm.*, m.name AS medicine_name, mb.batch_number, u.full_name AS user_name
             FROM stock_movements sm
             JOIN medicines m ON m.id = sm.medicine_id
             LEFT JOIN medicine_batches mb ON mb.id = sm.batch_id
             LEFT JOIN users u ON u.id = sm.user_id
             WHERE sm.movement_type IN ('adjustment', 'damaged') AND sm.reference_type = 'manual_adjustment'
             ORDER BY sm.id DESC LIMIT 30"
        );

        $medicines = $db->query(
            "SELECT id, code, name FROM medicines WHERE deleted_at IS NULL AND status = 'active' ORDER BY name ASC"
        )->fetchAll();

        $this->view('stock_adjustment.index', [
            'title' => 'Penyesuaian Stok',
            'rows' => $stmt->fetchAll(),
            'medicines' => $medicines,
        ]);
    }

    public function batchesForMedicine(Request $request, string $medicineId): void
    {
        $stmt = Database::connection()->prepare(
            "SELECT id, batch_number, available_qty, expired_date FROM medicine_batches
             WHERE medicine_id = :id AND status = 'active' ORDER BY expired_date ASC"
        );
        $stmt->execute(['id' => $medicineId]);
        $this->json(['success' => true, 'data' => $stmt->fetchAll(), 'message' => 'OK', 'errors' => []]);
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        $validator = $this->validate($input, [
            'batch_id' => 'required|integer',
            'direction' => 'required|in:in,out',
            'qty' => 'required|numeric',
            'reason' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('/stock/adjustment');
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM medicine_batches WHERE id = :id');
        $stmt->execute(['id' => $input['batch_id']]);
        $batch = $stmt->fetch();

        if (!$batch) {
            Session::flash('error', 'Batch tidak ditemukan.');
            $this->redirect('/stock/adjustment');
            return;
        }

        $qty = (float) $input['qty'];
        $direction = $input['direction'];

        if ($direction === 'out' && $qty > (float) $batch['available_qty']) {
            Session::flash('error', 'Qty pengurangan melebihi stok tersedia pada batch ini.');
            $this->redirect('/stock/adjustment');
            return;
        }

        try {
            $movementId = StockLedgerService::moveTransactional(
                (int) $batch['medicine_id'],
                (int) $batch['id'],
                'adjustment',
                $direction === 'in' ? $qty : 0,
                $direction === 'out' ? $qty : 0,
                'manual_adjustment',
                null,
                $input['reason'],
                Auth::id()
            );
        } catch (\Throwable $e) {
            Session::flash('error', 'Gagal menyimpan penyesuaian stok.');
            $this->redirect('/stock/adjustment');
            return;
        }

        AuditLogger::log('create', 'stock_adjustment', $movementId, null, $input);
        Session::flash('success', 'Penyesuaian stok berhasil disimpan.');
        $this->redirect('/stock/adjustment');
    }
}
