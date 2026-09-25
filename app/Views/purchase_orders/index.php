<?php
use App\Core\Auth;
$statusLabels = ['draft'=>'Draft','sent'=>'Terkirim','partially_received'=>'Sebagian Diterima','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
$statusBadge = ['draft'=>'badge-muted','sent'=>'badge-info','partially_received'=>'badge-warning','completed'=>'badge-success','cancelled'=>'badge-danger'];
?>
<div class="page-header">
    <div>
        <p class="page-title">Purchase Order</p>
        <p class="page-subtitle">Pesanan pembelian ke supplier.</p>
    </div>
    <?php if (Auth::can('purchase.create')): ?>
    <a href="/purchase-orders/create" class="btn btn-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Buat PO</a>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-bag"></use></svg></div>
            <h3>Belum ada Purchase Order</h3>
            <p>Buat PO pertama untuk memesan barang ke supplier.</p>
        </div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>No. PO</th><th>Supplier</th><th>Tanggal</th><th>Item</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td style="font-weight:600;"><?= e($row['po_number']) ?></td>
                <td><?= e($row['supplier_name']) ?></td>
                <td class="table-cell-muted"><?= e(format_date($row['order_date'])) ?></td>
                <td><?= (int) $row['item_count'] ?> item</td>
                <td><span class="badge <?= $statusBadge[$row['status']] ?>"><?= $statusLabels[$row['status']] ?></span></td>
                <td class="table-actions"><a href="/purchase-orders/<?= $row['id'] ?>" class="btn btn-sm btn-soft">Detail</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
