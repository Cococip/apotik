<?php
use App\Core\Auth;
$statusLabels = ['unpaid'=>'Belum Dibayar','partial'=>'Sebagian','paid'=>'Lunas'];
$statusBadge = ['unpaid'=>'badge-danger','partial'=>'badge-warning','paid'=>'badge-success'];
?>
<div class="page-header">
    <div>
        <p class="page-title">Pembelian</p>
        <p class="page-subtitle">Riwayat penerimaan barang dari supplier.</p>
    </div>
    <?php if (Auth::can('purchase.create')): ?>
    <a href="/purchases/create" class="btn btn-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Input Pembelian</a>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-truck"></use></svg></div>
            <h3>Belum ada data pembelian</h3>
            <p>Input pembelian pertama untuk menambah stok dari supplier.</p>
        </div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>No. Pembelian</th><th>Supplier</th><th>Tanggal</th><th>Total</th><th>Dibayar</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td style="font-weight:600;"><?= e($row['purchase_number']) ?></td>
                <td><?= e($row['supplier_name']) ?></td>
                <td class="table-cell-muted"><?= e(format_date($row['purchase_date'])) ?></td>
                <td><?= e(format_money($row['total'])) ?></td>
                <td><?= e(format_money($row['paid_total'])) ?></td>
                <td><span class="badge <?= $statusBadge[$row['payment_status']] ?>"><?= $statusLabels[$row['payment_status']] ?></span></td>
                <td class="table-actions"><a href="/purchases/<?= $row['id'] ?>" class="btn btn-sm btn-soft">Detail</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php render_pagination($pagination, '/purchases', []); ?>
    <?php endif; ?>
</div>
