# ApotekCare — Sistem Manajemen Apotek

Aplikasi manajemen apotek berbasis PHP MVC custom (tanpa framework besar), MySQL 8, dan PWA dengan dukungan transaksi offline. Semua modul di bawah ini **fungsional penuh** — bukan mockup, bukan "coming soon".

## Modul yang Berfungsi Penuh

- Autentikasi, session, CSRF, rate-limit login, RBAC granular (ditegakkan di backend lewat `PermissionMiddleware`, bukan cuma sembunyikan menu)
- Dashboard real-time dari database (statistik, grafik Chart.js, widget stok menipis/akan expired/transaksi terbaru)
- Master data: kategori, jenis obat, golongan obat, satuan, produsen, rak, dokter, pasien, supplier
- Obat: CRUD lengkap, konversi satuan per obat
- Batch & Expired dengan FEFO, ambang peringatan yang bisa dikonfigurasi
- Stock ledger (kartu stok), Stok Opname, Penyesuaian Stok manual — semua tercatat di `stock_movements`
- Barcode: generate kode internal (APO######), generate massal, cetak label (JsBarcode, browser print)
- **Shift Kasir**: buka/tutup shift dengan rekonsiliasi kas sistem vs aktual
- **POS/Kasir**: pencarian & scan barcode, keranjang, FEFO otomatis di sisi server, pembayaran (termasuk piutang sebagian), cetak struk, **bekerja offline** (lihat bagian PWA di bawah)
- **Penjualan**: riwayat transaksi, detail, cetak ulang struk
- **Retur Penjualan**: retur sebagian/penuh per item, mengembalikan stok (kecuali kondisi rusak)
- **Purchase Order & Pembelian**: PO ke supplier, penerimaan barang otomatis membuat batch + stok masuk, opsi bayar sebagian saat terima
- **Retur Pembelian**: retur ke supplier, mengurangi stok
- **Hutang Supplier**: pelunasan hutang, status otomatis (belum bayar/sebagian/lunas)
- **Resep**: alur draft → menunggu verifikasi → diverifikasi → diproses → selesai, dengan jejak siapa input & siapa verifikasi
- **Keuangan**: kas masuk/keluar manual, pengeluaran operasional, piutang (pelunasan sisa tagihan pasien), rekap arus kas per periode
- **Laporan**: penjualan, pembelian, stok, expired, retur, laba, hutang, keuangan — semua dengan filter tanggal, export CSV, dan tampilan print
- **Backup**: `mysqldump` manual dari UI, daftar & download backup
- **Sinkronisasi offline**: transaksi POS yang gagal terkirim (offline) disimpan di IndexedDB perangkat, otomatis dikirim ulang saat online, server selalu memvalidasi ulang stok (FEFO) dan bersifat idempoten by UUID — konflik stok ditandai eksplisit untuk ditinjau admin, tidak pernah ditimpa diam-diam
- User management, Role & Permission matrix, Audit Log
- Pengaturan: Profil Apotek, Sistem, Persediaan, Printer
- Notifikasi real-time (stok menipis, akan expired)
- PWA: manifest + service worker, app shell tersedia offline, aset JS/CSS milik aplikasi selalu diambil fresh saat online (network-first) agar update tidak pernah tertahan cache lama

## Requirement

- PHP 8.2+ (dikembangkan dengan PHP 8.4) dengan ekstensi: `pdo_mysql`, `mbstring`, `json`
- MySQL 8.0+ / MariaDB 10.6+
- `mysqldump` di PATH (untuk fitur Backup)
- Composer 2.x
- Web server: Apache/Nginx, atau `php -S` untuk development

## Instalasi

```bash
cd apotikcare
composer install
cp .env.example .env
# edit .env sesuai kredensial database Anda
```

### Setup Database

```bash
mysql -u root -e "CREATE DATABASE apotekcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php database/migrate.php
php database/seeders/seed.php
```

`migrate.php` melacak migrasi yang sudah diterapkan lewat tabel `schema_migrations`, jadi aman dijalankan berulang — file migrasi baru (mis. `002_phase2_additions.sql` untuk `invoice_counters`) otomatis diterapkan tanpa mengulang yang lama. Seeder mengisi role/permission (37 permission granular), 5 akun demo, data master, ~20 obat dengan batch, ~75 contoh transaksi 30 hari, dan menyinkronkan counter nomor invoice agar transaksi pertama di POS tidak bentrok dengan data seed.

### Konfigurasi (.env)

| Key | Keterangan | Default |
|---|---|---|
| `APP_URL` | Base URL aplikasi | `http://127.0.0.1:6100` |
| `APP_DEBUG` | Tampilkan stack trace saat error | `true` (matikan di production) |
| `DB_*` | Kredensial database | lihat `.env.example` |
| `TIMEZONE` | Zona waktu aplikasi | `Asia/Jakarta` |

Pengaturan operasional (profil apotek, format invoice, ambang expired, metode pembayaran, dll) **disimpan di database melalui menu Pengaturan**, bukan di `.env` (§51).

### Menjalankan (development)

```bash
php -S 127.0.0.1:6100 -t public public/router.php
```

Buka `http://127.0.0.1:6100`.

### Virtual Host (production, Apache)

```apache
<VirtualHost *:80>
    ServerName apotekcare.local
    DocumentRoot /var/www/html/apotikcare/public
    <Directory /var/www/html/apotikcare/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Permission Folder (production)

```bash
chown -R www-data:www-data storage/ public/uploads/
chmod -R 775 storage/
```

## Akun Demo

**Password untuk semua akun demo: `Apotek#2026` — WAJIB diganti sebelum digunakan di production.**

| Username | Role | Akses |
|---|---|---|
| `superadmin` | Super Admin | Semua modul |
| `owner` | Owner | Semua modul kecuali hapus user |
| `apoteker` | Apoteker | Obat, stok, barcode, master data, resep, laporan |
| `kasir` | Kasir | POS, penjualan, retur penjualan, resep, shift kasir |
| `gudang` | Gudang | Persediaan, barcode, pembelian |

## Arsitektur

```
app/Core/          Router, Controller, Model (soft-delete aware), Database (PDO), Auth (RBAC), Session, Csrf, Validator, View
app/Middleware/     AuthMiddleware, GuestMiddleware, PermissionMiddleware (backend enforcement, §25)
app/Controllers/    Satu per modul, tipis — logika bisnis ada di Services
app/Services/       AuditLogger, StockLedgerService (satu-satunya jalur tulis stok), SalesService (checkout, dipakai POS & sync),
                     PurchaseService, SyncService, CsvExporter, BarcodeCodeGenerator, ExpiryStatusService, CashShiftService, dll
app/Models/         Satu class per tabel yang sudah dipakai UI
app/Views/          layouts/main (app), layouts/pos (kasir, tanpa sidebar penuh), layouts/auth (login), print (label/struk)
public/             Front controller, assets (CSS/JS/vendor di-vendor lokal, bukan CDN — wajib untuk PWA offline)
database/           migrations/ (dilacak via schema_migrations), seeders/seed.php
routes/             Satu file per modul, di-require dari web.php/api.php
```

### Alur Stok (§8, §53)

Semua perubahan stok **wajib** lewat `App\Services\StockLedgerService` — tidak pernah `UPDATE medicine_batches SET available_qty = ...` langsung. Setiap pemanggilan menghasilkan satu baris di `stock_movements` dengan `balance_after` yang dihitung ulang, sehingga Kartu Stok selalu akurat dan bisa diaudit. FEFO diterapkan dengan mengurutkan batch berdasarkan `expired_date ASC` dan mengunci baris (`SELECT ... FOR UPDATE`) di `App\Services\SalesService::checkout()` — dipakai baik oleh POS online maupun mesin sinkronisasi offline, sehingga alokasi batch **selalu dihitung ulang di server**, tidak pernah dipercayakan ke data cache klien.

### RBAC

Permission didefinisikan satu tempat: `config/permissions.php` (37 permission granular). Middleware `PermissionMiddleware` menegakkan izin di level route (backend), bukan sekadar menyembunyikan menu di sidebar. `Auth::isSuperAdmin()` selalu bypass — permission row untuk super_admin tetap disimpan agar UI role editor konsisten.

## PWA & Mode Offline

- `manifest.json` + `service-worker.js`: aset vendor (Bootstrap/Chart.js/JsBarcode) di-cache-first (jarang berubah); aset milik aplikasi sendiri (`app.js`, `pos.js`, CSS) di-**network-first** — supaya update kode tidak pernah tertahan oleh cache lama, dan tetap ada fallback ke cache saat offline.
- Indikator ONLINE/OFFLINE di topbar melakukan heartbeat setiap 20 detik ke `manifest.json`.
- **Transaksi offline di POS**: saat `fetch` ke `/api/pos/checkout` gagal (bukan ditolak server, tapi gagal jaringan), transaksi disimpan ke IndexedDB (`public/assets/js/offline-db.js`) lengkap dengan UUID, stok cache lokal disesuaikan secara optimis, dan struk sementara (bertanda "BELUM TERSINKRONISASI") bisa dicetak. Begal katalog obat (harga/stok) juga di-cache di IndexedDB dari `/api/pos/catalog` supaya pencarian & scan barcode tetap berfungsi offline.
- **Sinkronisasi**: begitu online (event `online` atau interval 30 detik), antrean di IndexedDB dikirim ke `POST /api/sync/push`. Server memproses lewat `SyncService::processSale()` yang idempoten (cek `uuid` di tabel `sales`), mencatat setiap upaya di `sync_queue`/`sync_logs`, dan **menandai konflik** (bukan menimpa diam-diam) kalau stok sudah habis duluan saat offline. Halaman **Sistem > Sinkronisasi** menampilkan status pending/berhasil/gagal/konflik, daftar perangkat terdaftar, dan tombol retry manual per item.

## Barcode & Printer

Barcode internal format `APO######`, dibuat hanya atas aksi eksplisit pengguna (tombol Generate, dengan konfirmasi) — tidak pernah otomatis/diam-diam (§9). Rendering barcode memakai JsBarcode di sisi klien (vendored lokal), cetak label dan struk memakai CSS print terpisah (`print.css`) dan `window.print()` browser. Pengaturan printer (ukuran kertas 58mm/80mm, header/footer struk) di Pengaturan > Printer dipakai langsung oleh halaman struk POS (`/pos/receipt/{id}`).

## Production Checklist

- [ ] Ganti password semua akun demo (`Apotek#2026`)
- [ ] Set `APP_DEBUG=false` di `.env`
- [ ] Set `SESSION_SECURE=true` bila menggunakan HTTPS
- [ ] Review permission tiap role sesuai kebutuhan operasional apotek
- [ ] Isi Profil Apotek (nama, alamat, logo, footer struk) di Pengaturan
- [ ] Konfigurasi Pengaturan > Printer sesuai printer struk yang dipakai
- [ ] Backup database sebelum deploy migrasi baru (menu Backup atau `mysqldump` manual)
- [ ] Pastikan `storage/` writable oleh user web server, dan `mysqldump` ada di PATH server
- [ ] Nonaktifkan/lindungi endpoint development (`database/migrate.php`, `database/seeders/`) dari akses publik di server production

## Troubleshooting

| Gejala | Kemungkinan Penyebab |
|---|---|
| 500 error | Cek `storage/logs/app-YYYY-MM-DD.log` dan `storage/logs/php-error.log` |
| CSRF "Sesi tidak valid" terus muncul | Cookie session diblokir atau token form kedaluwarsa — reload halaman form |
| Barcode label tidak muncul di halaman cetak | Pastikan obat yang dipilih sudah memiliki barcode (generate dulu) |
| Dashboard menampilkan angka 0 semua | Jalankan ulang `php database/seeders/seed.php` pada database kosong |
| Notifikasi tidak muncul | Notifikasi disinkronkan saat bell dibuka/di-poll — pastikan sudah login dan ada data stok menipis/akan expired |
| POS gagal checkout dengan invoice bentrok | Jalankan seeder terbaru — versi lama tidak menyinkronkan `invoice_counters` dengan data seed |
| Transaksi offline tidak tersinkron | Cek Sistem > Sinkronisasi untuk status konflik/gagal; buka DevTools > Application > IndexedDB > `apotekcare_offline` untuk lihat antrean mentah |
| Backup gagal dibuat | Pastikan `mysqldump` terpasang dan bisa diakses user yang menjalankan PHP |

## Testing Manual (checklist)

- [x] Login/logout, rate limit setelah 5 percobaan gagal
- [x] Permission backend ditegakkan (bukan cuma UI) — dicoba dengan akun `kasir` ke `/medicines` → 403
- [x] CRUD semua master data + soft delete
- [x] Tambah obat, tambah batch → stok bertambah via stock_movements
- [x] Batch ditandai rusak/ditarik → stok dinolkan dengan movement tercatat
- [x] Stok Opname & Penyesuaian Stok manual tervalidasi
- [x] Generate barcode (satuan & massal), cetak label
- [x] Buka/tutup shift kasir dengan rekonsiliasi selisih kas
- [x] POS: cari & scan barcode, checkout online, cetak struk, invoice tidak bentrok dengan data seed
- [x] POS offline: checkout gagal jaringan → antre di IndexedDB → auto-sync saat online → invoice resmi terbit, idempoten (uuid sama tidak dobel)
- [x] Sinkronisasi menandai konflik saat stok habis, bukan menimpa diam-diam; retry manual berfungsi
- [x] Retur penjualan & retur pembelian mengoreksi stok dengan benar
- [x] Purchase Order → Pembelian → batch & stok otomatis bertambah, hutang tercatat, pelunasan mengubah status
- [x] Resep: alur draft → verifikasi → diproses → selesai, dengan jejak user
- [x] Keuangan: kas masuk/keluar, pengeluaran, piutang, rekap arus kas sesuai data riil
- [x] Semua 8 laporan tampil, filter tanggal, export CSV valid (tanpa notice PHP bocor ke file)
- [x] Backup manual berhasil membuat & bisa didownload dump SQL valid
- [x] Role & Permission matrix tersimpan dan langsung berlaku
- [x] Audit log mencatat login/create/update/delete di seluruh modul
- [x] Dashboard & notifikasi membaca data asli dari database
- [x] PWA: manifest & service worker ter-cache; aset aplikasi network-first (update tidak tertahan cache lama)
- [x] Responsive: sidebar collapse (desktop) & drawer (mobile), POS tetap 1 kolom nyaman di tablet/mobile, tabel scroll horizontal
# apotik
