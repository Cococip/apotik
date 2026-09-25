<div class="page-header">
    <div>
        <p class="page-title">Profil Apotek</p>
        <p class="page-subtitle">Identitas apotek yang tampil di aplikasi dan struk.</p>
    </div>
</div>

<div class="card-surface card-pad" style="max-width:640px;">
    <form method="POST" action="/settings/pharmacy" data-loading-text="Menyimpan...">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label">Nama Apotek<span class="required">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= e($pharmacy['name'] ?? 'ApotekCare') ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Alamat</label>
            <textarea name="address" class="form-control" rows="2"><?= e($pharmacy['address'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Telepon</label>
                <input type="text" name="phone" class="form-control" value="<?= e($pharmacy['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($pharmacy['email'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">NPWP</label>
            <input type="text" name="npwp" class="form-control" value="<?= e($pharmacy['npwp'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Footer Struk</label>
            <textarea name="receipt_footer" class="form-control" rows="2" placeholder="Contoh: Terima kasih atas kunjungan Anda."><?= e($pharmacy['receipt_footer'] ?? '') ?></textarea>
            <p class="form-help">Teks ini akan tampil di bagian bawah struk penjualan.</p>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>
