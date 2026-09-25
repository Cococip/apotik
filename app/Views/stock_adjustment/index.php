<?php
use App\Core\Auth;
?>
<div class="page-header">
    <div>
        <p class="page-title">Penyesuaian Stok</p>
        <p class="page-subtitle">Koreksi manual stok batch di luar sesi opname (contoh: kesalahan input, kehilangan).</p>
    </div>
</div>

<div class="chart-grid" style="grid-template-columns:1fr 1.3fr;">
    <?php if (Auth::can('inventory.adjust')): ?>
    <div class="card-surface card-pad">
        <p class="section-card-title">Buat Penyesuaian</p>
        <form method="POST" action="/stock/adjustment" data-loading-text="Menyimpan...">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label">Obat<span class="required">*</span></label>
                <select id="adjMedicine" class="form-select" required>
                    <option value="">Pilih obat...</option>
                    <?php foreach ($medicines as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['name']) ?> (<?= e($m['code']) ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Batch<span class="required">*</span></label>
                <select name="batch_id" id="adjBatch" class="form-select" required>
                    <option value="">Pilih obat terlebih dahulu</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Arah<span class="required">*</span></label>
                    <select name="direction" class="form-select" required>
                        <option value="in">Tambah (+)</option>
                        <option value="out">Kurangi (-)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Qty<span class="required">*</span></label>
                    <input type="number" step="0.01" name="qty" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Alasan<span class="required">*</span></label>
                <textarea name="reason" class="form-control" rows="2" required placeholder="Contoh: koreksi input awal, barang hilang, dsb."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Simpan Penyesuaian</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="card-surface card-pad">
        <p class="section-card-title">Riwayat Penyesuaian Terakhir</p>
        <?php if (empty($rows)): ?>
            <div class="empty-state" style="padding:24px 8px;"><p>Belum ada penyesuaian manual.</p></div>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Tanggal</th><th>Obat</th><th>Batch</th><th>Perubahan</th><th>Alasan</th><th>User</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="table-cell-muted"><?= e(format_datetime($row['created_at'])) ?></td>
                    <td style="font-weight:600;"><?= e($row['medicine_name']) ?></td>
                    <td class="table-cell-muted"><?= e($row['batch_number']) ?></td>
                    <td><?= $row['qty_in'] > 0 ? '+' . (int) $row['qty_in'] : '-' . (int) $row['qty_out'] ?></td>
                    <td class="table-cell-muted"><?= e($row['note']) ?></td>
                    <td class="table-cell-muted"><?= e($row['user_name'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('adjMedicine')?.addEventListener('change', function () {
    var batchSelect = document.getElementById('adjBatch');
    batchSelect.innerHTML = '<option value="">Memuat...</option>';
    if (!this.value) {
        batchSelect.innerHTML = '<option value="">Pilih obat terlebih dahulu</option>';
        return;
    }
    fetch('/api/medicines/' + this.value + '/batches')
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var batches = res.data || [];
            if (!batches.length) {
                batchSelect.innerHTML = '<option value="">Tidak ada batch aktif</option>';
                return;
            }
            batchSelect.innerHTML = batches.map(function (b) {
                return '<option value="' + b.id + '">' + b.batch_number + ' — stok ' + Math.trunc(b.available_qty) + ' (exp ' + b.expired_date + ')</option>';
            }).join('');
        });
});
</script>
