<?php
$statusLabels = ['unpaid'=>'Belum Dibayar','partial'=>'Sebagian','paid'=>'Lunas'];
$statusBadge = ['unpaid'=>'badge-danger','partial'=>'badge-warning','paid'=>'badge-success'];
?>
<div class="breadcrumb-trail"><a href="/purchases">Pembelian</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> <?= e($purchase['purchase_number']) ?></div>
<div class="page-header">
    <div>
        <p class="page-title"><?= e($purchase['purchase_number']) ?></p>
        <p class="page-subtitle"><?= e($purchase['supplier_name']) ?> · <?= e(format_date($purchase['purchase_date'])) ?> · <span class="badge <?= $statusBadge[$purchase['payment_status']] ?>"><?= $statusLabels[$purchase['payment_status']] ?></span></p>
    </div>
    <a href="/purchase-returns/create?purchase=<?= urlencode($purchase['purchase_number']) ?>" class="btn btn-outline">Proses Retur</a>
</div>

<div class="chart-grid" style="grid-template-columns:2fr 1fr;">
    <div class="card-surface card-pad">
        <p class="section-card-title">Item Diterima</p>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Obat</th><th>Batch</th><th>Expired</th><th>Qty</th><th>Harga Beli</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($details as $d): ?>
                <tr>
                    <td style="font-weight:600;"><?= e($d['medicine_name']) ?></td>
                    <td class="table-cell-muted"><?= e($d['batch_number']) ?></td>
                    <td class="table-cell-muted"><?= e(format_date($d['expired_date'])) ?></td>
                    <td><?= (int) $d['qty'] ?></td>
                    <td><?= e(format_money($d['purchase_price'])) ?></td>
                    <td><?= e(format_money($d['subtotal'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <div class="card-surface card-pad">
        <p class="section-card-title">Ringkasan</p>
        <div style="font-size:13.5px;display:flex;flex-direction:column;gap:8px;">
            <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><strong><?= e(format_money($purchase['subtotal'])) ?></strong></div>
            <div style="display:flex;justify-content:space-between;font-size:16px;"><span>Total</span><strong><?= e(format_money($purchase['total'])) ?></strong></div>
            <hr style="border-color:var(--color-border);">
            <div style="display:flex;justify-content:space-between;"><span>Sudah Dibayar</span><strong><?= e(format_money($purchase['paid_total'])) ?></strong></div>
            <div style="display:flex;justify-content:space-between;color:var(--color-danger);"><span>Sisa</span><strong><?= e(format_money(max(0, $purchase['total'] - $purchase['paid_total']))) ?></strong></div>
            <?php if ($purchase['due_date']): ?><div style="display:flex;justify-content:space-between;"><span>Jatuh Tempo</span><strong><?= e(format_date($purchase['due_date'])) ?></strong></div><?php endif; ?>
        </div>
        <?php if ($purchase['total'] - $purchase['paid_total'] > 0): ?>
        <a href="/supplier-payments?purchase_id=<?= $purchase['id'] ?>" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:14px;">Bayar Hutang</a>
        <?php endif; ?>
    </div>
</div>
