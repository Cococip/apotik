<?php

namespace App\Services;

use App\Core\Database;

class CashShiftService
{
    public static function currentOpenShift(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM cash_shifts WHERE cashier_id = :id AND status = 'open' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function anyOpenShift(): ?array
    {
        $stmt = Database::connection()->query(
            "SELECT cs.*, u.full_name AS cashier_name FROM cash_shifts cs JOIN users u ON u.id = cs.cashier_id WHERE cs.status = 'open' ORDER BY cs.id DESC LIMIT 1"
        );
        return $stmt->fetch() ?: null;
    }

    public static function systemBalance(int $shiftId): float
    {
        $db = Database::connection();

        $shift = $db->prepare('SELECT opening_balance FROM cash_shifts WHERE id = :id');
        $shift->execute(['id' => $shiftId]);
        $opening = (float) ($shift->fetch()['opening_balance'] ?? 0);

        $cashSales = $db->prepare(
            "SELECT COALESCE(SUM(paid_amount), 0) AS total FROM sales WHERE shift_id = :id AND payment_method = 'Cash' AND status = 'completed'"
        );
        $cashSales->execute(['id' => $shiftId]);
        $salesTotal = (float) $cashSales->fetch()['total'];

        $cashIn = $db->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM cash_transactions WHERE shift_id = :id AND type = 'cash_in'");
        $cashIn->execute(['id' => $shiftId]);
        $cashInTotal = (float) $cashIn->fetch()['total'];

        $cashOut = $db->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM cash_transactions WHERE shift_id = :id AND type = 'cash_out'");
        $cashOut->execute(['id' => $shiftId]);
        $cashOutTotal = (float) $cashOut->fetch()['total'];

        return $opening + $salesTotal + $cashInTotal - $cashOutTotal;
    }

    public static function summary(int $shiftId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT payment_method, COUNT(*) AS trx, COALESCE(SUM(grand_total),0) AS total
             FROM sales WHERE shift_id = :id AND status = 'completed' GROUP BY payment_method"
        );
        $stmt->execute(['id' => $shiftId]);
        $byMethod = $stmt->fetchAll();

        $refundStmt = $db->prepare(
            "SELECT COUNT(*) AS c, COALESCE(SUM(total),0) AS total FROM sale_returns sr
             JOIN sales s ON s.id = sr.sale_id WHERE s.shift_id = :id"
        );
        $refundStmt->execute(['id' => $shiftId]);
        $refund = $refundStmt->fetch();

        return [
            'by_method' => $byMethod,
            'refund_count' => (int) $refund['c'],
            'refund_total' => (float) $refund['total'],
        ];
    }
}
