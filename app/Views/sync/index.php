<div class="page-header">
    <div>
        <p class="page-title">Sinkronisasi</p>
        <p class="page-subtitle">Status antrean transaksi offline dari perangkat POS.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card"><span class="stat-label">Menunggu</span><span class="stat-value"><?= $counts['pending'] ?></span></div>
    <div class="stat-card"><span class="stat-label">Berhasil</span><span class="stat-value"><?= $counts['synced'] ?></span></div>
    <div class="stat-card"><span class="stat-label">Gagal</span><span class="stat-value"><?= $counts['failed'] ?></span></div>
    <div class="stat-card"><span class="stat-label">Konflik</span><span class="stat-value"><?= $counts['conflict'] ?></span></div>
</div>

<div class="card-surface card-pad" style="margin-bottom:16px;">
    <p class="section-card-title">Status Umum</p>
    <p class="table-cell-muted">Sinkronisasi terakhir berhasil: <strong><?= $lastSync ? e(format_datetime($lastSync)) : 'Belum pernah' ?></strong></p>
    <p class="table-cell-muted" style="margin-top:8px;">
        Mode transaksi offline aktif pada modul <strong>Kasir (POS)</strong> — saat koneksi terputus, transaksi tetap dapat
        dibuat dan disimpan sementara di perangkat, lalu otomatis dikirim ke server ketika koneksi kembali. Server selalu
        memvalidasi ulang stok (FEFO) sebelum transaksi offline disetujui — tidak pernah mempercayai stok cache dari klien.
    </p>
</div>

<div class="card-surface card-pad" style="margin-bottom:16px;">
    <p class="section-card-title">Transaksi Gagal / Konflik</p>
    <?php if (empty($items)): ?>
        <div class="empty-state" style="padding:24px 8px;"><p>Tidak ada transaksi yang gagal atau berkonflik.</p></div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>UUID</th><th>Perangkat</th><th>Status</th><th>Pesan</th><th>Percobaan</th><th>Waktu</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td class="table-cell-muted" style="font-family:monospace;font-size:11px;"><?= e(substr($item['uuid'], 0, 13)) ?>...</td>
                <td class="table-cell-muted"><?= e($item['device_name'] ?: $item['device_id']) ?></td>
                <td><span class="badge <?= $item['status'] === 'conflict' ? 'badge-warning' : 'badge-danger' ?>"><?= $item['status'] === 'conflict' ? 'Konflik' : 'Gagal' ?></span></td>
                <td class="table-cell-muted"><?= e($item['error_message'] ?: '-') ?></td>
                <td><?= (int) $item['retry_count'] ?></td>
                <td class="table-cell-muted"><?= e(format_datetime($item['created_at'])) ?></td>
                <td class="table-actions">
                    <form method="POST" action="/sync/<?= $item['id'] ?>/retry" data-confirm="Coba proses ulang transaksi ini?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-soft">Retry</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <p class="section-card-title">Perangkat Terdaftar</p>
    <?php if (empty($devices)): ?>
        <div class="empty-state" style="padding:24px 8px;"><p>Belum ada perangkat yang tersinkronisasi.</p></div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Device ID</th><th>Nama</th><th>User Terakhir</th><th>Terakhir Aktif</th></tr></thead>
        <tbody>
        <?php foreach ($devices as $d): ?>
            <tr>
                <td class="table-cell-muted" style="font-family:monospace;font-size:11px;"><?= e($d['device_id']) ?></td>
                <td style="font-weight:600;"><?= e($d['device_name'] ?: '-') ?></td>
                <td class="table-cell-muted"><?= e($d['user_name'] ?: '-') ?></td>
                <td class="table-cell-muted"><?= e(format_datetime($d['last_seen_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
