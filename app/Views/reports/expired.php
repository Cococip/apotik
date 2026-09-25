<?php use App\Core\Auth; ?>
<div class="breadcrumb-trail no-print"><a href="/reports">Laporan</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Expired</div>
<div class="page-header no-print">
    <div><p class="page-title">Laporan Expired</p><p class="page-subtitle">Seluruh batch aktif diurutkan berdasarkan tanggal expired (FEFO).</p></div>
    <div style="display:flex;gap:8px;">
        <?php if (Auth::can('report.export')): ?>
        <a href="?export=csv" class="btn btn-soft"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-download"></use></svg> Export CSV</a>
        <?php endif; ?>
        <button type="button" class="btn btn-ghost" onclick="window.print()">Print</button>
    </div>
</div>

<div class="card-surface card-pad">
    <?php if (empty($rows)): ?>
        <div class="empty-state"><p>Tidak ada batch aktif.</p></div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Kode</th><th>Obat</th><th>Batch</th><th>Expired</th><th>Qty</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td class="table-cell-muted"><?= e($row['code']) ?></td>
                <td style="font-weight:600;"><?= e($row['medicine_name']) ?></td>
                <td class="table-cell-muted"><?= e($row['batch_number']) ?></td>
                <td class="table-cell-muted"><?= e(format_date($row['expired_date'])) ?></td>
                <td><?= (int) $row['available_qty'] ?></td>
                <td><span class="badge <?= e($row['expiry_status']['badge']) ?>"><?= e($row['expiry_status']['label']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
