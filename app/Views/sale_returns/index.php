<?php use App\Core\Auth; ?>
<div class="page-header">
    <div>
        <p class="page-title">Retur Penjualan</p>
        <p class="page-subtitle">Riwayat pengembalian barang dari pelanggan.</p>
    </div>
    <?php if (Auth::can('sales.refund')): ?>
    <a href="/sale-returns/create" class="btn btn-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Buat Retur</a>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-corner-return"></use></svg></div>
            <h3>Belum ada retur penjualan</h3>
            <p>Retur akan tercatat di sini setelah diproses.</p>
        </div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>No. Retur</th><th>Invoice Asal</th><th>Tanggal</th><th>Alasan</th><th>Total</th><th>Petugas</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td style="font-weight:600;"><?= e($row['return_number']) ?></td>
                <td><a href="/sales/<?= $row['sale_id'] ?>"><?= e($row['invoice_number']) ?></a></td>
                <td class="table-cell-muted"><?= e(format_date($row['return_date'])) ?></td>
                <td class="table-cell-muted"><?= e($row['reason']) ?></td>
                <td><?= e(format_money($row['total'])) ?></td>
                <td class="table-cell-muted"><?= e($row['user_name'] ?: '-') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
