<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Single checkout path shared by the online POS and the offline sync
 * engine (§53 #9/#10/#11 — every sale has a UUID, server is idempotent,
 * server always re-derives FEFO batch allocation instead of trusting the
 * client's cached stock, §13).
 */
class SalesService
{
    public static function nextInvoiceNumber(PDO $db, string $date): string
    {
        $db->prepare('INSERT INTO invoice_counters (counter_date, last_number) VALUES (:d, 1) ON DUPLICATE KEY UPDATE last_number = last_number + 1')
            ->execute(['d' => $date]);
        $stmt = $db->prepare('SELECT last_number FROM invoice_counters WHERE counter_date = :d');
        $stmt->execute(['d' => $date]);
        $number = (int) $stmt->fetch()['last_number'];

        $prefix = SettingsService::get('transaction.invoice_prefix', 'INV');
        return $prefix . '-' . str_replace('-', '', $date) . '-' . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }

    public static function generateUuid(): string
    {
        return sprintf(
            '%08x-%04x-4%03x-%04x-%012x',
            mt_rand(0, 0xffffffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xfff),
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffffffffffff)
        );
    }

    /**
     * @param array $params {
     *   items: array<{medicine_id:int, qty:float}>,
     *   payment_method: string, paid_amount: float, discount?: float,
     *   customer_id?: int|null, doctor_id?: int|null, prescription_id?: int|null,
     *   notes?: string|null, cashier_id: int, shift_id?: int|null,
     *   device_id?: string|null, uuid?: string|null, transaction_date?: string|null,
     *   sync_status?: string,
     * }
     * @throws CheckoutException
     */
    public static function checkout(array $params): array
    {
        $db = Database::connection();
        $uuid = $params['uuid'] ?? self::generateUuid();

        // Idempotency (§11/§53 #11): a sale with this UUID already exists —
        // return it instead of creating a duplicate.
        $existing = $db->prepare('SELECT id, invoice_number FROM sales WHERE uuid = :u LIMIT 1');
        $existing->execute(['u' => $uuid]);
        if ($row = $existing->fetch()) {
            return ['sale_id' => (int) $row['id'], 'invoice_number' => $row['invoice_number'], 'duplicate' => true];
        }

        if (empty($params['items'])) {
            throw new CheckoutException('Keranjang kosong.');
        }

        $db->beginTransaction();
        try {
            $lines = [];
            $subtotal = 0.0;

            foreach ($params['items'] as $item) {
                $medicineId = (int) $item['medicine_id'];
                $qtyNeeded = (float) $item['qty'];
                if ($qtyNeeded <= 0) {
                    continue;
                }

                $medStmt = $db->prepare("SELECT * FROM medicines WHERE id = :id AND deleted_at IS NULL AND status = 'active' FOR UPDATE");
                $medStmt->execute(['id' => $medicineId]);
                $medicine = $medStmt->fetch();
                if (!$medicine) {
                    throw new CheckoutException('Salah satu obat di keranjang tidak ditemukan atau nonaktif.');
                }

                $unitPrice = !empty($params['prescription_id'])
                    ? (float) $medicine['selling_price_prescription']
                    : (float) $medicine['selling_price'];

                $batchStmt = $db->prepare(
                    "SELECT * FROM medicine_batches
                     WHERE medicine_id = :id AND status = 'active' AND expired_date >= CURDATE() AND available_qty > 0
                     ORDER BY expired_date ASC FOR UPDATE"
                );
                $batchStmt->execute(['id' => $medicineId]);
                $batches = $batchStmt->fetchAll();

                $remaining = $qtyNeeded;
                foreach ($batches as $batch) {
                    if ($remaining <= 0.0001) {
                        break;
                    }
                    $take = min($remaining, (float) $batch['available_qty']);
                    $lines[] = [
                        'medicine_id' => $medicineId,
                        'medicine_name' => $medicine['name'],
                        'batch_id' => (int) $batch['id'],
                        'qty' => $take,
                        'unit_price' => $unitPrice,
                        'subtotal' => $take * $unitPrice,
                    ];
                    $remaining -= $take;
                    $subtotal += $take * $unitPrice;
                }

                if ($remaining > 0.0001) {
                    throw new CheckoutException("Stok {$medicine['name']} tidak mencukupi. Sisa permintaan: " . rtrim(rtrim(number_format($remaining, 2, '.', ''), '0'), '.'));
                }
            }

            if (empty($lines)) {
                throw new CheckoutException('Tidak ada item valid untuk diproses.');
            }

            $discount = max(0, (float) ($params['discount'] ?? 0));
            $tax = 0.0;
            $grandTotal = max(0, $subtotal - $discount + $tax);
            $paidAmount = (float) ($params['paid_amount'] ?? 0);

            $isCredit = $paidAmount + 0.0001 < $grandTotal;
            if ($isCredit && empty($params['customer_id'])) {
                throw new CheckoutException('Transaksi dengan pembayaran kurang (piutang) wajib memilih pasien/pelanggan.');
            }

            $changeAmount = max(0, $paidAmount - $grandTotal);
            $transactionDate = $params['transaction_date'] ?? date('Y-m-d H:i:s');
            $invoiceNumber = self::nextInvoiceNumber($db, date('Y-m-d', strtotime($transactionDate)));

            $insertSale = $db->prepare(
                'INSERT INTO sales (invoice_number, uuid, transaction_date, customer_id, doctor_id, prescription_id, cashier_id, shift_id, subtotal, discount, tax, grand_total, paid_amount, change_amount, payment_method, status, notes, sync_status, device_id)
                 VALUES (:invoice_number, :uuid, :transaction_date, :customer_id, :doctor_id, :prescription_id, :cashier_id, :shift_id, :subtotal, :discount, :tax, :grand_total, :paid_amount, :change_amount, :payment_method, "completed", :notes, :sync_status, :device_id)'
            );
            $insertSale->execute([
                'invoice_number' => $invoiceNumber,
                'uuid' => $uuid,
                'transaction_date' => $transactionDate,
                'customer_id' => $params['customer_id'] ?? null,
                'doctor_id' => $params['doctor_id'] ?? null,
                'prescription_id' => $params['prescription_id'] ?? null,
                'cashier_id' => $params['cashier_id'],
                'shift_id' => $params['shift_id'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $params['payment_method'] ?? 'Cash',
                'notes' => $params['notes'] ?? null,
                'sync_status' => $params['sync_status'] ?? 'synced',
                'device_id' => $params['device_id'] ?? null,
            ]);
            $saleId = (int) $db->lastInsertId();

            $insertDetail = $db->prepare(
                'INSERT INTO sale_details (sale_id, medicine_id, batch_id, qty, unit_price, discount, subtotal) VALUES (:sale_id, :medicine_id, :batch_id, :qty, :unit_price, 0, :subtotal)'
            );
            foreach ($lines as $line) {
                $insertDetail->execute([
                    'sale_id' => $saleId,
                    'medicine_id' => $line['medicine_id'],
                    'batch_id' => $line['batch_id'],
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);

                StockLedgerService::move(
                    $line['medicine_id'],
                    $line['batch_id'],
                    'sale',
                    0,
                    $line['qty'],
                    'sale',
                    $saleId,
                    'Penjualan ' . $invoiceNumber,
                    $params['cashier_id']
                );
            }

            if ($paidAmount > 0) {
                $db->prepare('INSERT INTO payments (sale_id, method, amount, paid_at) VALUES (:sale_id, :method, :amount, :paid_at)')
                    ->execute([
                        'sale_id' => $saleId,
                        'method' => $params['payment_method'] ?? 'Cash',
                        'amount' => $paidAmount,
                        'paid_at' => $transactionDate,
                    ]);
            }

            $db->commit();

            AuditLogger::log('create', 'sale', $saleId, null, ['invoice_number' => $invoiceNumber, 'grand_total' => $grandTotal]);

            return [
                'sale_id' => $saleId,
                'invoice_number' => $invoiceNumber,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'duplicate' => false,
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
