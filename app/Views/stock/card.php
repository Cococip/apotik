<?php
$movementLabels = [
    'purchase' => 'Pembelian', 'sale' => 'Penjualan', 'sale_return' => 'Retur Penjualan',
    'purchase_return' => 'Retur Pembelian', 'adjustment' => 'Penyesuaian', 'stock_opname' => 'Stok Opname',
    'damaged' => 'Rusak', 'expired' => 'Expired', 'transfer' => 'Transfer', 'opening_balance' => 'Stok Awal',
];
?>
<div class="breadcrumb-trail"><a href="/stock">Stok</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Kartu Stok</div>
<div class="page-header">
    <div>
        <p class="page-title">Kartu Stok — <?= e($medicine['name']) ?></p>
        <p class="page-subtitle">Kode <?= e($medicine['code']) ?> · Stok saat ini: <?= (int) $medicine['stock'] ?></p>
    </div>
    <a href="/medicines/<?= $medicine['id'] ?>/edit" class="btn btn-outline">Lihat Data Obat</a>
</div>

<div class="card-surface card-pad">
    <?php if (empty($movements)): ?>
        <div class="empty-state"><p>Belum ada histori pergerakan stok untuk obat ini.</p></div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Tanggal</th><th>Jenis</th><th>Batch</th><th>Masuk</th><th>Keluar</th><th>Saldo</th><th>Catatan</th><th>User</th></tr></thead>
        <tbody>
        <?php foreach ($movements as $m): ?>
            <tr>
                <td class="table-cell-muted"><?= e(format_datetime($m['created_at'])) ?></td>
                <td><span class="badge badge-info"><?= e($movementLabels[$m['movement_type']] ?? $m['movement_type']) ?></span></td>
                <td class="table-cell-muted"><?= e($m['batch_number'] ?: '-') ?></td>
                <td><?= $m['qty_in'] > 0 ? '+' . (int) $m['qty_in'] : '-' ?></td>
                <td><?= $m['qty_out'] > 0 ? '-' . (int) $m['qty_out'] : '-' ?></td>
                <td style="font-weight:700;"><?= (int) $m['balance_after'] ?></td>
                <td class="table-cell-muted"><?= e($m['note'] ?: '-') ?></td>
                <td class="table-cell-muted"><?= e($m['user_name'] ?: 'Sistem') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
