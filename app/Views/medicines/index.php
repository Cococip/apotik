<?php
use App\Core\Auth;
?>
<div class="page-header">
    <div>
        <p class="page-title">Daftar Obat</p>
        <p class="page-subtitle">Master data obat beserta stok terkini.</p>
    </div>
    <?php if (Auth::can('medicine.create')): ?>
    <a href="/medicines/create" class="btn btn-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Tambah Obat</a>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <div class="table-toolbar">
        <form class="table-search" method="get" style="flex:1;">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-search"></use></svg>
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Cari nama, kode, generik, atau barcode...">
        </form>
        <form method="get" style="display:flex;gap:8px;">
            <input type="hidden" name="search" value="<?= e($search) ?>">
            <select name="category_id" class="form-select" onchange="this.form.submit()" style="width:auto;">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-select" onchange="this.form.submit()" style="width:auto;">
                <option value="">Semua Status</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </form>
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-pill"></use></svg></div>
            <h3>Belum ada data obat</h3>
            <p>Tambahkan obat pertama untuk mulai mengelola persediaan apotek.</p>
            <?php if (Auth::can('medicine.create')): ?>
            <a href="/medicines/create" class="btn btn-primary">+ Tambah Obat</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Kode</th><th>Nama Obat</th><th>Kategori</th><th>Satuan</th><th>Harga Jual</th><th>Stok</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                    $stock = (float) $row['stock'];
                    if ($stock <= 0) { $stockBadge = 'badge-danger'; $stockLabel = 'Habis'; }
                    elseif ($stock <= $row['minimum_stock']) { $stockBadge = 'badge-warning'; $stockLabel = 'Menipis'; }
                    else { $stockBadge = 'badge-success'; $stockLabel = 'Aman'; }
                ?>
                <tr>
                    <td class="table-cell-muted"><?= e($row['code']) ?></td>
                    <td>
                        <a href="/medicines/<?= $row['id'] ?>/edit" style="font-weight:600;color:var(--color-ink);"><?= e($row['name']) ?></a>
                        <?php if ($row['requires_prescription']): ?><span class="badge badge-warning-soft" style="margin-left:6px;">Resep</span><?php endif; ?>
                        <div class="table-cell-muted"><?= e($row['generic_name'] ?: '') ?></div>
                    </td>
                    <td class="table-cell-muted"><?= e($row['category_name'] ?: '-') ?></td>
                    <td class="table-cell-muted"><?= e($row['unit_symbol'] ?: '-') ?></td>
                    <td><?= e(format_money($row['selling_price'])) ?></td>
                    <td>
                        <span class="badge <?= $stockBadge ?>"><?= (int) $stock ?> · <?= $stockLabel ?></span>
                    </td>
                    <td><span class="badge <?= $row['status'] === 'active' ? 'badge-success' : 'badge-muted' ?>"><?= $row['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td class="table-actions">
                        <?php if (Auth::can('medicine.update')): ?>
                        <a href="/medicines/<?= $row['id'] ?>/edit" class="btn btn-icon btn-ghost"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-edit"></use></svg></a>
                        <?php endif; ?>
                        <?php if (Auth::can('medicine.delete')): ?>
                        <form method="POST" action="/medicines/<?= $row['id'] ?>" data-confirm="Nonaktifkan obat ini?" style="display:inline;">
                            <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-icon btn-ghost"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-trash"></use></svg></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php render_pagination($pagination, '/medicines', ['search' => $search, 'category_id' => $categoryId, 'status' => $status]); ?>
    <?php endif; ?>
</div>
