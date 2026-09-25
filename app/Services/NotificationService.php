<?php

namespace App\Services;

use App\Core\Database;

/**
 * Notifications are synced (not cron-generated, §37/Phase1 scope) each time
 * a user loads the bell dropdown — existing unread notifications for the
 * same reference are never duplicated.
 */
class NotificationService
{
    public static function sync(int $userId): void
    {
        $db = Database::connection();

        foreach (DashboardService::lowStockWidget(50) as $item) {
            self::createIfMissing($db, $userId, 'low_stock', 'medicine', (int) $item['id'],
                'Stok Menipis',
                $item['name'] . ' tersisa ' . (int) $item['stock'] . ' (minimum ' . (int) $item['minimum_stock'] . ')'
            );
        }

        foreach (DashboardService::expiringSoonWidget(50) as $batch) {
            $status = ExpiryStatusService::statusFor($batch['expired_date']);
            if (!in_array($status['code'], ['critical', 'high'], true)) {
                continue;
            }
            self::createIfMissing($db, $userId, 'expiring_soon', 'medicine_batch', (int) $batch['id'],
                'Batch Akan Expired',
                $batch['medicine_name'] . ' (batch ' . $batch['batch_number'] . ') ' . strtolower($status['label'])
            );
        }
    }

    private static function createIfMissing(\PDO $db, int $userId, string $type, string $referenceType, int $referenceId, string $title, string $message): void
    {
        $stmt = $db->prepare(
            'SELECT id FROM notifications WHERE user_id = :user_id AND type = :type AND reference_type = :ref_type AND reference_id = :ref_id AND is_read = 0 LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'type' => $type, 'ref_type' => $referenceType, 'ref_id' => $referenceId]);
        if ($stmt->fetch()) {
            return;
        }

        $db->prepare(
            'INSERT INTO notifications (user_id, type, title, message, reference_type, reference_id, is_read, created_at)
             VALUES (:user_id, :type, :title, :message, :ref_type, :ref_id, 0, NOW())'
        )->execute([
            'user_id' => $userId, 'type' => $type, 'title' => $title, 'message' => $message,
            'ref_type' => $referenceType, 'ref_id' => $referenceId,
        ]);
    }

    public static function recentFor(int $userId, int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM notifications WHERE user_id = :user_id ORDER BY is_read ASC, created_at DESC LIMIT ' . (int) $limit
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) AS c FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetch()['c'];
    }

    public static function markRead(int $userId, int $notificationId): bool
    {
        $stmt = Database::connection()->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id');
        return $stmt->execute(['id' => $notificationId, 'user_id' => $userId]);
    }
}
