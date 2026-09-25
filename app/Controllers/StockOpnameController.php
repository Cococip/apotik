<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditLogger;
use App\Services\StockLedgerService;

class StockOpnameController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT so.*, u.full_name AS user_name,
                    (SELECT COUNT(*) FROM stock_opname_details d WHERE d.stock_opname_id = so.id) AS total_items,
                    (SELECT COUNT(*) FROM stock_opname_details d WHERE d.stock_opname_id = so.id AND d.difference_qty != 0) AS total_differences
             FROM stock_opnames so
             LEFT JOIN users u ON u.id = so.user_id
             ORDER BY so.id DESC LIMIT 30"
        );

        $this->view('stock_opname.index', [
            'title' => 'Stok Opname',
            'rows' => $stmt->fetchAll(),
        ]);
    }

    public function create(Request $request): void
    {
        $search = trim((string) $request->query('search', ''));
        $db = Database::connection();
        $sql = "SELECT mb.id AS batch_id, mb.batch_number, mb.expired_date, mb.available_qty,
                       m.id AS medicine_id, m.name AS medicine_name, m.code AS medicine_code, u.symbol AS unit_symbol
                FROM medicine_batches mb
                JOIN medicines m ON m.id = mb.medicine_id
                LEFT JOIN units u ON u.id = m.unit_id
                WHERE mb.status = 'active' AND m.deleted_at IS NULL";
        $params = [];
        if ($search) {
            $sql .= ' AND (m.name LIKE :search1 OR mb.batch_number LIKE :search2)';
            $params['search1'] = '%' . $search . '%';
            $params['search2'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY m.name ASC, mb.expired_date ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $this->view('stock_opname.create', [
            'title' => 'Buat Stok Opname',
            'batches' => $stmt->fetchAll(),
            'search' => $search,
            'today' => date('Y-m-d'),
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $physicalQty = $request->input('physical_qty', []);
        $note = trim((string) $request->input('note', ''));
        $opnameDate = $request->input('opname_date') ?: date('Y-m-d');

        $rows = [];
        foreach ((array) $physicalQty as $batchId => $qty) {
            if ($qty === '' || $qty === null) {
                continue;
            }
            $rows[(int) $batchId] = (float) $qty;
        }

        if (empty($rows)) {
            Session::flash('error', 'Isi minimal satu qty fisik untuk membuat opname.');
            $this->redirect('/stock/opname/create');
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $opnameNumber = 'SO-' . date('Ymd') . '-' . str_pad((string) (self::todayCount($db) + 1), 4, '0', STR_PAD_LEFT);

            $stmt = $db->prepare(
                'INSERT INTO stock_opnames (opname_number, opname_date, status, note, user_id) VALUES (:number, :date, "completed", :note, :user_id)'
            );
            $stmt->execute(['number' => $opnameNumber, 'date' => $opnameDate, 'note' => $note ?: null, 'user_id' => Auth::id()]);
            $opnameId = (int) $db->lastInsertId();

            $detailStmt = $db->prepare(
                'INSERT INTO stock_opname_details (stock_opname_id, medicine_id, batch_id, system_qty, physical_qty, difference_qty, note)
                 VALUES (:opname_id, :medicine_id, :batch_id, :system_qty, :physical_qty, :difference_qty, :note)'
            );

            $batchLookup = $db->prepare('SELECT medicine_id, available_qty, batch_number FROM medicine_batches WHERE id = :id');

            $itemCount = 0;
            $diffCount = 0;
            foreach ($rows as $batchId => $physical) {
                $batchLookup->execute(['id' => $batchId]);
                $batch = $batchLookup->fetch();
                if (!$batch) {
                    continue;
                }
                $system = (float) $batch['available_qty'];
                $difference = $physical - $system;

                $detailStmt->execute([
                    'opname_id' => $opnameId,
                    'medicine_id' => $batch['medicine_id'],
                    'batch_id' => $batchId,
                    'system_qty' => $system,
                    'physical_qty' => $physical,
                    'difference_qty' => $difference,
                    'note' => null,
                ]);
                $itemCount++;

                if ($difference != 0) {
                    StockLedgerService::move(
                        (int) $batch['medicine_id'],
                        $batchId,
                        'stock_opname',
                        $difference > 0 ? $difference : 0,
                        $difference < 0 ? abs($difference) : 0,
                        'stock_opname',
                        $opnameId,
                        'Stok opname ' . $opnameNumber . ' — batch ' . $batch['batch_number'],
                        Auth::id()
                    );
                    $diffCount++;
                }
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            \App\Core\Logger::error('StockOpname failed: ' . $e->getMessage());
            Session::flash('error', 'Gagal menyimpan stok opname.');
            $this->redirect('/stock/opname/create');
            return;
        }

        AuditLogger::log('create', 'stock_opname', $opnameId, null, ['items' => $itemCount, 'differences' => $diffCount]);
        Session::flash('success', "Stok opname {$opnameNumber} tersimpan: {$itemCount} item diperiksa, {$diffCount} selisih disesuaikan.");
        $this->redirect('/stock/opname/' . $opnameId);
    }

    public function show(Request $request, string $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT so.*, u.full_name AS user_name FROM stock_opnames so LEFT JOIN users u ON u.id = so.user_id WHERE so.id = :id');
        $stmt->execute(['id' => $id]);
        $opname = $stmt->fetch();

        if (!$opname) {
            Session::flash('error', 'Data opname tidak ditemukan.');
            $this->redirect('/stock/opname');
            return;
        }

        $detailStmt = $db->prepare(
            'SELECT d.*, m.name AS medicine_name, mb.batch_number
             FROM stock_opname_details d
             JOIN medicines m ON m.id = d.medicine_id
             JOIN medicine_batches mb ON mb.id = d.batch_id
             WHERE d.stock_opname_id = :id ORDER BY d.id ASC'
        );
        $detailStmt->execute(['id' => $id]);

        $this->view('stock_opname.show', [
            'title' => 'Detail Opname ' . $opname['opname_number'],
            'opname' => $opname,
            'details' => $detailStmt->fetchAll(),
        ]);
    }

    private static function todayCount(\PDO $db): int
    {
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM stock_opnames WHERE opname_number LIKE :prefix");
        $stmt->execute(['prefix' => 'SO-' . date('Ymd') . '-%']);
        return (int) $stmt->fetch()['c'];
    }
}
