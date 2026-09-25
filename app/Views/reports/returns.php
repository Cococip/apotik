<div class="breadcrumb-trail no-print"><a href="/reports">Laporan</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Retur</div>
<div class="page-header no-print">
    <div><p class="page-title">Laporan Retur</p><p class="page-subtitle">Periode <?= e(format_date($dateFrom)) ?> — <?= e(format_date($dateTo)) ?></p></div>
</div>

<div class="card-surface card-pad no-print" style="margin-bottom:16px;">
    <form method="get" style="display:flex;gap:8px;align-items:end;">
        <div class="form-group" style="margin-bottom:0;"><label class="form-label">Dari</label><input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="form-control"></div>
        <div class="form-group" style="margin-bottom:0;"><label class="form-label">Sampai</label><input type="date" name="date_to" value="<?= e($dateTo) ?>" class="form-control"></div>
        <button type="submit" class="btn btn-outline">Terapkan</button>
        <button type="button" class="btn btn-ghost" onclick="window.print()">Print</button>
    </form>
</div>

<div class="chart-grid" style="grid-template-columns:1fr 1fr;">
    <div class="card-surface card-pad">
        <p class="section-card-title">Retur Penjualan</p>
        <?php if (empty($saleReturns)): ?>
            <div class="empty-state" style="padding:20px 8px;"><p>Tidak ada retur penjualan.</p></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>No. Retur</th><th>Invoice</th><th>Alasan</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($saleReturns as $r): ?>
                <tr><td style="font-weight:600;"><?= e($r['return_number']) ?></td><td><?= e($r['invoice_number']) ?></td><td class="table-cell-muted"><?= e($r['reason']) ?></td><td><?= e(format_money($r['total'])) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <div class="card-surface card-pad">
        <p class="section-card-title">Retur Pembelian</p>
        <?php if (empty($purchaseReturns)): ?>
            <div class="empty-state" style="padding:20px 8px;"><p>Tidak ada retur pembelian.</p></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>No. Retur</th><th>Supplier</th><th>Alasan</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($purchaseReturns as $r): ?>
                <tr><td style="font-weight:600;"><?= e($r['return_number']) ?></td><td><?= e($r['supplier_name']) ?></td><td class="table-cell-muted"><?= e($r['reason']) ?></td><td><?= e(format_money($r['total'])) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
