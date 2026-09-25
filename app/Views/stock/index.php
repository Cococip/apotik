<?php
?>
<div class="page-header">
    <div>
        <p class="page-title">Stok</p>
        <p class="page-subtitle">Ringkasan stok tersedia per obat (hanya batch aktif, sudah dikurangi expired).</p>
    </div>
</div>

<div class="card-surface card-pad">
    <div class="table-toolbar">
        <form class="table-search" method="get" style="flex:1;">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-search"></use></svg>
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Cari nama/kode obat...">
        </form>
        <form method="get">
            <input type="hidden" name="search" value="<?= e($search) ?>">
            <select name="category_id" class="form-select" onchange="this.form.submit()" style="width:auto;">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty-state"><p>Tidak ada data obat aktif.</p></div>
    <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Kode</th><th>Nama Obat</th><th>Kategori</th><th>Stok Tersedia</th><th>Min</th><th>Maks</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                    $stock = (float) $row['stock'];
                    if ($stock <= 0) { $badge = 'badge-danger'; $label = 'Habis'; }
                    elseif ($stock <= $row['minimum_stock']) { $badge = 'badge-warning'; $label = 'Menipis'; }
                    elseif ($row['maximum_stock'] > 0 && $stock >= $row['maximum_stock']) { $badge = 'badge-info'; $label = 'Berlebih'; }
                    else { $badge = 'badge-success'; $label = 'Aman'; }
                ?>
                <tr>
                    <td class="table-cell-muted"><?= e($row['code']) ?></td>
                    <td style="font-weight:600;"><?= e($row['name']) ?></td>
                    <td class="table-cell-muted"><?= e($row['category_name'] ?: '-') ?></td>
                    <td><?= (int) $stock ?> <?= e($row['unit_symbol'] ?: '') ?></td>
                    <td class="table-cell-muted"><?= (int) $row['minimum_stock'] ?></td>
                    <td class="table-cell-muted"><?= (int) $row['maximum_stock'] ?></td>
                    <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                    <td class="table-actions">
                        <a href="/stock/card/<?= $row['id'] ?>" class="btn btn-sm btn-soft">Kartu Stok</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php render_pagination($pagination, '/stock', ['search' => $search, 'category_id' => $categoryId]); ?>
    <?php endif; ?>
</div>
