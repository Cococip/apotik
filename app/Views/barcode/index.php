<?php
use App\Core\Auth;
?>
<div class="page-header">
    <div>
        <p class="page-title">Daftar Barcode</p>
        <p class="page-subtitle">Kelola barcode internal maupun barcode pabrik untuk setiap obat.</p>
    </div>
    <?php if (Auth::can('barcode.generate')): ?>
    <form method="POST" action="/barcode/generate-bulk" data-confirm="Buat barcode otomatis untuk semua obat yang belum memiliki barcode?">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-barcode"></use></svg> Generate Massal</button>
    </form>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <div class="table-toolbar">
        <form class="table-search" method="get" style="flex:1;">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-search"></use></svg>
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Cari nama, kode, atau barcode...">
        </form>
        <div style="display:flex;gap:8px;">
            <?php $filters = ['all' => 'Semua', 'assigned' => 'Sudah Ada', 'missing' => 'Belum Ada']; ?>
            <?php foreach ($filters as $key => $label): ?>
                <a href="?filter=<?= $key ?>&search=<?= e($search) ?>" class="btn btn-sm <?= $filter === $key ? 'btn-soft' : 'btn-outline' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-barcode"></use></svg></div>
            <h3>Tidak ada data</h3>
            <p>Coba ubah filter atau kata kunci pencarian.</p>
        </div>
    <?php else: ?>
        <?php
            $canGenerate = Auth::can('barcode.generate');
            $showActionColumn = false;
            if ($canGenerate) {
                foreach ($rows as $row) {
                    if (!$row['barcode']) { $showActionColumn = true; break; }
                }
            }
        ?>
        <?php /* Standalone (not wrapping the table) so per-row Generate <form>s below are never
                 nested inside it — nested <form> elements are invalid HTML and browsers silently
                 drop the inner ones, which made the per-row Generate button submit this form instead. */ ?>
        <form method="GET" action="/barcode/print" target="_blank" id="printSelectionForm"></form>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th style="width:36px;"><input type="checkbox" id="checkAll"></th><th>Kode</th><th>Nama Obat</th><th>Barcode</th><th>Harga</th><?php if ($showActionColumn): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="row-check" form="printSelectionForm" <?= !$row['barcode'] ? 'disabled' : '' ?>></td>
                    <td class="table-cell-muted"><?= e($row['code']) ?></td>
                    <td style="font-weight:600;"><?= e($row['name']) ?></td>
                    <td>
                        <?php if ($row['barcode']): ?>
                            <span class="badge badge-info" style="font-family:monospace;"><?= e($row['barcode']) ?></span>
                        <?php else: ?>
                            <span class="badge badge-muted">Belum ada</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-cell-muted"><?= e(format_money($row['selling_price'])) ?></td>
                    <?php if ($showActionColumn): ?>
                    <td class="table-actions">
                        <?php if (!$row['barcode'] && $canGenerate): ?>
                        <form method="POST" action="/barcode/generate/<?= $row['id'] ?>" data-confirm="Buat barcode internal untuk <?= e($row['name']) ?>?">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-soft">Generate</button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;">
            <?php render_pagination($pagination, '/barcode', ['search' => $search, 'filter' => $filter]); ?>
            <?php if (Auth::can('barcode.print')): ?>
            <button type="submit" form="printSelectionForm" class="btn btn-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-printer"></use></svg> Cetak Label Terpilih</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('checkAll')?.addEventListener('change', function () {
    document.querySelectorAll('.row-check:not(:disabled)').forEach(function (cb) { cb.checked = this.checked; }.bind(this));
});
</script>
