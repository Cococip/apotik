<?php

/**
 * Canonical permission registry, enforced by PermissionMiddleware/Auth::can()
 * on every route (§25 — backend enforcement, not just hidden menus).
 */
return [
    'Dashboard' => [
        'dashboard.view' => 'Lihat dashboard',
    ],
    'Obat & Batch' => [
        'medicine.view' => 'Lihat data obat',
        'medicine.create' => 'Tambah obat',
        'medicine.update' => 'Ubah obat',
        'medicine.delete' => 'Hapus obat',
        'medicine.batch.manage' => 'Kelola batch & expired',
    ],
    'Persediaan' => [
        'inventory.view' => 'Lihat stok & kartu stok',
        'inventory.adjust' => 'Penyesuaian stok manual',
        'inventory.opname' => 'Stok opname',
    ],
    'Barcode' => [
        'barcode.view' => 'Lihat daftar barcode',
        'barcode.generate' => 'Generate barcode',
        'barcode.print' => 'Cetak label barcode',
    ],
    'Master Data' => [
        'master.manage' => 'Kelola kategori, jenis, golongan, satuan, produsen, rak, dokter, pasien, supplier',
    ],
    'Transaksi' => [
        'sales.view' => 'Lihat penjualan',
        'sales.create' => 'Buat penjualan (POS)',
        'sales.update' => 'Ubah penjualan',
        'sales.delete' => 'Hapus penjualan',
        'sales.refund' => 'Retur / refund penjualan',
    ],
    'Resep' => [
        'prescription.view' => 'Lihat resep',
        'prescription.create' => 'Input resep baru',
        'prescription.verify' => 'Verifikasi & proses resep',
    ],
    'Pembelian' => [
        'purchase.view' => 'Lihat pembelian',
        'purchase.create' => 'Buat pembelian & retur pembelian',
        'purchase.approve' => 'Approve purchase order & bayar hutang',
    ],
    'Keuangan' => [
        'finance.view' => 'Lihat data keuangan',
        'finance.manage' => 'Input kas masuk/keluar & pengeluaran',
    ],
    'Laporan' => [
        'report.view' => 'Lihat laporan',
        'report.export' => 'Export laporan',
    ],
    'Manajemen' => [
        'user.view' => 'Lihat pengguna',
        'user.create' => 'Tambah pengguna',
        'user.update' => 'Ubah pengguna',
        'user.delete' => 'Hapus pengguna',
        'role.manage' => 'Kelola role & permission',
        'audit.view' => 'Lihat audit log',
    ],
    'Sistem' => [
        'settings.manage' => 'Kelola pengaturan apotek',
        'backup.manage' => 'Backup database',
        'sync.manage' => 'Lihat & kelola sinkronisasi offline',
    ],
];
