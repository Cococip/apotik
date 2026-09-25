<?php use App\Core\Auth; ?>
<div class="breadcrumb-trail no-print"><a href="/reports">Laporan</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Stok</div>
<div class="page-header no-print">
    <div><p class="page-title">Laporan Stok</p><p class="page-subtitle">Snapshot stok seluruh obat saat ini.</p></div>
    <div style="display:flex;gap:8px;">
        <?php if (Auth::can('report.export')): ?>
        <a href="?export=csv" class="btn btn-soft"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-download"></use></svg> Export CSV</a>
        <?php endif; ?>
        <button type="button" class="btn btn-ghost" onclick="window.print()">Print</button>
    </div>
</div>

<div class="card-surface card-pad">
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Kode</th><th>Nama Obat</th><th>Kategori</th><th>Stok</th><th>Min</th><th>Maks</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
                if ($row['stock'] <= 0) { $b='badge-danger'; $l='Habis'; }
                elseif ($row['stock'] <= $row['minimum_stock']) { $b='badge-warning'; $l='Menipis'; }
                else { $b='badge-success'; $l='Aman'; }
            ?>
            <tr>
                <td class="table-cell-muted"><?= e($row['code']) ?></td>
                <td style="font-weight:600;"><?= e($row['name']) ?></td>
                <td class="table-cell-muted"><?= e($row['category_name'] ?: '-') ?></td>
                <td><?= (int) $row['stock'] ?> <?= e($row['symbol'] ?: '') ?></td>
                <td class="table-cell-muted"><?= (int) $row['minimum_stock'] ?></td>
                <td class="table-cell-muted"><?= (int) $row['maximum_stock'] ?></td>
                <td><span class="badge <?= $b ?>"><?= $l ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
