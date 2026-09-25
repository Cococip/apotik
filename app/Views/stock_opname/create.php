<div class="breadcrumb-trail"><a href="/stock/opname">Stok Opname</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Buat Baru</div>
<div class="page-header">
    <div>
        <p class="page-title">Buat Stok Opname</p>
        <p class="page-subtitle">Isi jumlah fisik hasil hitung. Kosongkan baris yang tidak dihitung — hanya baris terisi yang akan diproses.</p>
    </div>
</div>

<form method="POST" action="/stock/opname" data-loading-text="Menyimpan...">
    <?= csrf_field() ?>
    <div class="card-surface card-pad" style="margin-bottom:16px;">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tanggal Opname</label>
                <input type="date" name="opname_date" class="form-control" value="<?= e($today) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Catatan</label>
                <input type="text" name="note" class="form-control" placeholder="Opsional">
            </div>
        </div>
        <input type="text" id="opnameFilter" class="form-control" placeholder="Ketik untuk menyaring daftar obat..." style="margin-top:4px;">
    </div>

    <div class="card-surface card-pad">
        <?php if (empty($batches)): ?>
            <div class="empty-state"><p>Belum ada batch aktif untuk dihitung.</p></div>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table" id="opnameTable">
            <thead><tr><th>Obat</th><th>Batch</th><th>Expired</th><th>Stok Sistem</th><th style="width:160px;">Qty Fisik</th></tr></thead>
            <tbody>
            <?php foreach ($batches as $b): ?>
                <tr data-name="<?= e(strtolower($b['medicine_name'] . ' ' . $b['batch_number'])) ?>">
                    <td style="font-weight:600;"><?= e($b['medicine_name']) ?><div class="table-cell-muted"><?= e($b['medicine_code']) ?></div></td>
                    <td class="table-cell-muted"><?= e($b['batch_number']) ?></td>
                    <td class="table-cell-muted"><?= e(format_date($b['expired_date'])) ?></td>
                    <td><?= (int) $b['available_qty'] ?> <?= e($b['unit_symbol'] ?: '') ?></td>
                    <td><input type="number" step="0.01" name="physical_qty[<?= $b['batch_id'] ?>]" class="form-control" placeholder="-"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
            <a href="/stock/opname" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Stok Opname</button>
        </div>
        <?php endif; ?>
    </div>
</form>

<script>
document.getElementById('opnameFilter')?.addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#opnameTable tbody tr').forEach(function (tr) {
        tr.style.display = tr.getAttribute('data-name').indexOf(q) !== -1 ? '' : 'none';
    });
});
</script>
