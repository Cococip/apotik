<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Label Barcode — <?= e(config('app.name')) ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/print.css">
</head>
<body class="print-body">
<div class="label-toolbar no-print">
    <label>Ukuran Label:
        <select id="sizeSelect" onchange="changeSize(this.value)">
            <option value="small" <?= $size === 'small' ? 'selected' : '' ?>>Kecil (38x22mm)</option>
            <option value="medium" <?= $size === 'medium' ? 'selected' : '' ?>>Sedang (50x30mm)</option>
            <option value="large" <?= $size === 'large' ? 'selected' : '' ?>>Besar (70x38mm)</option>
        </select>
    </label>
    <button class="btn btn-primary" onclick="window.print()">Cetak</button>
    <span style="color:var(--color-ink-muted);font-size:13px;"><?= count($medicines) ?> label siap dicetak</span>
</div>

<?php if (empty($medicines)): ?>
    <div class="empty-state no-print"><p>Tidak ada obat dengan barcode terpilih. Kembali ke Daftar Barcode dan pilih obat yang sudah memiliki barcode.</p></div>
<?php else: ?>
<div class="label-sheet">
    <?php foreach ($medicines as $m): ?>
        <div class="label-item size-<?= e($size) ?>">
            <div class="label-name"><?= e($m['name']) ?></div>
            <svg class="barcode" jsbarcode-value="<?= e($m['barcode']) ?>" jsbarcode-height="30" jsbarcode-fontsize="10" jsbarcode-margin="0"></svg>
            <div class="label-price"><?= e(format_money($m['selling_price'])) ?></div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script src="/assets/vendor/jsbarcode/JsBarcode.all.min.js"></script>
<script>
    JsBarcode('.barcode').init();
    function changeSize(size) {
        var params = new URLSearchParams(window.location.search);
        params.set('size', size);
        window.location.search = params.toString();
    }
</script>
</body>
</html>
