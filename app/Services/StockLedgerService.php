<?php

namespace App\Services;

use App\Core\Database;

/**
 * Single write path for every stock change (§8). Never call
 * `UPDATE medicine_batches SET available_qty = ...` directly outside
 * this service — every change must leave a stock_movements row so the
 * Kartu Stok stays accurate. Callers are responsible for wrapping
 * multi-line operations (stock opname, purchase receiving) in a DB
 * transaction; single-call sites can use moveTransactional().
 */
class StockLedgerService
{
    public static function move(
        int $medicineId,
        ?int $batchId,
        string $movementType,
        float $qtyIn,
        float $qtyOut,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null,
        ?int $userId = null
    ): int {
        $db = Database::connection();

        if ($batchId !== null) {
            $delta = $qtyIn - $qtyOut;
            $db->prepare('UPDATE medicine_batches SET available_qty = available_qty + :delta WHERE id = :id')
                ->execute(['delta' => $delta, 'id' => $batchId]);

            $db->prepare(
                "UPDATE medicine_batches SET status = CASE WHEN available_qty <= 0 THEN 'depleted' WHEN status = 'depleted' AND available_qty > 0 THEN 'active' ELSE status END WHERE id = :id"
            )->execute(['id' => $batchId]);
        }

        $balance = self::currentBalance($medicineId);

        $stmt = $db->prepare(
            'INSERT INTO stock_movements (medicine_id, batch_id, movement_type, reference_type, reference_id, qty_in, qty_out, balance_after, note, user_id, created_at)
             VALUES (:medicine_id, :batch_id, :movement_type, :reference_type, :reference_id, :qty_in, :qty_out, :balance_after, :note, :user_id, NOW())'
        );
        $stmt->execute([
            'medicine_id' => $medicineId,
            'batch_id' => $batchId,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'qty_in' => $qtyIn,
            'qty_out' => $qtyOut,
            'balance_after' => $balance,
            'note' => $note,
            'user_id' => $userId,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function moveTransactional(
        int $medicineId,
        ?int $batchId,
        string $movementType,
        float $qtyIn,
        float $qtyOut,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null,
        ?int $userId = null
    ): int {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $id = self::move($medicineId, $batchId, $movementType, $qtyIn, $qtyOut, $referenceType, $referenceId, $note, $userId);
            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function currentBalance(int $medicineId): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(available_qty), 0) AS total FROM medicine_batches WHERE medicine_id = :id AND status = 'active'"
        );
        $stmt->execute(['id' => $medicineId]);
        return (float) $stmt->fetch()['total'];
    }

    public static function ledgerFor(int $medicineId, int $limit = 200): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT sm.*, mb.batch_number, u.full_name AS user_name
             FROM stock_movements sm
             LEFT JOIN medicine_batches mb ON mb.id = sm.batch_id
             LEFT JOIN users u ON u.id = sm.user_id
             WHERE sm.medicine_id = :id
             ORDER BY sm.created_at DESC, sm.id DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute(['id' => $medicineId]);
        return $stmt->fetchAll();
    }
}
