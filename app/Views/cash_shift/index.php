<?php
$methodLabels = [];
?>
<div class="page-header">
    <div>
        <p class="page-title">Shift Kasir</p>
        <p class="page-subtitle">Buka shift sebelum mulai transaksi di POS, tutup shift setelah selesai.</p>
    </div>
</div>

<?php if (!$shift): ?>
<div class="card-surface card-pad" style="max-width:480px;">
    <p class="section-card-title">Buka Shift Baru</p>
    <p class="section-card-subtitle">Masukkan jumlah kas awal di laci kasir.</p>
    <form method="POST" action="/shift/open" data-loading-text="Membuka shift...">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label">Kas Awal<span class="required">*</span></label>
            <input type="number" step="1" name="opening_balance" class="form-control" value="0" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Buka Shift &amp; Mulai Kasir</button>
    </form>
</div>
<?php else: ?>
<div class="chart-grid" style="grid-template-columns:1fr 1fr;">
    <div class="card-surface card-pad">
        <p class="section-card-title">Shift Berjalan — <?= e($shift['shift_number']) ?></p>
        <p class="section-card-subtitle">Dibuka <?= e(format_datetime($shift['opening_at'])) ?></p>
        <div style="display:flex;flex-direction:column;gap:8px;font-size:13.5px;">
            <div style="display:flex;justify-content:space-between;"><span>Kas Awal</span><strong><?= e(format_money($shift['opening_balance'])) ?></strong></div>
            <?php foreach ($summary['by_method'] as $m): ?>
                <div style="display:flex;justify-content:space-between;"><span><?= e($m['payment_method']) ?> (<?= (int) $m['trx'] ?> trx)</span><strong><?= e(format_money($m['total'])) ?></strong></div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;color:var(--color-danger);"><span>Retur (<?= $summary['refund_count'] ?>)</span><strong>-<?= e(format_money($summary['refund_total'])) ?></strong></div>
            <hr style="border-color:var(--color-border);">
            <div style="display:flex;justify-content:space-between;font-size:15px;"><span>Estimasi Kas Sistem</span><strong><?= e(format_money($systemBalance)) ?></strong></div>
        </div>
        <a href="/pos" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:16px;">Buka POS</a>
    </div>

    <div class="card-surface card-pad">
        <p class="section-card-title">Tutup Shift</p>
        <p class="section-card-subtitle">Hitung kas fisik di laci dan masukkan jumlahnya.</p>
        <form method="POST" action="/shift/<?= $shift['id'] ?>/close" data-confirm="Tutup shift ini? Pastikan penghitungan kas sudah benar." data-loading-text="Menutup shift...">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label">Kas Aktual (hasil hitung fisik)<span class="required">*</span></label>
                <input type="number" step="1" name="closing_balance_actual" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Opsional"></textarea>
            </div>
            <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;">Tutup Shift</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card-surface card-pad" style="margin-top:16px;">
    <p class="section-card-title">Riwayat Shift Saya</p>
    <?php if (empty($history)): ?>
        <div class="empty-state" style="padding:20px 8px;"><p>Belum ada riwayat shift.</p></div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>No. Shift</th><th>Buka</th><th>Tutup</th><th>Kas Awal</th><th>Sistem</th><th>Aktual</th><th>Selisih</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($history as $h): ?>
            <tr>
                <td style="font-weight:600;"><?= e($h['shift_number']) ?></td>
                <td class="table-cell-muted"><?= e(format_datetime($h['opening_at'])) ?></td>
                <td class="table-cell-muted"><?= $h['closing_at'] ? e(format_datetime($h['closing_at'])) : '-' ?></td>
                <td><?= e(format_money($h['opening_balance'])) ?></td>
                <td><?= $h['closing_balance_system'] !== null ? e(format_money($h['closing_balance_system'])) : '-' ?></td>
                <td><?= $h['closing_balance_actual'] !== null ? e(format_money($h['closing_balance_actual'])) : '-' ?></td>
                <td>
                    <?php if ($h['difference'] === null): ?>-
                    <?php elseif ((float) $h['difference'] == 0): ?><span class="badge badge-success">Sesuai</span>
                    <?php else: ?><span class="badge <?= $h['difference'] > 0 ? 'badge-info' : 'badge-danger' ?>"><?= e(format_money($h['difference'])) ?></span>
                    <?php endif; ?>
                </td>
                <td><span class="badge <?= $h['status'] === 'open' ? 'badge-warning' : 'badge-muted' ?>"><?= $h['status'] === 'open' ? 'Terbuka' : 'Tertutup' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
