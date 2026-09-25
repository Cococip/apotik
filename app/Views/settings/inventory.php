<div class="page-header">
    <div>
        <p class="page-title">Pengaturan Persediaan</p>
        <p class="page-subtitle">Ambang batas stok minimum default dan peringatan expired (§7).</p>
    </div>
</div>

<div class="card-surface card-pad" style="max-width:640px;">
    <form method="POST" action="/settings/inventory" data-loading-text="Menyimpan...">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label">Stok Minimum Default (obat baru)</label>
            <input type="number" name="default_min_stock" class="form-control" value="<?= e($settings['inventory.default_min_stock'] ?? 10) ?>">
        </div>

        <p class="section-card-title" style="margin-top:20px;">Ambang Peringatan Expired (hari)</p>
        <p class="section-card-subtitle">Batch akan ditandai sesuai tingkat keparahan berdasarkan sisa hari menuju expired.</p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Pantau (badge info)</label>
                <input type="number" name="expiry_watch_days" class="form-control" value="<?= e($settings['inventory.expiry_watch_days'] ?? 90) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Perhatian</label>
                <input type="number" name="expiry_medium_days" class="form-control" value="<?= e($settings['inventory.expiry_medium_days'] ?? 60) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Segera Expired</label>
                <input type="number" name="expiry_high_days" class="form-control" value="<?= e($settings['inventory.expiry_high_days'] ?? 30) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Kritis</label>
                <input type="number" name="expiry_critical_days" class="form-control" value="<?= e($settings['inventory.expiry_critical_days'] ?? 7) ?>">
            </div>
        </div>

        <div class="form-check" style="margin:16px 0;">
            <input type="checkbox" name="fefo_enabled" id="fefo" <?= ($settings['inventory.fefo_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
            <label for="fefo" class="form-label" style="margin-bottom:0;">Aktifkan FEFO (First Expired First Out)</label>
        </div>
        <p class="form-help" style="margin-top:-10px;margin-bottom:16px;">FEFO memastikan batch dengan expired paling dekat selalu diprioritaskan saat penjualan (aktif digunakan mulai modul POS di Fase 2).</p>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>
