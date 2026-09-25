<?php
/** @var array|null $currentUser */
use App\Core\Auth;

function nav_link(string $path, string $icon, string $label, bool $active): void
{
    ?>
    <a href="<?= e($path) ?>" class="sidebar-link <?= $active ? 'is-active' : '' ?>">
        <svg class="icon"><use href="/assets/icons/sprite.svg#<?= e($icon) ?>"></use></svg>
        <span class="sidebar-link-label"><?= e($label) ?></span>
    </a>
    <?php
}

function nav_group_start(string $id, string $icon, string $label, bool $open): void
{
    ?>
    <button type="button" class="sidebar-group-toggle" aria-controls="<?= e($id) ?>">
        <svg class="icon"><use href="/assets/icons/sprite.svg#<?= e($icon) ?>"></use></svg>
        <span class="sidebar-link-label"><?= e($label) ?></span>
        <svg class="icon sidebar-chevron"><use href="/assets/icons/sprite.svg#ic-chevron-down"></use></svg>
    </button>
    <div id="<?= e($id) ?>" class="sidebar-submenu <?= $open ? 'is-open' : '' ?>">
    <?php
}

function nav_group_end(): void
{
    echo '</div>';
}
?>
<div class="sidebar-backdrop"></div>
<aside class="app-sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-mark">AC</div>
        <div class="sidebar-brand-text"><?= e(setting('pharmacy.name', 'ApotekCare')) ?><small>Sistem Manajemen Apotek</small></div>
    </div>
    <nav class="sidebar-nav">
        <?php nav_link('/', 'ic-dashboard', 'Dashboard', active_menu('/', true) !== ''); ?>

        <p class="sidebar-section-title">Transaksi</p>
        <?php if (Auth::can('sales.create')): ?>
            <?php nav_link('/pos', 'ic-cart', 'POS Kasir', active_menu('/pos')); ?>
        <?php endif; ?>
        <?php if (Auth::can('sales.view')): ?>
            <?php nav_link('/sales', 'ic-receipt', 'Penjualan', active_menu('/sales')); ?>
        <?php endif; ?>
        <?php if (Auth::can('prescription.view')): ?>
            <?php nav_link('/prescriptions', 'ic-clipboard', 'Resep', active_menu('/prescriptions')); ?>
        <?php endif; ?>
        <?php if (Auth::can('sales.refund')): ?>
            <?php nav_link('/sale-returns', 'ic-corner-return', 'Retur Penjualan', active_menu('/sale-returns')); ?>
        <?php endif; ?>

        <p class="sidebar-section-title">Persediaan</p>
        <?php if (Auth::can('medicine.view')): ?>
            <?php nav_link('/medicines', 'ic-pill', 'Daftar Obat', active_menu('/medicines')); ?>
        <?php endif; ?>
        <?php if (Auth::can('inventory.view')): ?>
            <?php nav_link('/stock', 'ic-card-stock', 'Stok', active_menu('/stock', true)); ?>
            <?php nav_link('/stock/batches', 'ic-layers', 'Batch & Expired', active_menu('/stock/batches')); ?>
            <?php nav_link('/stock/opname', 'ic-clipboard-check', 'Stok Opname', active_menu('/stock/opname')); ?>
            <?php nav_link('/stock/adjustment', 'ic-sliders', 'Penyesuaian Stok', active_menu('/stock/adjustment')); ?>
        <?php endif; ?>

        <p class="sidebar-section-title">Pembelian</p>
        <?php if (Auth::can('purchase.view')): ?>
            <?php nav_link('/purchase-orders', 'ic-bag', 'Purchase Order', active_menu('/purchase-orders')); ?>
            <?php nav_link('/purchases', 'ic-truck', 'Pembelian', active_menu('/purchases')); ?>
            <?php nav_link('/purchase-returns', 'ic-corner-return', 'Retur Pembelian', active_menu('/purchase-returns')); ?>
        <?php endif; ?>
        <?php if (Auth::can('master.manage')): ?>
            <?php nav_link('/suppliers', 'ic-users', 'Supplier', active_menu('/suppliers')); ?>
        <?php endif; ?>
        <?php if (Auth::can('purchase.view')): ?>
            <?php nav_link('/supplier-payments', 'ic-credit-card', 'Hutang Supplier', active_menu('/supplier-payments')); ?>
        <?php endif; ?>

        <p class="sidebar-section-title">Master Data</p>
        <?php if (Auth::can('master.manage')): ?>
            <?php nav_link('/categories', 'ic-tag', 'Kategori Obat', active_menu('/categories')); ?>
            <?php nav_link('/medicine-types', 'ic-list', 'Jenis Obat', active_menu('/medicine-types')); ?>
            <?php nav_link('/medicine-groups', 'ic-shield', 'Golongan Obat', active_menu('/medicine-groups')); ?>
            <?php nav_link('/units', 'ic-ruler', 'Satuan', active_menu('/units')); ?>
            <?php nav_link('/manufacturers', 'ic-factory', 'Produsen', active_menu('/manufacturers')); ?>
            <?php nav_link('/doctors', 'ic-stethoscope', 'Dokter', active_menu('/doctors')); ?>
            <?php nav_link('/patients', 'ic-user', 'Pasien', active_menu('/patients')); ?>
            <?php nav_link('/racks', 'ic-map-pin', 'Rak/Lokasi', active_menu('/racks')); ?>
        <?php endif; ?>

        <p class="sidebar-section-title">Barcode</p>
        <?php if (Auth::can('barcode.view')): ?>
            <?php nav_link('/barcode', 'ic-barcode', 'Daftar Barcode', active_menu('/barcode', true)); ?>
            <?php nav_link('/barcode/print', 'ic-printer', 'Cetak Label', active_menu('/barcode/print')); ?>
        <?php endif; ?>

        <p class="sidebar-section-title">Keuangan</p>
        <?php if (Auth::can('finance.view')): ?>
            <?php nav_link('/finance/cash-in', 'ic-dollar', 'Kas Masuk', active_menu('/finance/cash-in')); ?>
            <?php nav_link('/finance/cash-out', 'ic-dollar', 'Kas Keluar', active_menu('/finance/cash-out')); ?>
        <?php endif; ?>
        <?php if (Auth::can('purchase.view')): ?>
            <?php nav_link('/supplier-payments', 'ic-credit-card', 'Pembayaran Supplier', active_menu('/supplier-payments')); ?>
        <?php endif; ?>
        <?php if (Auth::can('finance.view')): ?>
            <?php nav_link('/finance/piutang', 'ic-file-text', 'Piutang', active_menu('/finance/piutang')); ?>
            <?php nav_link('/finance/recap', 'ic-file-text', 'Rekap Keuangan', active_menu('/finance/recap')); ?>
        <?php endif; ?>

        <p class="sidebar-section-title">Laporan</p>
        <?php if (Auth::can('report.view')): ?>
            <?php nav_link('/reports', 'ic-file-text', 'Laporan Lengkap', active_menu('/reports')); ?>
        <?php endif; ?>

        <p class="sidebar-section-title">Manajemen</p>
        <?php if (Auth::can('user.view')): ?>
            <?php nav_link('/users', 'ic-user', 'User', active_menu('/users')); ?>
        <?php endif; ?>
        <?php if (Auth::can('role.manage')): ?>
            <?php nav_link('/roles', 'ic-shield-check', 'Role & Permission', active_menu('/roles')); ?>
        <?php endif; ?>
        <?php nav_link('/shift', 'ic-clock', 'Shift Kasir', active_menu('/shift')); ?>
        <?php if (Auth::can('audit.view')): ?>
            <?php nav_link('/audit-log', 'ic-activity', 'Audit Log', active_menu('/audit-log')); ?>
        <?php endif; ?>

        <p class="sidebar-section-title">Sistem</p>
        <?php if (Auth::can('settings.manage')): ?>
            <?php nav_group_start('menuSistem', 'ic-settings', 'Pengaturan', str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/settings')); ?>
                <?php nav_link('/settings/pharmacy', 'ic-home', 'Profil Apotek', active_menu('/settings/pharmacy')); ?>
                <?php nav_link('/settings/system', 'ic-settings', 'Sistem', active_menu('/settings/system')); ?>
                <?php nav_link('/settings/inventory', 'ic-layers', 'Persediaan', active_menu('/settings/inventory')); ?>
                <?php nav_link('/settings/printer', 'ic-printer', 'Printer', active_menu('/settings/printer')); ?>
            <?php nav_group_end(); ?>
        <?php endif; ?>
        <?php if (Auth::can('backup.manage')): ?>
            <?php nav_link('/backup', 'ic-database', 'Backup', active_menu('/backup')); ?>
        <?php endif; ?>
        <?php if (Auth::can('sync.manage')): ?>
            <?php nav_link('/sync', 'ic-refresh', 'Sinkronisasi', active_menu('/sync')); ?>
        <?php endif; ?>
    </nav>
</aside>
