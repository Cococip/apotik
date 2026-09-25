<?php use App\Core\Auth; ?>
<div class="page-header">
    <div>
        <p class="page-title">Retur Pembelian</p>
        <p class="page-subtitle">Riwayat pengembalian barang ke supplier.</p>
    </div>
    <?php if (Auth::can('purchase.create')): ?>
    <a href="/purchase-returns/create" class="btn btn-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Buat Retur</a>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-corner-return"></use></svg></div>
            <h3>Belum ada retur pembelian</h3>
            <p>Retur ke supplier akan tercatat di sini.</p>
        </div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>No. Retur</th><th>No. Pembelian</th><th>Supplier</th><th>Tanggal</th><th>Alasan</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td style="font-weight:600;"><?= e($row['return_number']) ?></td>
                <td><?= e($row['purchase_number']) ?></td>
                <td><?= e($row['supplier_name']) ?></td>
                <td class="table-cell-muted"><?= e(format_date($row['return_date'])) ?></td>
                <td class="table-cell-muted"><?= e($row['reason']) ?></td>
                <td><?= e(format_money($row['total'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
