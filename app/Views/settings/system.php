<div class="page-header">
    <div>
        <p class="page-title">Pengaturan Sistem</p>
        <p class="page-subtitle">Zona waktu, mata uang, dan format tanggal.</p>
    </div>
</div>

<div class="card-surface card-pad" style="max-width:560px;">
    <form method="POST" action="/settings/system" data-loading-text="Menyimpan...">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label">Zona Waktu</label>
            <select name="timezone" class="form-select">
                <?php foreach (['Asia/Jakarta' => 'WIB (Asia/Jakarta)', 'Asia/Makassar' => 'WITA (Asia/Makassar)', 'Asia/Jayapura' => 'WIT (Asia/Jayapura)'] as $tz => $label): ?>
                    <option value="<?= $tz ?>" <?= ($settings['system.timezone'] ?? 'Asia/Jakarta') === $tz ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Mata Uang</label>
            <input type="text" name="currency" class="form-control" value="<?= e($settings['system.currency'] ?? 'IDR') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Format Tanggal</label>
            <select name="date_format" class="form-select">
                <?php foreach (['d/m/Y' => 'DD/MM/YYYY (31/12/2026)', 'd-m-Y' => 'DD-MM-YYYY (31-12-2026)', 'Y-m-d' => 'YYYY-MM-DD (2026-12-31)'] as $fmt => $label): ?>
                    <option value="<?= $fmt ?>" <?= ($settings['system.date_format'] ?? 'd/m/Y') === $fmt ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>
