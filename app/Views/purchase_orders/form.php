<div class="breadcrumb-trail"><a href="/purchase-orders">Purchase Order</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Buat Baru</div>
<div class="page-header">
    <div>
        <p class="page-title">Buat Purchase Order</p>
        <p class="page-subtitle">Pesan barang ke supplier sebelum penerimaan.</p>
    </div>
</div>

<form method="POST" action="/purchase-orders" data-loading-text="Menyimpan...">
    <?= csrf_field() ?>
    <div class="card-surface card-pad" style="margin-bottom:16px;">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Supplier<span class="required">*</span></label>
                <select name="supplier_id" class="form-select" required>
                    <option value="">Pilih supplier...</option>
                    <?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Diharapkan Tiba</label>
                <input type="date" name="expected_date" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
    </div>

    <div class="card-surface card-pad" id="poLineItems" data-line-items>
        <p class="section-card-title">Item Pesanan <button type="button" class="btn btn-sm btn-soft" data-add-line="poLineItems">+ Tambah Baris</button></p>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Obat</th><th style="width:110px;">Qty</th><th style="width:160px;">Harga Satuan</th><th style="width:40px;"></th></tr></thead>
            <tbody></tbody>
        </table>
        </div>
        <template>
            <tr>
                <td>
                    <select name="medicine_id[]" class="form-select" required>
                        <option value="">Pilih obat...</option>
                        <?php foreach ($medicines as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['name']) ?> (<?= e($m['code']) ?>)</option><?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" step="0.01" name="qty[]" class="form-control" required></td>
                <td><input type="number" step="1" name="unit_price[]" class="form-control" required></td>
                <td><button type="button" class="btn btn-icon btn-ghost" data-remove-line><svg class="icon"><use href="/assets/icons/sprite.svg#ic-trash"></use></svg></button></td>
            </tr>
        </template>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
        <a href="/purchase-orders" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Purchase Order</button>
    </div>
</form>
<script src="/assets/js/line-items.js" defer></script>
