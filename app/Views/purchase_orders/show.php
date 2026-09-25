<?php
use App\Core\Auth;
$statusLabels = ['draft'=>'Draft','sent'=>'Terkirim','partially_received'=>'Sebagian Diterima','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
$statusBadge = ['draft'=>'badge-muted','sent'=>'badge-info','partially_received'=>'badge-warning','completed'=>'badge-success','cancelled'=>'badge-danger'];
?>
<div class="breadcrumb-trail"><a href="/purchase-orders">Purchase Order</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> <?= e($po['po_number']) ?></div>
<div class="page-header">
    <div>
        <p class="page-title"><?= e($po['po_number']) ?></p>
        <p class="page-subtitle"><?= e($po['supplier_name']) ?> · <?= e(format_date($po['order_date'])) ?> · <span class="badge <?= $statusBadge[$po['status']] ?>"><?= $statusLabels[$po['status']] ?></span></p>
    </div>
    <div style="display:flex;gap:8px;">
        <?php if (in_array($po['status'], ['draft','sent'], true) && Auth::can('purchase.create')): ?>
        <a href="/purchases/create?po_id=<?= $po['id'] ?>" class="btn btn-primary">Buat Pembelian dari PO Ini</a>
        <?php endif; ?>
        <?php if ($po['status'] !== 'completed' && $po['status'] !== 'cancelled' && Auth::can('purchase.approve')): ?>
        <form method="POST" action="/purchase-orders/<?= $po['id'] ?>/cancel" data-confirm="Batalkan PO ini?">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline">Batalkan PO</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="card-surface card-pad">
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Obat</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
        <tbody>
        <?php $total = 0; foreach ($details as $d): $total += $d['subtotal']; ?>
            <tr>
                <td style="font-weight:600;"><?= e($d['medicine_name']) ?></td>
                <td><?= (int) $d['qty'] ?></td>
                <td><?= e(format_money($d['unit_price'])) ?></td>
                <td><?= e(format_money($d['subtotal'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <div style="text-align:right;margin-top:12px;font-size:15px;font-weight:700;">Total Estimasi: <?= e(format_money($total)) ?></div>
    <?php if ($po['notes']): ?><p class="table-cell-muted" style="margin-top:12px;">Catatan: <?= e($po['notes']) ?></p><?php endif; ?>
</div>
