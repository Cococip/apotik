<?php use App\Core\Auth; ?>
<div class="breadcrumb-trail no-print"><a href="/reports">Laporan</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Laba</div>
<div class="page-header no-print">
    <div><p class="page-title">Laporan Laba</p><p class="page-subtitle">Periode <?= e(format_date($dateFrom)) ?> — <?= e(format_date($dateTo)) ?></p></div>
</div>

<div class="card-surface card-pad no-print" style="margin-bottom:16px;">
    <form method="get" style="display:flex;gap:8px;align-items:end;">
        <div class="form-group" style="margin-bottom:0;"><label class="form-label">Dari</label><input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="form-control"></div>
        <div class="form-group" style="margin-bottom:0;"><label class="form-label">Sampai</label><input type="date" name="date_to" value="<?= e($dateTo) ?>" class="form-control"></div>
        <button type="submit" class="btn btn-outline">Terapkan</button>
        <?php if (Auth::can('report.export')): ?>
        <a href="?date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>&export=csv" class="btn btn-soft"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-download"></use></svg> Export CSV</a>
        <?php endif; ?>
        <button type="button" class="btn btn-ghost" onclick="window.print()">Print</button>
    </form>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card"><span class="stat-label">Omzet</span><span class="stat-value"><?= e(format_money($totals['revenue'])) ?></span></div>
    <div class="stat-card"><span class="stat-label">Modal (HPP)</span><span class="stat-value"><?= e(format_money($totals['cogs'])) ?></span></div>
    <div class="stat-card"><span class="stat-label">Laba Kotor</span><span class="stat-value"><?= e(format_money($totals['profit'])) ?></span></div>
</div>

<div class="card-surface card-pad">
    <?php if (empty($rows)): ?>
        <div class="empty-state"><p>Tidak ada data pada periode ini.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Tanggal</th><th>Omzet</th><th>Modal</th><th>Laba Kotor</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td class="table-cell-muted"><?= e(format_date($row['d'])) ?></td>
                <td><?= e(format_money($row['revenue'])) ?></td>
                <td><?= e(format_money($row['cogs'])) ?></td>
                <td style="font-weight:700;"><?= e(format_money($row['profit'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
