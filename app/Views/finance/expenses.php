<?php use App\Core\Auth; ?>
<div class="page-header">
    <div>
        <p class="page-title">Pengeluaran</p>
        <p class="page-subtitle">Biaya operasional apotek (listrik, sewa, gaji, dll).</p>
    </div>
</div>

<div class="chart-grid" style="grid-template-columns:1fr 1.6fr;">
    <?php if (Auth::can('finance.manage')): ?>
    <div class="card-surface card-pad">
        <p class="section-card-title">Catat Pengeluaran</p>
        <form method="POST" action="/finance/expenses" data-loading-text="Menyimpan...">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label">Kategori<span class="required">*</span></label>
                <input type="text" name="category" class="form-control" placeholder="Contoh: Listrik" required>
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal</label>
                <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Jumlah<span class="required">*</span></label>
                <input type="number" step="1" name="amount" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Keterangan</label>
                <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Simpan</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="card-surface card-pad">
        <p class="section-card-title">Riwayat Pengeluaran</p>
        <?php if (empty($rows)): ?>
            <div class="empty-state" style="padding:24px 8px;"><p>Belum ada catatan pengeluaran.</p></div>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>No.</th><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th>Jumlah</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="table-cell-muted"><?= e($row['expense_number']) ?></td>
                    <td class="table-cell-muted"><?= e(format_date($row['expense_date'])) ?></td>
                    <td style="font-weight:600;"><?= e($row['category']) ?></td>
                    <td class="table-cell-muted"><?= e($row['description'] ?: '-') ?></td>
                    <td><?= e(format_money($row['amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>
