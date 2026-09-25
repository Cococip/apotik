<?php
$reports = [
    ['url' => '/reports/sales', 'icon' => 'ic-receipt', 'title' => 'Penjualan', 'desc' => 'Rekap transaksi penjualan per periode.'],
    ['url' => '/reports/purchases', 'icon' => 'ic-truck', 'title' => 'Pembelian', 'desc' => 'Rekap penerimaan barang dari supplier.'],
    ['url' => '/reports/stock', 'icon' => 'ic-card-stock', 'title' => 'Stok', 'desc' => 'Snapshot stok seluruh obat saat ini.'],
    ['url' => '/reports/expired', 'icon' => 'ic-layers', 'title' => 'Expired', 'desc' => 'Batch aktif dengan status kedaluwarsa.'],
    ['url' => '/reports/returns', 'icon' => 'ic-corner-return', 'title' => 'Retur', 'desc' => 'Retur penjualan & pembelian per periode.'],
    ['url' => '/reports/profit', 'icon' => 'ic-dollar', 'title' => 'Laba', 'desc' => 'Omzet, modal, dan estimasi laba kotor harian.'],
    ['url' => '/reports/debt', 'icon' => 'ic-credit-card', 'title' => 'Hutang', 'desc' => 'Sisa hutang ke supplier yang belum lunas.'],
    ['url' => '/reports/finance', 'icon' => 'ic-file-text', 'title' => 'Keuangan', 'desc' => 'Ringkasan arus kas masuk dan keluar.'],
];
?>
<div class="page-header">
    <div>
        <p class="page-title">Laporan</p>
        <p class="page-subtitle">Pilih jenis laporan yang ingin dilihat.</p>
    </div>
</div>

<div class="pos-product-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));">
    <?php foreach ($reports as $r): ?>
    <a href="<?= $r['url'] ?>" class="card-surface card-pad" style="display:block;color:inherit;">
        <span class="stat-icon" style="margin-bottom:10px;"><svg class="icon"><use href="/assets/icons/sprite.svg#<?= $r['icon'] ?>"></use></svg></span>
        <p style="font-weight:700;font-size:14px;margin:0 0 4px;"><?= $r['title'] ?></p>
        <p class="table-cell-muted" style="margin:0;"><?= $r['desc'] ?></p>
    </a>
    <?php endforeach; ?>
</div>
