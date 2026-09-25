<?php use App\Core\Auth; ?>
<div class="breadcrumb-trail"><a href="/sales">Penjualan</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> <?= e($sale['invoice_number']) ?></div>
<div class="page-header">
    <div>
        <p class="page-title">Transaksi <?= e($sale['invoice_number']) ?></p>
        <p class="page-subtitle"><?= e(format_datetime($sale['transaction_date'])) ?> · Kasir <?= e($sale['cashier_name']) ?></p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/pos/receipt/<?= $sale['id'] ?>" target="_blank" class="btn btn-outline">Cetak Ulang Struk</a>
        <?php if (Auth::can('sales.refund')): ?>
        <a href="/sale-returns/create?invoice=<?= urlencode($sale['invoice_number']) ?>" class="btn btn-soft">Proses Retur</a>
        <?php endif; ?>
    </div>
</div>

<div class="chart-grid" style="grid-template-columns:2fr 1fr;">
    <div class="card-surface card-pad">
        <p class="section-card-title">Item</p>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Obat</th><th>Batch</th><th>Qty</th><th>Harga</th><th>Subtotal</th><th>Retur</th></tr></thead>
            <tbody>
            <?php foreach ($details as $d): ?>
                <tr>
                    <td style="font-weight:600;"><?= e($d['medicine_name']) ?></td>
                    <td class="table-cell-muted"><?= e($d['batch_number'] ?: '-') ?></td>
                    <td><?= (int) $d['qty'] ?></td>
                    <td><?= e(format_money($d['unit_price'])) ?></td>
                    <td><?= e(format_money($d['subtotal'])) ?></td>
                    <td><?= $d['returned_qty'] > 0 ? (int) $d['returned_qty'] . ' dikembalikan' : '-' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="card-surface card-pad">
        <p class="section-card-title">Ringkasan Pembayaran</p>
        <div style="font-size:13.5px;display:flex;flex-direction:column;gap:8px;">
            <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><strong><?= e(format_money($sale['subtotal'])) ?></strong></div>
            <div style="display:flex;justify-content:space-between;"><span>Diskon</span><strong>-<?= e(format_money($sale['discount'])) ?></strong></div>
            <div style="display:flex;justify-content:space-between;font-size:16px;"><span>Total</span><strong><?= e(format_money($sale['grand_total'])) ?></strong></div>
            <hr style="border-color:var(--color-border);">
            <div style="display:flex;justify-content:space-between;"><span>Metode</span><strong><?= e($sale['payment_method']) ?></strong></div>
            <div style="display:flex;justify-content:space-between;"><span>Dibayar</span><strong><?= e(format_money($sale['paid_amount'])) ?></strong></div>
            <div style="display:flex;justify-content:space-between;"><span>Kembalian</span><strong><?= e(format_money($sale['change_amount'])) ?></strong></div>
            <?php if ($sale['paid_amount'] < $sale['grand_total']): ?>
                <div style="display:flex;justify-content:space-between;color:var(--color-danger);"><span>Piutang</span><strong><?= e(format_money($sale['grand_total'] - $sale['paid_amount'])) ?></strong></div>
                <div><span>Pasien: </span><strong><?= e($sale['customer_name'] ?: '-') ?></strong></div>
            <?php endif; ?>
        </div>
    </div>
</div>
