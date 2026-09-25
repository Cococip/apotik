<?php
use App\Core\Auth;
?>
<div class="page-header">
    <div>
        <p class="page-title">Stok Opname</p>
        <p class="page-subtitle">Riwayat sesi penghitungan fisik stok.</p>
    </div>
    <?php if (Auth::can('inventory.opname')): ?>
    <a href="/stock/opname/create" class="btn btn-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Buat Opname</a>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-clipboard-check"></use></svg></div>
            <h3>Belum ada sesi stok opname</h3>
            <p>Buat sesi opname pertama untuk mencocokkan stok sistem dengan stok fisik.</p>
            <a href="/stock/opname/create" class="btn btn-primary">+ Buat Opname</a>
        </div>
    <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>No. Opname</th><th>Tanggal</th><th>Item Diperiksa</th><th>Selisih</th><th>Petugas</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td style="font-weight:600;"><?= e($row['opname_number']) ?></td>
                    <td class="table-cell-muted"><?= e(format_date($row['opname_date'])) ?></td>
                    <td><?= (int) $row['total_items'] ?></td>
                    <td><span class="badge <?= $row['total_differences'] > 0 ? 'badge-warning' : 'badge-success' ?>"><?= (int) $row['total_differences'] ?> selisih</span></td>
                    <td class="table-cell-muted"><?= e($row['user_name'] ?: '-') ?></td>
                    <td class="table-actions"><a href="/stock/opname/<?= $row['id'] ?>" class="btn btn-sm btn-soft">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>
