<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditLogger;
use App\Services\StockLedgerService;

class PurchaseReturnController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT pr.*, p.purchase_number, s.name AS supplier_name, u.full_name AS user_name
             FROM purchase_returns pr
             JOIN purchases p ON p.id = pr.purchase_id
             JOIN suppliers s ON s.id = pr.supplier_id
             LEFT JOIN users u ON u.id = pr.user_id
             ORDER BY pr.id DESC LIMIT 30"
        );

        $this->view('purchase_returns.index', ['title' => 'Retur Pembelian', 'rows' => $stmt->fetchAll()]);
    }

    public function create(Request $request): void
    {
        $purchaseNumber = trim((string) $request->query('purchase', ''));
        $purchase = null;
        $details = [];

        if ($purchaseNumber !== '') {
            $db = Database::connection();
            $stmt = $db->prepare("SELECT p.*, s.name AS supplier_name FROM purchases p JOIN suppliers s ON s.id = p.supplier_id WHERE p.purchase_number = :num");
            $stmt->execute(['num' => $purchaseNumber]);
            $purchase = $stmt->fetch();

            if ($purchase) {
                $d = $db->prepare(
                    'SELECT pd.*, m.name AS medicine_name,
                            (SELECT COALESCE(SUM(prd.qty),0) FROM purchase_return_details prd WHERE prd.batch_id = pd.batch_id) AS returned_qty
                     FROM purchase_details pd JOIN medicines m ON m.id = pd.medicine_id WHERE pd.purchase_id = :id'
                );
                $d->execute(['id' => $purchase['id']]);
                $details = $d->fetchAll();
            } else {
                Session::flash('error', 'Nomor pembelian tidak ditemukan.');
            }
        }

        $this->view('purchase_returns.create', [
            'title' => 'Buat Retur Pembelian',
            'purchaseNumber' => $purchaseNumber,
            'purchase' => $purchase,
            'details' => $details,
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $purchaseId = (int) $request->input('purchase_id');
        $reason = trim((string) $request->input('reason', ''));
        $qtyInputs = (array) $request->input('qty', []);

        if ($reason === '') {
            Session::flash('error', 'Alasan retur wajib diisi.');
            $this->back('/purchase-returns');
            return;
        }

        $db = Database::connection();
        $purchaseStmt = $db->prepare('SELECT * FROM purchases WHERE id = :id');
        $purchaseStmt->execute(['id' => $purchaseId]);
        $purchase = $purchaseStmt->fetch();
        if (!$purchase) {
            Session::flash('error', 'Pembelian tidak ditemukan.');
            $this->redirect('/purchase-returns');
            return;
        }

        $db->beginTransaction();
        try {
            $returnNumber = 'RB-' . date('Ymd-His');
            $total = 0.0;
            $lines = [];

            foreach ($qtyInputs as $purchaseDetailId => $qty) {
                $qty = (float) $qty;
                if ($qty <= 0) {
                    continue;
                }
                $detailStmt = $db->prepare('SELECT * FROM purchase_details WHERE id = :id AND purchase_id = :pid');
                $detailStmt->execute(['id' => $purchaseDetailId, 'pid' => $purchaseId]);
                $detail = $detailStmt->fetch();
                if (!$detail) {
                    continue;
                }

                $batchStmt = $db->prepare('SELECT * FROM medicine_batches WHERE id = :id FOR UPDATE');
                $batchStmt->execute(['id' => $detail['batch_id']]);
                $batch = $batchStmt->fetch();
                if (!$batch || (float) $batch['available_qty'] < $qty) {
                    throw new \RuntimeException('Stok batch tidak mencukupi untuk retur (mungkin sudah terjual sebagian).');
                }

                $subtotal = $qty * (float) $detail['purchase_price'];
                $lines[] = ['detail' => $detail, 'qty' => $qty, 'subtotal' => $subtotal];
                $total += $subtotal;
            }

            if (empty($lines)) {
                throw new \RuntimeException('Tidak ada item retur yang valid.');
            }

            $insertReturn = $db->prepare(
                'INSERT INTO purchase_returns (return_number, purchase_id, supplier_id, return_date, reason, total, status, user_id)
                 VALUES (:num, :purchase_id, :supplier_id, CURDATE(), :reason, :total, "completed", :user_id)'
            );
            $insertReturn->execute([
                'num' => $returnNumber, 'purchase_id' => $purchaseId, 'supplier_id' => $purchase['supplier_id'],
                'reason' => $reason, 'total' => $total, 'user_id' => Auth::id(),
            ]);
            $returnId = (int) $db->lastInsertId();

            $insertDetail = $db->prepare(
                'INSERT INTO purchase_return_details (purchase_return_id, medicine_id, batch_id, qty, price, subtotal) VALUES (:return_id, :medicine_id, :batch_id, :qty, :price, :subtotal)'
            );
            foreach ($lines as $line) {
                $d = $line['detail'];
                $insertDetail->execute([
                    'return_id' => $returnId, 'medicine_id' => $d['medicine_id'], 'batch_id' => $d['batch_id'],
                    'qty' => $line['qty'], 'price' => $d['purchase_price'], 'subtotal' => $line['subtotal'],
                ]);

                StockLedgerService::move(
                    (int) $d['medicine_id'],
                    (int) $d['batch_id'],
                    'purchase_return',
                    0,
                    $line['qty'],
                    'purchase_return',
                    $returnId,
                    'Retur pembelian ' . $returnNumber,
                    Auth::id()
                );
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', $e->getMessage() ?: 'Gagal memproses retur pembelian.');
            $this->redirect('/purchase-returns/create?purchase=' . urlencode($purchase['purchase_number']));
            return;
        }

        AuditLogger::log('create', 'purchase_return', $returnId, null, ['purchase_id' => $purchaseId, 'total' => $total]);
        Session::flash('success', 'Retur pembelian berhasil diproses.');
        $this->redirect('/purchase-returns');
    }
}
