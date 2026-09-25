<?php
/** @var array|null $medicine */
/** @var string|null $nextCode */
use App\Core\Auth;
use App\Services\ExpiryStatusService;

$isEdit = $medicine !== null;
$val = function (string $field, $default = '') use ($medicine) {
    $old = old($field, null);
    if ($old !== null) return $old;
    return $medicine[$field] ?? $default;
};
?>
<div class="breadcrumb-trail"><a href="/medicines">Daftar Obat</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> <?= $isEdit ? e($medicine['name']) : 'Tambah Obat' ?></div>
<div class="page-header">
    <div>
        <p class="page-title"><?= $isEdit ? 'Edit Obat' : 'Tambah Obat' ?></p>
        <p class="page-subtitle"><?= $isEdit ? 'Kode: ' . e($medicine['code']) : 'Lengkapi data obat baru.' ?></p>
    </div>
    <?php if ($isEdit): ?>
        <span class="badge <?= $medicine['stock'] <= $medicine['minimum_stock'] ? 'badge-warning' : 'badge-success' ?>">Stok saat ini: <?= (int) $medicine['stock'] ?></span>
    <?php endif; ?>
</div>

<div class="card-surface card-pad" style="margin-bottom:20px;">
    <form method="POST" action="<?= $isEdit ? '/medicines/' . $medicine['id'] : '/medicines' ?>" data-loading-text="Menyimpan...">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

        <p class="section-card-title">Informasi Umum</p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Kode Obat</label>
                <input type="text" name="code" class="form-control" value="<?= e($val('code', $nextCode)) ?>" placeholder="Otomatis jika kosong">
            </div>
            <div class="form-group">
                <label class="form-label">Barcode</label>
                <input type="text" name="barcode" class="form-control" value="<?= e($val('barcode')) ?>" placeholder="Kosongkan jika belum ada, buat di menu Barcode">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nama Obat<span class="required">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($val('name')) ?>" required>
                <?php if ($err = field_error('name')): ?><div class="form-error"><?= e($err) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Generik</label>
                <input type="text" name="generic_name" class="form-control" value="<?= e($val('generic_name')) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nama Dagang</label>
                <input type="text" name="brand_name" class="form-control" value="<?= e($val('brand_name')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $val('status', 'active') === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $val('status') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
        </div>

        <p class="section-card-title" style="margin-top:22px;">Klasifikasi</p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select name="category_id" class="form-select">
                    <option value="">-</option>
                    <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $val('category_id') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Jenis Obat</label>
                <select name="medicine_type_id" class="form-select">
                    <option value="">-</option>
                    <?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= $val('medicine_type_id') == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Golongan Obat</label>
                <select name="medicine_group_id" class="form-select">
                    <option value="">-</option>
                    <?php foreach ($groups as $g): ?><option value="<?= $g['id'] ?>" <?= $val('medicine_group_id') == $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Produsen</label>
                <select name="manufacturer_id" class="form-select">
                    <option value="">-</option>
                    <?php foreach ($manufacturers as $m): ?><option value="<?= $m['id'] ?>" <?= $val('manufacturer_id') == $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>

        <p class="section-card-title" style="margin-top:22px;">Satuan &amp; Harga</p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Satuan Kecil<span class="required">*</span></label>
                <select name="unit_id" class="form-select" required>
                    <option value="">-</option>
                    <?php foreach ($units as $u): ?><option value="<?= $u['id'] ?>" <?= $val('unit_id') == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?> (<?= e($u['symbol']) ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Satuan Besar</label>
                <select name="large_unit_id" class="form-select">
                    <option value="">-</option>
                    <?php foreach ($units as $u): ?><option value="<?= $u['id'] ?>" <?= $val('large_unit_id') == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?> (<?= e($u['symbol']) ?>)</option><?php endforeach; ?>
                </select>
                <p class="form-help">Contoh: 1 Box berisi 10 Strip — kelola rasio pada bagian Konversi Satuan di bawah.</p>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Harga Beli<span class="required">*</span></label>
                <input type="number" step="1" name="purchase_price" class="form-control" value="<?= e($val('purchase_price', 0)) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Harga Jual<span class="required">*</span></label>
                <input type="number" step="1" name="selling_price" class="form-control" value="<?= e($val('selling_price', 0)) ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Harga Jual (dengan Resep)</label>
                <input type="number" step="1" name="selling_price_prescription" class="form-control" value="<?= e($val('selling_price_prescription', 0)) ?>">
                <p class="form-help">Kosongkan untuk mengikuti harga jual normal.</p>
            </div>
            <div class="form-group">
                <label class="form-label">Lokasi Rak</label>
                <select name="rack_id" class="form-select">
                    <option value="">-</option>
                    <?php foreach ($racks as $r): ?><option value="<?= $r['id'] ?>" <?= $val('rack_id') == $r['id'] ? 'selected' : '' ?>><?= e($r['code']) ?> — <?= e($r['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>

        <p class="section-card-title" style="margin-top:22px;">Stok</p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Minimum Stok</label>
                <input type="number" name="minimum_stock" class="form-control" value="<?= e($val('minimum_stock', 10)) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Maksimum Stok</label>
                <input type="number" name="maximum_stock" class="form-control" value="<?= e($val('maximum_stock', 100)) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control" rows="3"><?= e($val('description')) ?></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
            <a href="/medicines" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Obat</button>
        </div>
    </form>
</div>

<?php if ($isEdit): ?>
<div class="chart-grid" style="grid-template-columns:1.3fr 1fr;">
    <div class="card-surface card-pad">
        <p class="section-card-title">Batch &amp; Expired <a href="/stock/card/<?= $medicine['id'] ?>" class="btn btn-sm btn-soft" style="font-weight:600;">Lihat Kartu Stok</a></p>
        <p class="section-card-subtitle">Diurutkan FEFO (First Expired First Out).</p>
        <?php if (empty($batches)): ?>
            <div class="empty-state" style="padding:24px 8px;"><p>Belum ada batch untuk obat ini.</p></div>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Batch</th><th>Diterima</th><th>Expired</th><th>Qty</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($batches as $batch): $es = ExpiryStatusService::statusFor($batch['expired_date']); ?>
                <tr>
                    <td style="font-weight:600;"><?= e($batch['batch_number']) ?></td>
                    <td class="table-cell-muted"><?= e(format_date($batch['received_date'])) ?></td>
                    <td class="table-cell-muted"><?= e(format_date($batch['expired_date'])) ?></td>
                    <td><?= (int) $batch['available_qty'] ?></td>
                    <td>
                        <?php if ($batch['status'] === 'recalled'): ?><span class="badge badge-muted">Rusak/Ditarik</span>
                        <?php elseif ($batch['status'] === 'depleted'): ?><span class="badge badge-muted">Habis</span>
                        <?php else: ?><span class="badge <?= e($es['badge']) ?>"><?= e($es['label']) ?></span><?php endif; ?>
                    </td>
                    <td class="table-actions">
                        <?php if ($batch['status'] === 'active' && Auth::can('medicine.batch.manage')): ?>
                        <form method="POST" action="/batches/<?= $batch['id'] ?>/status" data-confirm="Tandai batch ini rusak? Stok akan dinolkan." style="display:inline;">
                            <?= csrf_field() ?><input type="hidden" name="status" value="damaged">
                            <button type="submit" class="btn btn-sm btn-ghost">Tandai Rusak</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>

        <?php if (Auth::can('medicine.batch.manage')): ?>
        <hr style="border-color:var(--color-border);margin:16px 0;">
        <p class="section-card-title" style="font-size:13px;">Tambah Batch Baru</p>
        <form method="POST" action="/medicines/<?= $medicine['id'] ?>/batches" data-loading-text="Menyimpan...">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nomor Batch<span class="required">*</span></label>
                    <input type="text" name="batch_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Qty Awal<span class="required">*</span></label>
                    <input type="number" step="0.01" name="initial_qty" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tanggal Diterima<span class="required">*</span></label>
                    <input type="date" name="received_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Expired<span class="required">*</span></label>
                    <input type="date" name="expired_date" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Harga Beli Batch</label>
                    <input type="number" step="1" name="purchase_price" class="form-control" placeholder="<?= e(format_money($medicine['purchase_price'])) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Harga Jual Batch</label>
                    <input type="number" step="1" name="selling_price" class="form-control" placeholder="<?= e(format_money($medicine['selling_price'])) ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Tambah Batch</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="card-surface card-pad">
        <p class="section-card-title">Konversi Satuan</p>
        <p class="section-card-subtitle">Rasio antar satuan untuk obat ini.</p>
        <?php if (empty($unitConversions)): ?>
            <div class="empty-state" style="padding:20px 8px;"><p>Belum ada konversi satuan.</p></div>
        <?php else: ?>
            <?php foreach ($unitConversions as $uc): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--color-border);font-size:13px;">
                    <span>1 <?= e($uc['from_unit_name']) ?> = <?= e($uc['conversion_factor']) ?> <?= e($uc['to_unit_name']) ?></span>
                    <?php if (Auth::can('medicine.update')): ?>
                    <form method="POST" action="/unit-conversions/<?= $uc['id'] ?>" data-confirm="Hapus konversi ini?">
                        <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-icon btn-ghost"><svg class="icon" style="width:15px;height:15px;"><use href="/assets/icons/sprite.svg#ic-trash"></use></svg></button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (Auth::can('medicine.update')): ?>
        <form method="POST" action="/medicines/<?= $medicine['id'] ?>/unit-conversions" style="margin-top:14px;" data-loading-text="Menyimpan...">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label">Dari Satuan</label>
                <select name="from_unit_id" class="form-select" required>
                    <?php foreach ($units as $u): ?><option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Ke Satuan</label>
                <select name="to_unit_id" class="form-select" required>
                    <?php foreach ($units as $u): ?><option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Faktor Konversi</label>
                <input type="number" step="0.01" name="conversion_factor" class="form-control" placeholder="Contoh: 10" required>
            </div>
            <button type="submit" class="btn btn-soft" style="width:100%;justify-content:center;">+ Tambah Konversi</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
