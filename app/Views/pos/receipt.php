<?php
$paperSize = $printer['paper_size'] ?? '80mm';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Struk <?= e($sale['invoice_number']) ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/print.css">
</head>
<body class="print-body">
<div class="label-toolbar no-print">
    <button class="btn btn-primary" onclick="window.print()">Cetak Struk</button>
    <button class="btn btn-outline" onclick="window.close()">Tutup</button>
</div>

<div class="receipt receipt-<?= e($paperSize) ?>">
    <div class="receipt-center">
        <strong><?= e($pharmacy['name'] ?? 'ApotekCare') ?></strong><br>
        <?= nl2br(e($pharmacy['address'] ?? '')) ?><br>
        <?= e($pharmacy['phone'] ?? '') ?>
    </div>
    <?php if (!empty($printer['header_text'])): ?><div class="receipt-center"><?= nl2br(e($printer['header_text'])) ?></div><?php endif; ?>
    <div class="receipt-divider"></div>
    <div class="receipt-row"><span>No. Invoice</span><span><?= e($sale['invoice_number']) ?></span></div>
    <div class="receipt-row"><span>Tanggal</span><span><?= e(format_datetime($sale['transaction_date'])) ?></span></div>
    <div class="receipt-row"><span>Kasir</span><span><?= e($sale['cashier_name']) ?></span></div>
    <?php if (!empty($sale['customer_name'])): ?><div class="receipt-row"><span>Pasien</span><span><?= e($sale['customer_name']) ?></span></div><?php endif; ?>
    <?php if (!empty($sale['doctor_name'])): ?><div class="receipt-row"><span>Dokter</span><span><?= e($sale['doctor_name']) ?></span></div><?php endif; ?>
    <div class="receipt-divider"></div>
    <?php foreach ($details as $d): ?>
        <div class="receipt-item">
            <div><?= e($d['medicine_name']) ?></div>
            <div class="receipt-row">
                <span><?= (int) $d['qty'] ?> <?= e($d['unit_symbol'] ?: '') ?> x <?= e(format_money($d['unit_price'])) ?></span>
                <span><?= e(format_money($d['subtotal'])) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="receipt-divider"></div>
    <div class="receipt-row"><span>Subtotal</span><span><?= e(format_money($sale['subtotal'])) ?></span></div>
    <?php if ($sale['discount'] > 0): ?><div class="receipt-row"><span>Diskon</span><span>-<?= e(format_money($sale['discount'])) ?></span></div><?php endif; ?>
    <div class="receipt-row receipt-total"><span>Total</span><span><?= e(format_money($sale['grand_total'])) ?></span></div>
    <div class="receipt-row"><span>Bayar (<?= e($sale['payment_method']) ?>)</span><span><?= e(format_money($sale['paid_amount'])) ?></span></div>
    <div class="receipt-row"><span>Kembali</span><span><?= e(format_money($sale['change_amount'])) ?></span></div>
    <?php if ($sale['paid_amount'] < $sale['grand_total']): ?>
        <div class="receipt-row" style="font-weight:700;"><span>Sisa (Piutang)</span><span><?= e(format_money($sale['grand_total'] - $sale['paid_amount'])) ?></span></div>
    <?php endif; ?>
    <div class="receipt-divider"></div>
    <div class="receipt-center">
        <?= nl2br(e($printer['footer_text'] ?? $pharmacy['receipt_footer'] ?? 'Terima kasih')) ?>
    </div>
</div>

<script>
<?php if (!empty($printer['auto_print'])): ?>
window.onload = function () { window.print(); };
<?php endif; ?>
</script>
</body>
</html>
