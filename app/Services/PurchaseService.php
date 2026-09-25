<?php

namespace App\Services;

use App\Core\Database;

/**
 * "Pembelian" doubles as goods receipt (§18 — Penerimaan digabung ke
 * Pembelian): saving a purchase immediately creates the batches and
 * stock_movements, matching business rule #6 (pembelian menambah stok
 * setelah penerimaan) since in this flow the two happen together.
 */
class PurchaseService
{
    public static function nextPurchaseNumber(): string
    {
        $count = (int) Database::connection()->query('SELECT COUNT(*) AS c FROM purchases')->fetch()['c'];
        return 'PO-BUY-' . date('Ymd') . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param array $header {supplier_id, supplier_invoice_number, purchase_date, due_date, notes, purchase_order_id?}
     * @param array $lines [{medicine_id, batch_number, production_date, expired_date, purchase_price, selling_price, qty, discount}]
     * @param float $paidNow amount paid immediately (creates a supplier_payment row)
     */
    public static function create(array $header, array $lines, float $paidNow, int $userId): int
    {
        if (empty($lines)) {
            throw new CheckoutException('Minimal satu item pembelian harus diisi.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $subtotal = 0.0;
            foreach ($lines as $line) {
                $subtotal += ((float) $line['qty'] * (float) $line['purchase_price']) - (float) ($line['discount'] ?? 0);
            }
            $total = max(0, $subtotal);
            $paidNow = min($paidNow, $total);
            $paymentStatus = $paidNow <= 0 ? 'unpaid' : ($paidNow >= $total ? 'paid' : 'partial');

            $purchaseNumber = self::nextPurchaseNumber();
            $insertPurchase = $db->prepare(
                'INSERT INTO purchases (purchase_number, purchase_order_id, supplier_id, supplier_invoice_number, purchase_date, due_date, subtotal, discount, tax, total, payment_status, status, notes, user_id)
                 VALUES (:num, :po_id, :supplier_id, :inv, :date, :due, :subtotal, 0, 0, :total, :pstatus, "received", :notes, :user_id)'
            );
            $insertPurchase->execute([
                'num' => $purchaseNumber,
                'po_id' => $header['purchase_order_id'] ?? null,
                'supplier_id' => $header['supplier_id'],
                'inv' => $header['supplier_invoice_number'] ?? null,
                'date' => $header['purchase_date'],
                'due' => $header['due_date'] ?: null,
                'subtotal' => $subtotal,
                'total' => $total,
                'pstatus' => $paymentStatus,
                'notes' => $header['notes'] ?? null,
                'user_id' => $userId,
            ]);
            $purchaseId = (int) $db->lastInsertId();

            $insertDetail = $db->prepare(
                'INSERT INTO purchase_details (purchase_id, medicine_id, batch_id, batch_number, expired_date, production_date, purchase_price, selling_price, qty, discount, subtotal)
                 VALUES (:purchase_id, :medicine_id, :batch_id, :batch_number, :expired_date, :production_date, :purchase_price, :selling_price, :qty, :discount, :subtotal)'
            );
            $insertBatch = $db->prepare(
                'INSERT INTO medicine_batches (medicine_id, batch_number, received_date, production_date, expired_date, purchase_price, selling_price, initial_qty, available_qty, status)
                 VALUES (:medicine_id, :batch_number, :received_date, :production_date, :expired_date, :purchase_price, :selling_price, :qty, 0, "active")'
            );

            foreach ($lines as $line) {
                $qty = (float) $line['qty'];
                $lineSubtotal = ($qty * (float) $line['purchase_price']) - (float) ($line['discount'] ?? 0);

                $insertBatch->execute([
                    'medicine_id' => $line['medicine_id'],
                    'batch_number' => $line['batch_number'],
                    'received_date' => $header['purchase_date'],
                    'production_date' => $line['production_date'] ?: null,
                    'expired_date' => $line['expired_date'],
                    'purchase_price' => $line['purchase_price'],
                    'selling_price' => $line['selling_price'] ?: $line['purchase_price'],
                    'qty' => $qty,
                ]);
                $batchId = (int) $db->lastInsertId();

                $insertDetail->execute([
                    'purchase_id' => $purchaseId,
                    'medicine_id' => $line['medicine_id'],
                    'batch_id' => $batchId,
                    'batch_number' => $line['batch_number'],
                    'expired_date' => $line['expired_date'],
                    'production_date' => $line['production_date'] ?: null,
                    'purchase_price' => $line['purchase_price'],
                    'selling_price' => $line['selling_price'] ?: $line['purchase_price'],
                    'qty' => $qty,
                    'discount' => $line['discount'] ?? 0,
                    'subtotal' => $lineSubtotal,
                ]);

                StockLedgerService::move(
                    (int) $line['medicine_id'],
                    $batchId,
                    'purchase',
                    $qty,
                    0,
                    'purchase',
                    $purchaseId,
                    'Pembelian ' . $purchaseNumber . ' — batch ' . $line['batch_number'],
                    $userId
                );
            }

            if ($paidNow > 0) {
                $db->prepare(
                    'INSERT INTO supplier_payments (payment_number, supplier_id, purchase_id, payment_date, amount, payment_method, note, user_id)
                     VALUES (:num, :supplier_id, :purchase_id, CURDATE(), :amount, "Cash", "Pembayaran saat penerimaan barang", :user_id)'
                )->execute([
                    'num' => 'PAY-' . date('Ymd-His'),
                    'supplier_id' => $header['supplier_id'],
                    'purchase_id' => $purchaseId,
                    'amount' => $paidNow,
                    'user_id' => $userId,
                ]);
            }

            if (!empty($header['purchase_order_id'])) {
                $db->prepare("UPDATE purchase_orders SET status = 'completed' WHERE id = :id")->execute(['id' => $header['purchase_order_id']]);
            }

            $db->commit();

            AuditLogger::log('create', 'purchase', $purchaseId, null, ['purchase_number' => $purchaseNumber, 'total' => $total]);

            return $purchaseId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
