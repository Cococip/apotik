<?php

namespace App\Services;

use App\Core\Database;

class DashboardService
{
    public static function stats(): array
    {
        $db = Database::connection();

        $todaySales = $db->query(
            "SELECT COALESCE(SUM(grand_total),0) AS total, COUNT(*) AS trx
             FROM sales WHERE DATE(transaction_date) = CURDATE() AND status = 'completed'"
        )->fetch();

        $itemsSold = $db->query(
            "SELECT COALESCE(SUM(sd.qty),0) AS total FROM sale_details sd
             JOIN sales s ON s.id = sd.sale_id
             WHERE DATE(s.transaction_date) = CURDATE() AND s.status = 'completed'"
        )->fetch();

        $cost = $db->query(
            "SELECT COALESCE(SUM(sd.qty * mb.purchase_price),0) AS total
             FROM sale_details sd
             JOIN sales s ON s.id = sd.sale_id
             LEFT JOIN medicine_batches mb ON mb.id = sd.batch_id
             WHERE DATE(s.transaction_date) = CURDATE() AND s.status = 'completed'"
        )->fetch();

        $lowStock = $db->query(
            "SELECT COUNT(*) AS total FROM (
                SELECT m.id, m.minimum_stock, COALESCE(SUM(mb.available_qty),0) AS stock
                FROM medicines m
                LEFT JOIN medicine_batches mb ON mb.medicine_id = m.id AND mb.status = 'active'
                WHERE m.deleted_at IS NULL AND m.status = 'active'
                GROUP BY m.id
                HAVING stock <= m.minimum_stock
             ) t"
        )->fetch();

        $expiryWatch = (int) SettingsService::get('inventory.expiry_watch_days', 90);
        $expiringSoon = $db->prepare(
            "SELECT COUNT(*) AS total FROM medicine_batches
             WHERE status = 'active' AND available_qty > 0
             AND expired_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)"
        );
        $expiringSoon->execute(['days' => $expiryWatch]);
        $expiringSoon = $expiringSoon->fetch();

        $hutang = $db->query(
            "SELECT COALESCE(SUM(p.total - COALESCE(sp.paid, 0)), 0) AS total
             FROM purchases p
             LEFT JOIN (SELECT purchase_id, SUM(amount) AS paid FROM supplier_payments GROUP BY purchase_id) sp ON sp.purchase_id = p.id
             WHERE p.payment_status != 'paid'"
        )->fetch();

        return [
            'sales_today' => (float) $todaySales['total'],
            'transactions_today' => (int) $todaySales['trx'],
            'items_sold_today' => (float) $itemsSold['total'],
            'low_stock_count' => (int) $lowStock['total'],
            'expiring_soon_count' => (int) $expiringSoon['total'],
            'total_debt' => (float) ($hutang['total'] ?? 0),
            'total_receivable' => 0.0, // credit sales not enabled in Phase 1
            'estimated_profit_today' => (float) $todaySales['total'] - (float) $cost['total'],
        ];
    }

    public static function lowStockWidget(int $limit = 8): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT m.id, m.name, m.minimum_stock, COALESCE(SUM(mb.available_qty),0) AS stock
             FROM medicines m
             LEFT JOIN medicine_batches mb ON mb.medicine_id = m.id AND mb.status = 'active'
             WHERE m.deleted_at IS NULL AND m.status = 'active'
             GROUP BY m.id
             HAVING stock <= m.minimum_stock
             ORDER BY stock ASC
             LIMIT " . (int) $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function expiringSoonWidget(int $limit = 8): array
    {
        $days = (int) SettingsService::get('inventory.expiry_watch_days', 90);
        $stmt = Database::connection()->prepare(
            "SELECT mb.id, mb.batch_number, mb.expired_date, mb.available_qty, m.name AS medicine_name
             FROM medicine_batches mb
             JOIN medicines m ON m.id = mb.medicine_id
             WHERE mb.status = 'active' AND mb.available_qty > 0
             AND mb.expired_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
             ORDER BY mb.expired_date ASC
             LIMIT " . (int) $limit
        );
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }

    public static function recentTransactions(int $limit = 8): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT s.invoice_number, s.transaction_date, s.grand_total, s.status, s.payment_method, u.full_name AS cashier_name
             FROM sales s JOIN users u ON u.id = s.cashier_id
             ORDER BY s.transaction_date DESC, s.id DESC
             LIMIT " . (int) $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function salesSeries(int $days): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT DATE(transaction_date) AS d, COALESCE(SUM(grand_total),0) AS total
             FROM sales
             WHERE status = 'completed' AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
             GROUP BY DATE(transaction_date)"
        );
        $stmt->execute(['days' => $days - 1]);
        $rows = array_column($stmt->fetchAll(), 'total', 'd');

        $labels = [];
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('d/m', strtotime($date));
            $data[] = isset($rows[$date]) ? (float) $rows[$date] : 0.0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public static function topProducts(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT m.name, SUM(sd.qty) AS qty
             FROM sale_details sd
             JOIN sales s ON s.id = sd.sale_id
             JOIN medicines m ON m.id = sd.medicine_id
             WHERE s.status = 'completed' AND s.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY m.id, m.name
             ORDER BY qty DESC
             LIMIT " . (int) $limit
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return ['labels' => array_column($rows, 'name'), 'data' => array_map('floatval', array_column($rows, 'qty'))];
    }

    public static function topCategories(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(c.name, 'Tanpa Kategori') AS name, SUM(sd.subtotal) AS revenue
             FROM sale_details sd
             JOIN sales s ON s.id = sd.sale_id
             JOIN medicines m ON m.id = sd.medicine_id
             LEFT JOIN categories c ON c.id = m.category_id
             WHERE s.status = 'completed' AND s.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY c.id, name
             ORDER BY revenue DESC
             LIMIT " . (int) $limit
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return ['labels' => array_column($rows, 'name'), 'data' => array_map('floatval', array_column($rows, 'revenue'))];
    }
}
