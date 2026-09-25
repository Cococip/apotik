<div class="page-header">
    <div>
        <p class="page-title">Pengaturan Printer</p>
        <p class="page-subtitle">Konfigurasi printer struk (§21). Digunakan penuh mulai modul Kasir di Fase 2.</p>
    </div>
</div>

<div class="card-surface card-pad" style="max-width:560px;">
    <form method="POST" action="/settings/printer" data-loading-text="Menyimpan...">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label">Nama Printer</label>
            <input type="text" name="name" class="form-control" value="<?= e($printer['name'] ?? 'Printer Kasir Utama') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Ukuran Kertas</label>
            <select name="paper_size" class="form-select">
                <option value="58mm" <?= ($printer['paper_size'] ?? '80mm') === '58mm' ? 'selected' : '' ?>>58mm</option>
                <option value="80mm" <?= ($printer['paper_size'] ?? '80mm') === '80mm' ? 'selected' : '' ?>>80mm</option>
            </select>
        </div>
        <div class="form-check" style="margin-bottom:16px;">
            <input type="checkbox" name="auto_print" id="autoPrint" <?= !empty($printer['auto_print']) ? 'checked' : '' ?>>
            <label for="autoPrint" class="form-label" style="margin-bottom:0;">Cetak struk otomatis setelah transaksi</label>
        </div>
        <div class="form-group">
            <label class="form-label">Header Struk</label>
            <textarea name="header_text" class="form-control" rows="2"><?= e($printer['header_text'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Footer Struk</label>
            <textarea name="footer_text" class="form-control" rows="2"><?= e($printer['footer_text'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>
