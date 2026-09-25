<?php
use App\Core\Auth;
?>
<div class="page-header">
    <div>
        <p class="page-title">Penjualan</p>
        <p class="page-subtitle">Riwayat transaksi penjualan.</p>
    </div>
    <?php if (Auth::can('sales.create')): ?>
    <a href="/pos" class="btn btn-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-cart"></use></svg> Buka POS</a>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
        <input type="search" name="search" value="<?= e($search) ?>" class="form-control" style="max-width:200px;" placeholder="Cari no. invoice...">
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="form-control" style="max-width:160px;">
        <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="form-control" style="max-width:160px;">
        <select name="payment_method" class="form-select" style="max-width:160px;">
            <option value="">Semua Metode</option>
            <?php foreach (['Cash','Debit','Kartu Kredit','QRIS','Transfer','E-Wallet','Lainnya'] as $m): ?>
                <option value="<?= e($m) ?>" <?= $paymentMethod === $m ? 'selected' : '' ?>><?= e($m) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline">Filter</button>
        <a href="/sales" class="btn btn-ghost">Reset</a>
    </form>

    <?php if (empty($rows)): ?>
        <div class="empty-state"><p>Belum ada transaksi penjualan.</p></div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Invoice</th><th>Tanggal</th><th>Kasir</th><th>Pasien</th><th>Metode</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td style="font-weight:600;"><?= e($row['invoice_number']) ?></td>
                <td class="table-cell-muted"><?= e(format_datetime($row['transaction_date'])) ?></td>
                <td class="table-cell-muted"><?= e($row['cashier_name']) ?></td>
                <td class="table-cell-muted"><?= e($row['customer_name'] ?: '-') ?></td>
                <td><span class="badge badge-info"><?= e($row['payment_method']) ?></span></td>
                <td><?= e(format_money($row['grand_total'])) ?></td>
                <td>
                    <?php if ($row['return_count'] > 0): ?><span class="badge badge-warning">Ada Retur</span>
                    <?php elseif ($row['paid_amount'] < $row['grand_total']): ?><span class="badge badge-danger">Piutang</span>
                    <?php else: ?><span class="badge badge-success">Lunas</span><?php endif; ?>
                </td>
                <td class="table-actions"><a href="/sales/<?= $row['id'] ?>" class="btn btn-sm btn-soft">Detail</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php render_pagination($pagination, '/sales', ['search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'payment_method' => $paymentMethod]); ?>
    <?php endif; ?>
</div>
