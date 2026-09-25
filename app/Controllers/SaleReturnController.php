<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditLogger;
use App\Services\StockLedgerService;

class SaleReturnController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT sr.*, s.invoice_number, u.full_name AS user_name
             FROM sale_returns sr
             JOIN sales s ON s.id = sr.sale_id
             LEFT JOIN users u ON u.id = sr.user_id
             ORDER BY sr.id DESC LIMIT 30"
        );

        $this->view('sale_returns.index', [
            'title' => 'Retur Penjualan',
            'rows' => $stmt->fetchAll(),
        ]);
    }

    public function create(Request $request): void
    {
        $invoice = trim((string) $request->query('invoice', ''));
        $sale = null;
        $details = [];

        if ($invoice !== '') {
            $db = Database::connection();
            $stmt = $db->prepare("SELECT s.*, u.full_name AS cashier_name FROM sales s JOIN users u ON u.id = s.cashier_id WHERE s.invoice_number = :inv AND s.status = 'completed'");
            $stmt->execute(['inv' => $invoice]);
            $sale = $stmt->fetch();

            if ($sale) {
                $detailStmt = $db->prepare(
                    'SELECT sd.*, m.name AS medicine_name,
                            (SELECT COALESCE(SUM(srd.qty),0) FROM sale_return_details srd WHERE srd.sale_detail_id = sd.id) AS returned_qty
                     FROM sale_details sd JOIN medicines m ON m.id = sd.medicine_id
                     WHERE sd.sale_id = :id'
                );
                $detailStmt->execute(['id' => $sale['id']]);
                $details = $detailStmt->fetchAll();
            } else {
                Session::flash('error', 'Invoice tidak ditemukan atau transaksi sudah dibatalkan.');
            }
        }

        $this->view('sale_returns.create', [
            'title' => 'Buat Retur Penjualan',
            'invoice' => $invoice,
            'sale' => $sale,
            'details' => $details,
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $saleId = (int) $request->input('sale_id');
        $reason = trim((string) $request->input('reason', ''));
        $condition = $request->input('condition', 'baik');
        $qtyInputs = (array) $request->input('qty', []);

        if ($reason === '') {
            Session::flash('error', 'Alasan retur wajib diisi.');
            $this->back('/sale-returns/create');
            return;
        }

        $db = Database::connection();
        $saleStmt = $db->prepare("SELECT * FROM sales WHERE id = :id AND status = 'completed'");
        $saleStmt->execute(['id' => $saleId]);
        $sale = $saleStmt->fetch();
        if (!$sale) {
            Session::flash('error', 'Transaksi tidak ditemukan.');
            $this->redirect('/sale-returns/create');
            return;
        }

        $db->beginTransaction();
        try {
            $returnNumber = 'RJ-' . date('Ymd-His') . '-' . $saleId;
            $totalReturn = 0.0;
            $lines = [];

            foreach ($qtyInputs as $saleDetailId => $qty) {
                $qty = (float) $qty;
                if ($qty <= 0) {
                    continue;
                }

                $detailStmt = $db->prepare(
                    'SELECT sd.*, (SELECT COALESCE(SUM(srd.qty),0) FROM sale_return_details srd WHERE srd.sale_detail_id = sd.id) AS returned_qty
                     FROM sale_details sd WHERE sd.id = :id AND sd.sale_id = :sale_id'
                );
                $detailStmt->execute(['id' => $saleDetailId, 'sale_id' => $saleId]);
                $detail = $detailStmt->fetch();
                if (!$detail) {
                    continue;
                }

                $maxReturnable = (float) $detail['qty'] - (float) $detail['returned_qty'];
                if ($qty > $maxReturnable) {
                    throw new \RuntimeException('Qty retur melebihi jumlah yang bisa dikembalikan untuk salah satu item.');
                }

                $subtotal = $qty * (float) $detail['unit_price'];
                $lines[] = ['detail' => $detail, 'qty' => $qty, 'subtotal' => $subtotal];
                $totalReturn += $subtotal;
            }

            if (empty($lines)) {
                throw new \RuntimeException('Tidak ada item retur yang valid.');
            }

            $insertReturn = $db->prepare(
                'INSERT INTO sale_returns (return_number, sale_id, return_date, reason, total, status, user_id) VALUES (:num, :sale_id, CURDATE(), :reason, :total, "completed", :user_id)'
            );
            $insertReturn->execute(['num' => $returnNumber, 'sale_id' => $saleId, 'reason' => $reason, 'total' => $totalReturn, 'user_id' => Auth::id()]);
            $returnId = (int) $db->lastInsertId();

            $insertDetail = $db->prepare(
                'INSERT INTO sale_return_details (sale_return_id, sale_detail_id, medicine_id, batch_id, qty, price, subtotal, condition_note)
                 VALUES (:return_id, :sale_detail_id, :medicine_id, :batch_id, :qty, :price, :subtotal, :condition)'
            );

            foreach ($lines as $line) {
                $d = $line['detail'];
                $insertDetail->execute([
                    'return_id' => $returnId,
                    'sale_detail_id' => $d['id'],
                    'medicine_id' => $d['medicine_id'],
                    'batch_id' => $d['batch_id'],
                    'qty' => $line['qty'],
                    'price' => $d['unit_price'],
                    'subtotal' => $line['subtotal'],
                    'condition' => $condition,
                ]);

                if ($condition === 'baik' && $d['batch_id']) {
                    StockLedgerService::move(
                        (int) $d['medicine_id'],
                        (int) $d['batch_id'],
                        'sale_return',
                        $line['qty'],
                        0,
                        'sale_return',
                        $returnId,
                        'Retur penjualan ' . $returnNumber,
                        Auth::id()
                    );
                }
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', $e->getMessage() ?: 'Gagal memproses retur.');
            $this->redirect('/sale-returns/create?invoice=' . urlencode($sale['invoice_number']));
            return;
        }

        AuditLogger::log('create', 'sale_return', $returnId, null, ['sale_id' => $saleId, 'total' => $totalReturn]);
        Session::flash('success', 'Retur penjualan berhasil diproses.');
        $this->redirect('/sale-returns');
    }
}
