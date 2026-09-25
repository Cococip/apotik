<?php

namespace App\Services;

use App\Core\Database;

/**
 * Processes queued offline transactions (§11/§12/§13/§53 #9-#11). The
 * server is the single source of truth for stock — SalesService::checkout()
 * re-derives FEFO batch allocation from the live database, it never trusts
 * qty/batch numbers the offline client cached. A sale that can no longer be
 * fulfilled (stock sold out from under it while offline) is marked
 * "conflict", never silently dropped or silently overwritten.
 */
class SyncService
{
    public static function registerDevice(string $deviceId, ?string $deviceName, ?int $userId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM devices WHERE device_id = :device_id');
        $stmt->execute(['device_id' => $deviceId]);

        if ($stmt->fetch()) {
            $db->prepare('UPDATE devices SET last_seen_at = NOW(), device_name = COALESCE(:name, device_name), user_id = :user_id WHERE device_id = :device_id')
                ->execute(['name' => $deviceName, 'user_id' => $userId, 'device_id' => $deviceId]);
            return;
        }

        $db->prepare('INSERT INTO devices (device_id, device_name, user_id, last_seen_at) VALUES (:device_id, :name, :user_id, NOW())')
            ->execute(['device_id' => $deviceId, 'name' => $deviceName, 'user_id' => $userId]);
    }

    /**
     * @param array $item {uuid, device_id, payload: {items, payment_method, paid_amount, discount, customer_id, doctor_id, transaction_date, shift_id, cashier_id}}
     */
    public static function processSale(array $item): array
    {
        $db = Database::connection();
        $uuid = $item['uuid'];
        $payload = $item['payload'];

        $queueStmt = $db->prepare('SELECT id, status FROM sync_queue WHERE uuid = :uuid');
        $queueStmt->execute(['uuid' => $uuid]);
        $existingQueue = $queueStmt->fetch();

        if ($existingQueue && $existingQueue['status'] === 'synced') {
            return ['uuid' => $uuid, 'status' => 'synced', 'message' => 'Sudah tersinkronisasi sebelumnya.'];
        }

        if ($existingQueue) {
            $queueId = (int) $existingQueue['id'];
            $db->prepare('UPDATE sync_queue SET payload = :payload, retry_count = retry_count + 1 WHERE id = :id')
                ->execute(['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE), 'id' => $queueId]);
        } else {
            $db->prepare('INSERT INTO sync_queue (uuid, device_id, type, payload, status) VALUES (:uuid, :device_id, "sale", :payload, "pending")')
                ->execute(['uuid' => $uuid, 'device_id' => $item['device_id'], 'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
            $queueId = (int) $db->lastInsertId();
        }

        try {
            $result = SalesService::checkout(array_merge($payload, [
                'uuid' => $uuid,
                'device_id' => $item['device_id'],
                'sync_status' => 'synced',
            ]));

            $db->prepare("UPDATE sync_queue SET status = 'synced', processed_at = NOW(), error_message = NULL WHERE id = :id")->execute(['id' => $queueId]);
            self::log($queueId, 'synced', $result['duplicate'] ? 'Idempotent: sudah ada.' : 'Berhasil diproses.');

            return ['uuid' => $uuid, 'status' => 'synced', 'sale_id' => $result['sale_id'], 'invoice_number' => $result['invoice_number']];
        } catch (CheckoutException $e) {
            // Stock/business-rule conflict — surface to admin, don't silently drop (§13).
            $db->prepare("UPDATE sync_queue SET status = 'conflict', error_message = :msg WHERE id = :id")
                ->execute(['msg' => $e->getMessage(), 'id' => $queueId]);
            self::log($queueId, 'conflict', $e->getMessage());

            return ['uuid' => $uuid, 'status' => 'conflict', 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Sync processSale failed: ' . $e->getMessage());
            $db->prepare("UPDATE sync_queue SET status = 'failed', error_message = :msg WHERE id = :id")
                ->execute(['msg' => 'Kesalahan sistem saat sinkronisasi.', 'id' => $queueId]);
            self::log($queueId, 'failed', $e->getMessage());

            return ['uuid' => $uuid, 'status' => 'failed', 'message' => 'Terjadi kesalahan sistem saat sinkronisasi.'];
        }
    }

    private static function log(int $queueId, string $status, string $message): void
    {
        Database::connection()
            ->prepare('INSERT INTO sync_logs (sync_queue_id, status, message) VALUES (:id, :status, :message)')
            ->execute(['id' => $queueId, 'status' => $status, 'message' => $message]);
    }

    public static function retry(int $queueId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM sync_queue WHERE id = :id');
        $stmt->execute(['id' => $queueId]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['status' => 'failed', 'message' => 'Antrean sinkronisasi tidak ditemukan.'];
        }

        return self::processSale([
            'uuid' => $row['uuid'],
            'device_id' => $row['device_id'],
            'payload' => json_decode($row['payload'], true),
        ]);
    }
}
