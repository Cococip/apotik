<div class="breadcrumb-trail"><a href="/purchases">Pembelian</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Input Baru</div>
<div class="page-header">
    <div>
        <p class="page-title">Input Pembelian</p>
        <p class="page-subtitle">Mencatat penerimaan barang — stok otomatis bertambah setelah disimpan.</p>
    </div>
</div>

<form method="POST" action="/purchases" data-loading-text="Menyimpan..." id="purchaseForm">
    <?= csrf_field() ?>
    <?php if ($po): ?><input type="hidden" name="purchase_order_id" value="<?= $po['id'] ?>"><?php endif; ?>

    <div class="card-surface card-pad" style="margin-bottom:16px;">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Supplier<span class="required">*</span></label>
                <select name="supplier_id" class="form-select" required <?= $po ? 'disabled' : '' ?>>
                    <option value="">Pilih supplier...</option>
                    <?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>" <?= $po && $po['supplier_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
                <?php if ($po): ?><input type="hidden" name="supplier_id" value="<?= $po['supplier_id'] ?>"><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">No. Invoice Supplier</label>
                <input type="text" name="supplier_invoice_number" class="form-control">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tanggal Pembelian<span class="required">*</span></label>
                <input type="date" name="purchase_date" class="form-control" value="<?= e($today) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Jatuh Tempo</label>
                <input type="date" name="due_date" class="form-control">
            </div>
        </div>
    </div>

    <div class="card-surface card-pad" id="purchaseLineItems" data-line-items>
        <p class="section-card-title">Item Diterima <button type="button" class="btn btn-sm btn-soft" data-add-line="purchaseLineItems">+ Tambah Baris</button></p>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Obat</th><th>No. Batch</th><th>Expired</th><th>Qty</th><th>Harga Beli</th><th>Harga Jual</th><th style="width:40px;"></th></tr></thead>
            <tbody></tbody>
        </table>
        </div>
        <template>
            <tr>
                <td>
                    <select name="medicine_id[]" class="form-select purchase-medicine-select" required style="min-width:160px;">
                        <option value="">Pilih obat...</option>
                        <?php foreach ($medicines as $m): ?><option value="<?= $m['id'] ?>" data-price="<?= $m['purchase_price'] ?>" data-sell="<?= $m['selling_price'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" name="batch_number[]" class="form-control" required style="min-width:110px;"></td>
                <td><input type="date" name="expired_date[]" class="form-control" required style="min-width:140px;"></td>
                <td><input type="number" step="0.01" name="qty[]" class="form-control" required style="width:90px;"></td>
                <td><input type="number" step="1" name="purchase_price[]" class="form-control purchase-price-input" required style="width:110px;"></td>
                <td><input type="number" step="1" name="selling_price[]" class="form-control sell-price-input" style="width:110px;"></td>
                <td><button type="button" class="btn btn-icon btn-ghost" data-remove-line><svg class="icon"><use href="/assets/icons/sprite.svg#ic-trash"></use></svg></button></td>
            </tr>
        </template>
    </div>

    <div class="card-surface card-pad" style="max-width:400px;margin-top:16px;">
        <div class="form-group">
            <label class="form-label">Jumlah Dibayar Sekarang</label>
            <input type="number" step="1" name="paid_now" class="form-control" value="0">
            <p class="form-help">Kosongkan/0 jika kredit penuh (hutang).</p>
        </div>
        <div class="form-group">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
        <a href="/purchases" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Pembelian</button>
    </div>
</form>

<script>
window.__PO_PREFILL = <?= json_encode($poDetails) ?>;
</script>
<script src="/assets/js/line-items.js" defer></script>
<script src="/assets/js/purchase-form.js" defer></script>
