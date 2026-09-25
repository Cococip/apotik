<?php use App\Core\Auth; ?>
<div class="breadcrumb-trail no-print"><a href="/reports">Laporan</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Keuangan</div>
<div class="page-header no-print">
    <div><p class="page-title">Laporan Keuangan</p><p class="page-subtitle">Periode <?= e(format_date($dateFrom)) ?> — <?= e(format_date($dateTo)) ?></p></div>
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

<div class="card-surface card-pad">
    <table class="data-table">
        <thead><tr><th>Kategori</th><th>Jumlah</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr><td style="font-weight:600;"><?= e($row['category']) ?></td><td><?= e(format_money($row['amount'])) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
