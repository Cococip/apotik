<div class="auth-shell">
    <div class="auth-brand-pane">
        <div class="auth-logo">
            <div class="sidebar-brand-mark" style="background:rgba(255,255,255,0.18);">AC</div>
            <?= e(setting('pharmacy.name', 'ApotekCare')) ?>
        </div>
        <div class="auth-brand-copy">
            <h1>Kelola apotek Anda dengan lebih rapi dan cepat.</h1>
            <p>Stok, batch, expired, barcode, hingga laporan — semua tercatat dalam satu sistem yang bisa diandalkan setiap hari, online maupun saat koneksi terputus.</p>
        </div>
        <div class="auth-brand-foot">&copy; <?= date('Y') ?> <?= e(setting('pharmacy.name', 'ApotekCare')) ?>. Seluruh hak cipta dilindungi.</div>
    </div>
    <div class="auth-form-pane">
        <div class="auth-form-card">
            <h2 style="margin-bottom:4px;">Masuk ke akun Anda</h2>
            <p style="color:var(--color-ink-muted);font-size:13px;margin-bottom:24px;">Masukkan kredensial Anda untuk melanjutkan.</p>

            <?php if ($error = flash('error')): ?>
                <div class="badge badge-danger" style="display:block;padding:10px 14px;margin-bottom:16px;white-space:normal;"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/login" data-loading-text="Memproses...">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="username">Username atau Email<span class="required">*</span></label>
                    <input type="text" id="username" name="username" class="form-control" value="<?= e(old('username')) ?>" autofocus required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password<span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Masuk</button>
            </form>

            <details style="margin-top:22px;font-size:12.5px;color:var(--color-ink-muted);">
                <summary style="cursor:pointer;font-weight:700;">Akun demo (khusus development)</summary>
                <p style="margin:10px 0 4px;">Password untuk semua akun: <code>Apotek#2026</code></p>
                <ul style="margin:0;padding-left:18px;">
                    <li>superadmin — akses penuh</li>
                    <li>owner — pemilik apotek</li>
                    <li>apoteker — obat, stok, barcode</li>
                    <li>kasir — dashboard (POS aktif di Fase 2)</li>
                    <li>gudang — persediaan & barcode</li>
                </ul>
            </details>
        </div>
    </div>
</div>
