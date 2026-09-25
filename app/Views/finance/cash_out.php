<?php use App\Core\Auth; ?>
<div class="page-header">
    <div>
        <p class="page-title">Kas Keluar</p>
        <p class="page-subtitle">Pengeluaran kas di luar pembelian barang (mis. penarikan, transfer internal).</p>
    </div>
</div>

<div class="chart-grid" style="grid-template-columns:1fr 1.6fr;">
    <?php if (Auth::can('finance.manage')): ?>
    <div class="card-surface card-pad">
        <p class="section-card-title">Catat Kas Keluar</p>
        <form method="POST" action="/finance/cash-out" data-loading-text="Menyimpan...">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label">Kategori<span class="required">*</span></label>
                <input type="text" name="category" class="form-control" placeholder="Contoh: Setor ke Bank" required>
            </div>
            <div class="form-group">
                <label class="form-label">Jumlah<span class="required">*</span></label>
                <input type="number" step="1" name="amount" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Keterangan</label>
                <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;">Simpan</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="card-surface card-pad">
        <p class="section-card-title">Riwayat Kas Keluar</p>
        <?php if (empty($rows)): ?>
            <div class="empty-state" style="padding:24px 8px;"><p>Belum ada catatan kas keluar manual.</p></div>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th>Jumlah</th><th>User</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="table-cell-muted"><?= e(format_datetime($row['created_at'])) ?></td>
                    <td style="font-weight:600;"><?= e($row['category']) ?></td>
                    <td class="table-cell-muted"><?= e($row['description'] ?: '-') ?></td>
                    <td><?= e(format_money($row['amount'])) ?></td>
                    <td class="table-cell-muted"><?= e($row['user_name'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>
