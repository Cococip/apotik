<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\SyncService;

class SyncController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::connection();

        $counts = $db->query(
            "SELECT status, COUNT(*) AS c FROM sync_queue GROUP BY status"
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        $lastSync = $db->query("SELECT MAX(processed_at) AS t FROM sync_queue WHERE status = 'synced'")->fetch()['t'];

        $items = $db->query(
            "SELECT sq.*, d.device_name FROM sync_queue sq LEFT JOIN devices d ON d.device_id = sq.device_id
             WHERE sq.status IN ('failed','conflict') ORDER BY sq.created_at DESC LIMIT 50"
        )->fetchAll();

        $devices = $db->query(
            "SELECT dv.*, u.full_name AS user_name FROM devices dv LEFT JOIN users u ON u.id = dv.user_id ORDER BY dv.last_seen_at DESC LIMIT 20"
        )->fetchAll();

        $this->view('sync.index', [
            'title' => 'Sinkronisasi',
            'counts' => [
                'pending' => (int) ($counts['pending'] ?? 0),
                'synced' => (int) ($counts['synced'] ?? 0),
                'failed' => (int) ($counts['failed'] ?? 0),
                'conflict' => (int) ($counts['conflict'] ?? 0),
            ],
            'lastSync' => $lastSync,
            'items' => $items,
            'devices' => $devices,
        ]);
    }

    public function retry(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $result = SyncService::retry((int) $id);
        if ($result['status'] === 'synced') {
            Session::flash('success', 'Sinkronisasi berhasil diproses ulang.');
        } else {
            Session::flash('error', 'Masih gagal: ' . ($result['message'] ?? 'Tidak diketahui.'));
        }

        $this->redirect('/sync');
    }
}
