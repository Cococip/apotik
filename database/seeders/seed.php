<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/app/Helpers/functions.php';

use App\Core\Database;
use App\Services\StockLedgerService;
use Dotenv\Dotenv;

Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();
date_default_timezone_set('Asia/Jakarta');

$pdo = Database::connection();

echo "Seeding ApotekCare demo data...\n";

// ============================================================
// 1. Permissions & Roles
// ============================================================

$permissionGroups = require dirname(__DIR__, 2) . '/config/permissions.php';
$permissionIds = [];

$insertPermission = $pdo->prepare('INSERT INTO permissions (slug, module, description) VALUES (:slug, :module, :description)');
foreach ($permissionGroups as $module => $items) {
    foreach ($items as $slug => $description) {
        $insertPermission->execute(['slug' => $slug, 'module' => $module, 'description' => $description]);
        $permissionIds[$slug] = (int) $pdo->lastInsertId();
    }
}
echo '- ' . count($permissionIds) . " permissions inserted\n";

$roles = [
    ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Akses penuh ke seluruh sistem', 'is_system' => 1],
    ['name' => 'Owner', 'slug' => 'owner', 'description' => 'Pemilik apotek, akses penuh kecuali hapus pengguna', 'is_system' => 1],
    ['name' => 'Apoteker', 'slug' => 'apoteker', 'description' => 'Mengelola obat, batch, stok, dan barcode', 'is_system' => 1],
    ['name' => 'Kasir', 'slug' => 'kasir', 'description' => 'Operator kasir/POS', 'is_system' => 1],
    ['name' => 'Gudang', 'slug' => 'gudang', 'description' => 'Mengelola persediaan dan barcode', 'is_system' => 1],
];
$roleIds = [];
$insertRole = $pdo->prepare('INSERT INTO roles (name, slug, description, is_system) VALUES (:name, :slug, :description, :is_system)');
foreach ($roles as $role) {
    $insertRole->execute($role);
    $roleIds[$role['slug']] = (int) $pdo->lastInsertId();
}
echo '- ' . count($roleIds) . " roles inserted\n";

$rolePermissionMap = [
    'super_admin' => array_keys($permissionIds),
    'owner' => array_values(array_diff(array_keys($permissionIds), ['user.delete'])),
    'apoteker' => ['dashboard.view', 'medicine.view', 'medicine.create', 'medicine.update', 'medicine.batch.manage', 'inventory.view', 'inventory.adjust', 'inventory.opname', 'barcode.view', 'barcode.generate', 'barcode.print', 'master.manage', 'report.view', 'report.export', 'prescription.view', 'prescription.create', 'prescription.verify'],
    'kasir' => ['dashboard.view', 'sales.view', 'sales.create', 'sales.refund', 'prescription.view', 'prescription.create'],
    'gudang' => ['dashboard.view', 'medicine.view', 'medicine.batch.manage', 'inventory.view', 'inventory.adjust', 'inventory.opname', 'barcode.view', 'barcode.generate', 'barcode.print', 'purchase.view', 'purchase.create'],
];
$insertRolePermission = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
foreach ($rolePermissionMap as $roleSlug => $slugs) {
    foreach ($slugs as $slug) {
        if (!isset($permissionIds[$slug])) {
            continue;
        }
        $insertRolePermission->execute(['role_id' => $roleIds[$roleSlug], 'permission_id' => $permissionIds[$slug]]);
    }
}
echo "- role_permissions mapped\n";

// ============================================================
// 2. Demo users (password documented in README — MUST change in production)
// ============================================================

$demoPassword = password_hash('Apotek#2026', PASSWORD_DEFAULT);
$users = [
    ['username' => 'superadmin', 'email' => 'superadmin@apotekcare.test', 'full_name' => 'Super Admin', 'role' => 'super_admin', 'phone' => '081200000001'],
    ['username' => 'owner', 'email' => 'owner@apotekcare.test', 'full_name' => 'Pemilik Apotek', 'role' => 'owner', 'phone' => '081200000002'],
    ['username' => 'apoteker', 'email' => 'apoteker@apotekcare.test', 'full_name' => 'Apoteker Utama', 'role' => 'apoteker', 'phone' => '081200000003'],
    ['username' => 'kasir', 'email' => 'kasir@apotekcare.test', 'full_name' => 'Kasir Shift Pagi', 'role' => 'kasir', 'phone' => '081200000004'],
    ['username' => 'gudang', 'email' => 'gudang@apotekcare.test', 'full_name' => 'Staf Gudang', 'role' => 'gudang', 'phone' => '081200000005'],
];
$userIds = [];
$insertUser = $pdo->prepare('INSERT INTO users (username, email, password, full_name, phone, role_id, status) VALUES (:username, :email, :password, :full_name, :phone, :role_id, "active")');
foreach ($users as $u) {
    $insertUser->execute([
        'username' => $u['username'],
        'email' => $u['email'],
        'password' => $demoPassword,
        'full_name' => $u['full_name'],
        'phone' => $u['phone'],
        'role_id' => $roleIds[$u['role']],
    ]);
    $userIds[$u['username']] = (int) $pdo->lastInsertId();
}
echo '- ' . count($userIds) . " demo users inserted\n";

// ============================================================
// 3. Pharmacy profile, settings, printer
// ============================================================

$pdo->prepare('INSERT INTO pharmacies (name, address, phone, email, receipt_footer) VALUES (:name, :address, :phone, :email, :footer)')
    ->execute([
        'name' => 'ApotekCare',
        'address' => 'Jl. Kesehatan Raya No. 10, Jakarta Selatan',
        'phone' => '021-5550123',
        'email' => 'halo@apotekcare.test',
        'footer' => 'Terima kasih atas kunjungan Anda. Semoga lekas sembuh!',
    ]);

$settings = [
    'system.timezone' => ['Asia/Jakarta', 'system'],
    'system.currency' => ['IDR', 'system'],
    'system.date_format' => ['d/m/Y', 'system'],
    'inventory.default_min_stock' => ['10', 'inventory'],
    'inventory.expiry_watch_days' => ['90', 'inventory'],
    'inventory.expiry_medium_days' => ['60', 'inventory'],
    'inventory.expiry_high_days' => ['30', 'inventory'],
    'inventory.expiry_critical_days' => ['7', 'inventory'],
    'inventory.fefo_enabled' => ['1', 'inventory'],
    'transaction.invoice_prefix' => ['INV', 'transaction'],
    'transaction.default_discount' => ['0', 'transaction'],
    'transaction.payment_methods' => ['Cash,Debit,Kartu Kredit,QRIS,Transfer,E-Wallet,Lainnya', 'transaction'],
    'pwa.app_name' => ['ApotekCare', 'pwa'],
    'pwa.theme_color' => ['#2563A6', 'pwa'],
];
$insertSetting = $pdo->prepare('INSERT INTO settings (`key`, `value`, `group`) VALUES (:key, :value, :group)');
foreach ($settings as $key => [$value, $group]) {
    $insertSetting->execute(['key' => $key, 'value' => $value, 'group' => $group]);
}
echo '- ' . count($settings) . " settings inserted\n";

$pdo->prepare('INSERT INTO printer_settings (name, paper_size, is_default, auto_print) VALUES (:name, "80mm", 1, 0)')
    ->execute(['name' => 'Printer Kasir Utama']);

// ============================================================
// 4. Master data
// ============================================================

function seedTable(PDO $pdo, string $table, array $columns, array $rows): array
{
    $ids = [];
    $placeholders = implode(', ', array_map(fn($c) => ':' . $c, $columns));
    $stmt = $pdo->prepare("INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})");
    foreach ($rows as $row) {
        $stmt->execute($row);
        $ids[] = (int) $pdo->lastInsertId();
    }
    return $ids;
}

$categoryIds = seedTable($pdo, 'categories', ['name', 'slug'], array_map(fn($n) => ['name' => $n, 'slug' => strtolower(str_replace(' ', '-', $n))], [
    'Analgesik', 'Antibiotik', 'Antasida & Pencernaan', 'Vitamin & Suplemen', 'Obat Batuk & Flu', 'Alat Kesehatan',
]));

$typeIds = seedTable($pdo, 'medicine_types', ['name'], array_map(fn($n) => ['name' => $n], [
    'Tablet', 'Kapsul', 'Sirup', 'Sirup Kering', 'Salep', 'Krim', 'Gel', 'Tetes', 'Injeksi', 'Inhaler', 'Suppositoria', 'Cairan', 'Alat Kesehatan', 'Vitamin', 'Produk Lainnya',
]));

$groupRows = [
    ['name' => 'Bebas', 'code' => 'B', 'requires_prescription' => 0],
    ['name' => 'Bebas Terbatas', 'code' => 'BT', 'requires_prescription' => 0],
    ['name' => 'Obat Keras', 'code' => 'K', 'requires_prescription' => 1],
    ['name' => 'Narkotika', 'code' => 'N', 'requires_prescription' => 1],
    ['name' => 'Psikotropika', 'code' => 'P', 'requires_prescription' => 1],
];
$groupIds = seedTable($pdo, 'medicine_groups', ['name', 'code', 'requires_prescription'], $groupRows);

$unitRows = [
    ['name' => 'Tablet', 'symbol' => 'Tab'], ['name' => 'Kapsul', 'symbol' => 'Kap'], ['name' => 'Botol', 'symbol' => 'Btl'],
    ['name' => 'Box', 'symbol' => 'Box'], ['name' => 'Strip', 'symbol' => 'Strp'], ['name' => 'Pcs', 'symbol' => 'Pcs'],
    ['name' => 'Tube', 'symbol' => 'Tube'], ['name' => 'Ampul', 'symbol' => 'Amp'],
];
$unitIds = seedTable($pdo, 'units', ['name', 'symbol'], $unitRows);

$manufacturerIds = seedTable($pdo, 'manufacturers', ['name'], array_map(fn($n) => ['name' => $n], [
    'Kimia Farma', 'Kalbe Farma', 'Sanbe Farma', 'Dexa Medica', 'Sido Muncul',
]));

$rackIds = seedTable($pdo, 'racks', ['code', 'name'], array_map(fn($c) => ['code' => $c, 'name' => 'Rak ' . $c], [
    'A1', 'A2', 'A3', 'B1', 'B2', 'B3', 'C1', 'C2',
]));

$doctorIds = seedTable($pdo, 'doctors', ['code', 'name', 'sip_number', 'specialization', 'phone'], [
    ['code' => 'DOK001', 'name' => 'dr. Andi Wijaya', 'sip_number' => 'SIP.503/001/2020', 'specialization' => 'Umum', 'phone' => '081300000001'],
    ['code' => 'DOK002', 'name' => 'dr. Siti Rahma, Sp.PD', 'sip_number' => 'SIP.503/002/2019', 'specialization' => 'Penyakit Dalam', 'phone' => '081300000002'],
    ['code' => 'DOK003', 'name' => 'dr. Budi Santoso, Sp.A', 'sip_number' => 'SIP.503/003/2021', 'specialization' => 'Anak', 'phone' => '081300000003'],
    ['code' => 'DOK004', 'name' => 'dr. Maya Kartika, Sp.THT', 'sip_number' => 'SIP.503/004/2018', 'specialization' => 'THT', 'phone' => '081300000004'],
    ['code' => 'DOK005', 'name' => 'dr. Hendra Gunawan, Sp.KK', 'sip_number' => 'SIP.503/005/2022', 'specialization' => 'Kulit & Kelamin', 'phone' => '081300000005'],
]);

$customerRows = [];
$customerNames = ['Ahmad Fauzi', 'Rina Marlina', 'Joko Susanto', 'Dewi Anggraini', 'Bambang Prasetyo', 'Sri Wahyuni', 'Agus Salim', 'Lestari Handayani'];
foreach ($customerNames as $i => $name) {
    $customerRows[] = [
        'patient_number' => 'PSN' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
        'name' => $name,
        'birth_date' => sprintf('19%02d-0%d-1%d', 60 + $i * 3, ($i % 9) + 1, $i % 8),
        'gender' => $i % 2 === 0 ? 'L' : 'P',
        'phone' => '0812' . str_pad((string) (1000000 + $i), 8, '0', STR_PAD_LEFT),
        'address' => 'Jl. Contoh No. ' . ($i + 1) . ', Jakarta',
    ];
}
$customerIds = seedTable($pdo, 'customers', ['patient_number', 'name', 'birth_date', 'gender', 'phone', 'address'], $customerRows);

$supplierRows = [
    ['code' => 'SUP001', 'name' => 'PT Kimia Farma Trading & Distribution', 'contact_person' => 'Rudi Hartono', 'phone' => '0215551001', 'payment_term_days' => 30],
    ['code' => 'SUP002', 'name' => 'PT Kalbe Farma Tbk', 'contact_person' => 'Wati Suryani', 'phone' => '0215551002', 'payment_term_days' => 30],
    ['code' => 'SUP003', 'name' => 'PT Enseval Putera Megatrading', 'contact_person' => 'Doni Prakoso', 'phone' => '0215551003', 'payment_term_days' => 45],
    ['code' => 'SUP004', 'name' => 'PT Anugrah Pharmindo Lestari', 'contact_person' => 'Nina Kusuma', 'phone' => '0215551004', 'payment_term_days' => 14],
    ['code' => 'SUP005', 'name' => 'PT Merapi Utama Pharma', 'contact_person' => 'Fajar Nugroho', 'phone' => '0215551005', 'payment_term_days' => 30],
];
$supplierIds = seedTable($pdo, 'suppliers', ['code', 'name', 'contact_person', 'phone', 'payment_term_days'], $supplierRows);

echo "- master data inserted (categories, types, groups, units, manufacturers, racks, doctors, customers, suppliers)\n";

// ============================================================
// 5. Medicines + batches (+ opening stock movements)
// ============================================================

$medicineDefs = [
    ['name' => 'Paracetamol 500mg', 'generic' => 'Paracetamol', 'type' => 'Tablet', 'group' => 'Bebas', 'cat' => 'Analgesik', 'buy' => 5000, 'sell' => 10000],
    ['name' => 'Amoxicillin 500mg', 'generic' => 'Amoxicillin', 'type' => 'Kapsul', 'group' => 'Obat Keras', 'cat' => 'Antibiotik', 'buy' => 8000, 'sell' => 15000],
    ['name' => 'Antasida Doen Tablet', 'generic' => 'Antasida', 'type' => 'Tablet', 'group' => 'Bebas', 'cat' => 'Antasida & Pencernaan', 'buy' => 3000, 'sell' => 7000],
    ['name' => 'Cetirizine 10mg', 'generic' => 'Cetirizine', 'type' => 'Tablet', 'group' => 'Bebas Terbatas', 'cat' => 'Obat Batuk & Flu', 'buy' => 4000, 'sell' => 9000],
    ['name' => 'Vitamin C 500mg', 'generic' => 'Ascorbic Acid', 'type' => 'Tablet', 'group' => 'Bebas', 'cat' => 'Vitamin & Suplemen', 'buy' => 6000, 'sell' => 12000],
    ['name' => 'Ibuprofen 400mg', 'generic' => 'Ibuprofen', 'type' => 'Tablet', 'group' => 'Bebas Terbatas', 'cat' => 'Analgesik', 'buy' => 5500, 'sell' => 11000],
    ['name' => 'Omeprazole 20mg', 'generic' => 'Omeprazole', 'type' => 'Kapsul', 'group' => 'Obat Keras', 'cat' => 'Antasida & Pencernaan', 'buy' => 9000, 'sell' => 17000],
    ['name' => 'Loperamide 2mg', 'generic' => 'Loperamide', 'type' => 'Tablet', 'group' => 'Obat Keras', 'cat' => 'Antasida & Pencernaan', 'buy' => 7000, 'sell' => 13000],
    ['name' => 'Ambroxol Syrup 60ml', 'generic' => 'Ambroxol', 'type' => 'Sirup', 'group' => 'Bebas Terbatas', 'cat' => 'Obat Batuk & Flu', 'buy' => 12000, 'sell' => 22000],
    ['name' => 'OBH Combi Syrup 100ml', 'generic' => 'Guaifenesin Combi', 'type' => 'Sirup', 'group' => 'Bebas', 'cat' => 'Obat Batuk & Flu', 'buy' => 14000, 'sell' => 25000],
    ['name' => 'Betadine Solution 60ml', 'generic' => 'Povidone Iodine', 'type' => 'Cairan', 'group' => 'Bebas', 'cat' => 'Alat Kesehatan', 'buy' => 18000, 'sell' => 32000],
    ['name' => 'Salbutamol Inhaler', 'generic' => 'Salbutamol', 'type' => 'Inhaler', 'group' => 'Obat Keras', 'cat' => 'Obat Batuk & Flu', 'buy' => 35000, 'sell' => 60000],
    ['name' => 'Dexamethasone 0.5mg', 'generic' => 'Dexamethasone', 'type' => 'Tablet', 'group' => 'Obat Keras', 'cat' => 'Analgesik', 'buy' => 4500, 'sell' => 9500],
    ['name' => 'Metformin 500mg', 'generic' => 'Metformin', 'type' => 'Tablet', 'group' => 'Obat Keras', 'cat' => 'Antasida & Pencernaan', 'buy' => 6000, 'sell' => 12500],
    ['name' => 'Amlodipine 5mg', 'generic' => 'Amlodipine', 'type' => 'Tablet', 'group' => 'Obat Keras', 'cat' => 'Analgesik', 'buy' => 7000, 'sell' => 14000],
    ['name' => 'Simvastatin 10mg', 'generic' => 'Simvastatin', 'type' => 'Tablet', 'group' => 'Obat Keras', 'cat' => 'Analgesik', 'buy' => 8500, 'sell' => 16000],
    ['name' => 'CTM 4mg', 'generic' => 'Chlorpheniramine Maleate', 'type' => 'Tablet', 'group' => 'Bebas', 'cat' => 'Obat Batuk & Flu', 'buy' => 2000, 'sell' => 5000],
    ['name' => 'Vitamin B Complex', 'generic' => 'Vitamin B Complex', 'type' => 'Tablet', 'group' => 'Bebas', 'cat' => 'Vitamin & Suplemen', 'buy' => 5000, 'sell' => 10000],
    ['name' => 'Alcohol Swab Box', 'generic' => null, 'type' => 'Alat Kesehatan', 'group' => 'Bebas', 'cat' => 'Alat Kesehatan', 'buy' => 15000, 'sell' => 28000],
    ['name' => 'Masker Medis Box isi 50', 'generic' => null, 'type' => 'Alat Kesehatan', 'group' => 'Bebas', 'cat' => 'Alat Kesehatan', 'buy' => 25000, 'sell' => 45000],
];

function idByName(array $ids, array $names, string $name): int
{
    $index = array_search($name, $names, true);
    return $ids[$index];
}

$categoryNames = ['Analgesik', 'Antibiotik', 'Antasida & Pencernaan', 'Vitamin & Suplemen', 'Obat Batuk & Flu', 'Alat Kesehatan'];
$typeNames = ['Tablet', 'Kapsul', 'Sirup', 'Sirup Kering', 'Salep', 'Krim', 'Gel', 'Tetes', 'Injeksi', 'Inhaler', 'Suppositoria', 'Cairan', 'Alat Kesehatan', 'Vitamin', 'Produk Lainnya'];
$groupNames = ['Bebas', 'Bebas Terbatas', 'Obat Keras', 'Narkotika', 'Psikotropika'];

$medicineIds = [];
$insertMedicine = $pdo->prepare(
    'INSERT INTO medicines (code, name, generic_name, category_id, medicine_type_id, medicine_group_id, manufacturer_id, unit_id, content_per_unit, purchase_price, selling_price, selling_price_prescription, minimum_stock, maximum_stock, rack_id, status)
     VALUES (:code, :name, :generic_name, :category_id, :medicine_type_id, :medicine_group_id, :manufacturer_id, :unit_id, 1, :purchase_price, :selling_price, :selling_price_prescription, :minimum_stock, :maximum_stock, :rack_id, "active")'
);
foreach ($medicineDefs as $i => $def) {
    $code = 'OBT' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
    $insertMedicine->execute([
        'code' => $code,
        'name' => $def['name'],
        'generic_name' => $def['generic'],
        'category_id' => idByName($categoryIds, $categoryNames, $def['cat']),
        'medicine_type_id' => idByName($typeIds, $typeNames, $def['type']),
        'medicine_group_id' => idByName($groupIds, $groupNames, $def['group']),
        'manufacturer_id' => $manufacturerIds[$i % count($manufacturerIds)],
        'unit_id' => $unitIds[0],
        'purchase_price' => $def['buy'],
        'selling_price' => $def['sell'],
        'selling_price_prescription' => (int) round($def['sell'] * 0.95),
        'minimum_stock' => 15,
        'maximum_stock' => 300,
        'rack_id' => $rackIds[$i % count($rackIds)],
    ]);
    $medicineIds[] = (int) $pdo->lastInsertId();
}
echo '- ' . count($medicineIds) . " medicines inserted\n";

// A few medicines get a real-looking EAN barcode already assigned; the rest
// stay blank so the Barcode module has real work to do (§9 — never silent).
$sampleBarcodes = ['8991002123457', '8991002123464', '8991002123471', '8991002123488', '8991002123495'];
foreach ($sampleBarcodes as $i => $barcode) {
    $pdo->prepare('UPDATE medicines SET barcode = :barcode WHERE id = :id')
        ->execute(['barcode' => $barcode, 'id' => $medicineIds[$i]]);
}

$today = new DateTime('today');
$insertBatch = $pdo->prepare(
    'INSERT INTO medicine_batches (medicine_id, batch_number, received_date, production_date, expired_date, purchase_price, selling_price, initial_qty, available_qty, status)
     VALUES (:medicine_id, :batch_number, :received_date, :production_date, :expired_date, :purchase_price, :selling_price, :initial_qty, 0, "active")'
);

// Expiry spread across demo thresholds so the dashboard "Akan Expired"
// widget and Batch module have real data at every severity level.
$expiryOffsets = [730, 730, 400, 400, 85, 85, 55, 55, 25, 25, 5, 5, -10, 730, 400, 85, 55, 25, 730, 400];

$batchCount = 0;
foreach ($medicineIds as $i => $medicineId) {
    $def = $medicineDefs[$i];
    $batchesForThisMedicine = ($i < 12) ? 2 : 1;
    for ($b = 0; $b < $batchesForThisMedicine; $b++) {
        $offsetIndex = ($i * 2 + $b) % count($expiryOffsets);
        $daysOffset = $expiryOffsets[$offsetIndex];
        $expiredDate = (clone $today)->modify("+{$daysOffset} days")->format('Y-m-d');
        $receivedDate = (clone $today)->modify('-' . random_int(10, 90) . ' days')->format('Y-m-d');
        $productionDate = (clone $today)->modify('-' . random_int(120, 300) . ' days')->format('Y-m-d');
        $qty = random_int(60, 220);
        $batchNumber = 'B' . strtoupper(substr(md5($medicineId . '-' . $b), 0, 6));

        $insertBatch->execute([
            'medicine_id' => $medicineId,
            'batch_number' => $batchNumber,
            'received_date' => $receivedDate,
            'production_date' => $productionDate,
            'expired_date' => $expiredDate,
            'purchase_price' => $def['buy'],
            'selling_price' => $def['sell'],
            'initial_qty' => $qty,
        ]);
        $batchId = (int) $pdo->lastInsertId();

        StockLedgerService::moveTransactional(
            $medicineId,
            $batchId,
            'opening_balance',
            (float) $qty,
            0,
            'batch',
            $batchId,
            'Stok awal batch ' . $batchNumber,
            $userIds['gudang']
        );
        $batchCount++;
    }
}
echo "- {$batchCount} batches inserted with opening stock movements\n";

// ============================================================
// 6. Sample sales for the last 30 days (feeds dashboard charts;
// full POS UI ships in a later phase — these prove the ledger works)
// ============================================================

$paymentMethods = ['Cash', 'Cash', 'Cash', 'Debit', 'QRIS', 'Transfer'];
$invoiceCounterPerDay = [];
$salesCreated = 0;

$insertSale = $pdo->prepare(
    'INSERT INTO sales (invoice_number, uuid, transaction_date, cashier_id, subtotal, discount, tax, grand_total, paid_amount, change_amount, payment_method, status, sync_status)
     VALUES (:invoice_number, :uuid, :transaction_date, :cashier_id, :subtotal, 0, 0, :grand_total, :paid_amount, :change_amount, :payment_method, "completed", "synced")'
);
$insertSaleDetail = $pdo->prepare(
    'INSERT INTO sale_details (sale_id, medicine_id, batch_id, qty, unit_price, discount, subtotal) VALUES (:sale_id, :medicine_id, :batch_id, :qty, :unit_price, 0, :subtotal)'
);
$insertPayment = $pdo->prepare('INSERT INTO payments (sale_id, method, amount, paid_at) VALUES (:sale_id, :method, :amount, :paid_at)');

// FEFO-eligible batches: active, not expired, with stock.
$eligibleBatchesStmt = $pdo->query(
    "SELECT mb.id AS batch_id, mb.medicine_id, mb.available_qty, m.selling_price
     FROM medicine_batches mb JOIN medicines m ON m.id = mb.medicine_id
     WHERE mb.status = 'active' AND mb.expired_date >= CURDATE() AND mb.available_qty > 5
     ORDER BY mb.medicine_id, mb.expired_date ASC"
);
$eligibleByMedicine = [];
foreach ($eligibleBatchesStmt->fetchAll() as $row) {
    $eligibleByMedicine[$row['medicine_id']][] = $row;
}
$eligibleMedicineIds = array_keys($eligibleByMedicine);

for ($dayAgo = 29; $dayAgo >= 0; $dayAgo--) {
    $date = (clone $today)->modify("-{$dayAgo} days");
    $salesToday = random_int(1, 4);

    for ($s = 0; $s < $salesToday; $s++) {
        $dateKey = $date->format('Ymd');
        $invoiceCounterPerDay[$dateKey] = ($invoiceCounterPerDay[$dateKey] ?? 0) + 1;
        $invoiceNumber = 'INV-' . $dateKey . '-' . str_pad((string) $invoiceCounterPerDay[$dateKey], 5, '0', STR_PAD_LEFT);

        $lineCount = random_int(1, 3);
        $pickedMedicines = (array) array_rand(array_flip($eligibleMedicineIds), min($lineCount, count($eligibleMedicineIds)));
        if (!is_array($pickedMedicines)) {
            $pickedMedicines = [$pickedMedicines];
        }

        $lines = [];
        $subtotal = 0;
        foreach ($pickedMedicines as $medicineId) {
            if (empty($eligibleByMedicine[$medicineId])) {
                continue;
            }
            $batch = $eligibleByMedicine[$medicineId][0];
            $qty = random_int(1, 3);
            if ($qty > $batch['available_qty'] - 5) {
                continue;
            }
            $unitPrice = (float) $batch['selling_price'];
            $lineSubtotal = $unitPrice * $qty;
            $lines[] = ['medicine_id' => $medicineId, 'batch_id' => $batch['batch_id'], 'qty' => $qty, 'unit_price' => $unitPrice, 'subtotal' => $lineSubtotal];
            $subtotal += $lineSubtotal;
            $eligibleByMedicine[$medicineId][0]['available_qty'] -= $qty;
        }

        if (empty($lines)) {
            continue;
        }

        $grandTotal = $subtotal;
        $paidAmount = $grandTotal;
        $changeAmount = 0;
        $method = $paymentMethods[array_rand($paymentMethods)];
        $transactionDateTime = $date->format('Y-m-d') . ' ' . sprintf('%02d:%02d:00', random_int(8, 20), random_int(0, 59));

        $insertSale->execute([
            'invoice_number' => $invoiceNumber,
            'uuid' => sprintf('%08x-%04x-4%03x-%04x-%012x', mt_rand(0, 0xffffffff), mt_rand(0, 0xffff), mt_rand(0, 0xfff), mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffffffffffff)),
            'transaction_date' => $transactionDateTime,
            'cashier_id' => $userIds['kasir'],
            'subtotal' => $subtotal,
            'grand_total' => $grandTotal,
            'paid_amount' => $paidAmount,
            'change_amount' => $changeAmount,
            'payment_method' => $method,
        ]);
        $saleId = (int) $pdo->lastInsertId();

        $insertPayment->execute(['sale_id' => $saleId, 'method' => $method, 'amount' => $paidAmount, 'paid_at' => $transactionDateTime]);

        foreach ($lines as $line) {
            $insertSaleDetail->execute([
                'sale_id' => $saleId,
                'medicine_id' => $line['medicine_id'],
                'batch_id' => $line['batch_id'],
                'qty' => $line['qty'],
                'unit_price' => $line['unit_price'],
                'subtotal' => $line['subtotal'],
            ]);
            StockLedgerService::moveTransactional(
                $line['medicine_id'],
                $line['batch_id'],
                'sale',
                0,
                (float) $line['qty'],
                'sale',
                $saleId,
                'Penjualan ' . $invoiceNumber,
                $userIds['kasir']
            );
        }
        $salesCreated++;
    }
}
echo "- {$salesCreated} sample sales inserted across the last 30 days\n";

// Keep the atomic invoice counter (used by POS checkout, App\Services\SalesService)
// in sync with the invoice numbers this seeder already handed out — otherwise the
// first real sale of the day collides with a seeded invoice_number.
$insertCounter = $pdo->prepare('INSERT INTO invoice_counters (counter_date, last_number) VALUES (:d, :n) ON DUPLICATE KEY UPDATE last_number = GREATEST(last_number, :n2)');
foreach ($invoiceCounterPerDay as $dateKey => $count) {
    $isoDate = DateTime::createFromFormat('Ymd', $dateKey)->format('Y-m-d');
    $insertCounter->execute(['d' => $isoDate, 'n' => $count, 'n2' => $count]);
}
echo '- invoice_counters synced for ' . count($invoiceCounterPerDay) . " day(s)\n";

echo "\nSeeding completed successfully.\n";
echo "Demo accounts (password for all: Apotek#2026 — MUST be changed in production):\n";
foreach ($users as $u) {
    echo "  - {$u['username']} ({$u['role']})\n";
}
