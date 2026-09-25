<div class="breadcrumb-trail"><a href="/prescriptions">Resep</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Input Baru</div>
<div class="page-header">
    <div>
        <p class="page-title">Input Resep Baru</p>
        <p class="page-subtitle">Resep akan berstatus "Menunggu Verifikasi" hingga diverifikasi apoteker.</p>
    </div>
</div>

<form method="POST" action="/prescriptions" data-loading-text="Menyimpan...">
    <?= csrf_field() ?>
    <div class="card-surface card-pad" style="margin-bottom:16px;">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Pasien<span class="required">*</span></label>
                <select name="customer_id" class="form-select" required>
                    <option value="">Pilih pasien...</option>
                    <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['patient_number']) ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Dokter<span class="required">*</span></label>
                <select name="doctor_id" class="form-select" required>
                    <option value="">Pilih dokter...</option>
                    <?php foreach ($doctors as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
    </div>

    <div class="card-surface card-pad" id="rxLineItems" data-line-items>
        <p class="section-card-title">Item Obat <button type="button" class="btn btn-sm btn-soft" data-add-line="rxLineItems">+ Tambah Baris</button></p>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Obat</th><th>Dosis</th><th>Frekuensi</th><th>Qty</th><th>Aturan Pakai</th><th style="width:40px;"></th></tr></thead>
            <tbody></tbody>
        </table>
        </div>
        <template>
            <tr>
                <td>
                    <select name="medicine_id[]" class="form-select" required style="min-width:150px;">
                        <option value="">Pilih obat...</option>
                        <?php foreach ($medicines as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" name="dosage[]" class="form-control" placeholder="500mg" style="width:100px;"></td>
                <td><input type="text" name="frequency[]" class="form-control" placeholder="3x1" style="width:90px;"></td>
                <td><input type="number" step="1" name="qty[]" class="form-control" required style="width:80px;"></td>
                <td><input type="text" name="usage_instructions[]" class="form-control" placeholder="Sesudah makan" style="min-width:140px;"></td>
                <td><button type="button" class="btn btn-icon btn-ghost" data-remove-line><svg class="icon"><use href="/assets/icons/sprite.svg#ic-trash"></use></svg></button></td>
            </tr>
        </template>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
        <a href="/prescriptions" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Resep</button>
    </div>
</form>
<script src="/assets/js/line-items.js" defer></script>
