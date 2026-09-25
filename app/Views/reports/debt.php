<?php use App\Core\Auth; ?>
<div class="breadcrumb-trail no-print"><a href="/reports">Laporan</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Hutang</div>
<div class="page-header no-print">
    <div><p class="page-title">Laporan Hutang</p><p class="page-subtitle">Sisa hutang ke supplier yang belum lunas.</p></div>
    <div style="display:flex;gap:8px;">
        <?php if (Auth::can('report.export')): ?>
        <a href="?export=csv" class="btn btn-soft"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-download"></use></svg> Export CSV</a>
        <?php endif; ?>
        <button type="button" class="btn btn-ghost" onclick="window.print()">Print</button>
    </div>
</div>

<div class="stat-grid" style="grid-template-columns:1fr;margin-bottom:16px;">
    <div class="stat-card"><span class="stat-label">Total Hutang Tersisa</span><span class="stat-value"><?= e(format_money($total)) ?></span></div>
</div>

<div class="card-surface card-pad">
    <?php if (empty($rows)): ?>
        <div class="empty-state"><p>Tidak ada hutang tersisa.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>No. Pembelian</th><th>Supplier</th><th>Total</th><th>Dibayar</th><th>Sisa</th><th>Jatuh Tempo</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td style="font-weight:600;"><?= e($row['purchase_number']) ?></td>
                <td><?= e($row['supplier_name']) ?></td>
                <td><?= e(format_money($row['total'])) ?></td>
                <td><?= e(format_money($row['paid_total'])) ?></td>
                <td style="color:var(--color-danger);font-weight:700;"><?= e(format_money($row['total'] - $row['paid_total'])) ?></td>
                <td class="table-cell-muted"><?= $row['due_date'] ? e(format_date($row['due_date'])) : '-' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
