<div class="page-header">
    <div>
        <p class="page-title">Backup Database</p>
        <p class="page-subtitle">Backup manual database apotek. Simpan file di lokasi aman secara berkala.</p>
    </div>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px;">
    <div class="stat-card"><span class="stat-label">Nama Database</span><span class="stat-value" style="font-size:16px;"><?= e($databaseName) ?></span></div>
    <div class="stat-card"><span class="stat-label">Jumlah Tabel</span><span class="stat-value"><?= (int) $tableCount ?></span></div>
    <div class="stat-card"><span class="stat-label">Ukuran Data</span><span class="stat-value"><?= e($sizeMb) ?> MB</span></div>
</div>

<div class="card-surface card-pad" style="margin-bottom:16px;">
    <p class="section-card-title">Buat Backup Baru</p>
    <p class="section-card-subtitle">Proses dapat memakan waktu beberapa detik tergantung ukuran database.</p>
    <form method="POST" action="/backup/create" data-loading-text="Membuat backup...">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-database"></use></svg> Backup Sekarang</button>
    </form>
</div>

<div class="card-surface card-pad">
    <p class="section-card-title">Daftar Backup</p>
    <?php if (empty($backups)): ?>
        <div class="empty-state" style="padding:24px 8px;"><p>Belum ada file backup.</p></div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Nama File</th><th>Ukuran</th><th>Dibuat</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($backups as $b): ?>
            <tr>
                <td style="font-weight:600;"><?= e($b['name']) ?></td>
                <td class="table-cell-muted"><?= number_format($b['size'] / 1024, 1) ?> KB</td>
                <td class="table-cell-muted"><?= e(date('d/m/Y H:i', $b['created_at'])) ?></td>
                <td class="table-actions"><a href="/backup/download/<?= e($b['name']) ?>" class="btn btn-sm btn-soft">Download</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
