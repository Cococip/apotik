<div class="page-header">
    <div>
        <p class="page-title">Rekap Keuangan</p>
        <p class="page-subtitle">Ringkasan arus kas dan estimasi laba pada periode tertentu.</p>
    </div>
</div>

<div class="card-surface card-pad" style="margin-bottom:16px;">
    <form method="get" style="display:flex;gap:8px;align-items:end;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="form-control">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Terapkan</button>
    </form>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-label">Omzet Penjualan</span>
        <span class="stat-value"><?= e(format_money($data['revenue'])) ?></span>
        <span class="table-cell-muted"><?= $data['transaction_count'] ?> transaksi</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Estimasi Laba Kotor</span>
        <span class="stat-value"><?= e(format_money($data['gross_profit'])) ?></span>
        <span class="table-cell-muted">Modal: <?= e(format_money($data['cogs'])) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Total Kas Masuk</span>
        <span class="stat-value"><?= e(format_money($data['total_cash_in'])) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Total Kas Keluar</span>
        <span class="stat-value"><?= e(format_money($data['total_cash_out'])) ?></span>
    </div>
</div>

<div class="card-surface card-pad">
    <p class="section-card-title">Rincian Arus Kas</p>
    <table class="data-table">
        <tbody>
            <tr><td>Kas Masuk — Penjualan (tunai diterima)</td><td style="text-align:right;font-weight:700;"><?= e(format_money($data['sales_cash_in'])) ?></td></tr>
            <tr><td>Kas Masuk — Manual</td><td style="text-align:right;font-weight:700;"><?= e(format_money($data['manual_cash_in'])) ?></td></tr>
            <tr><td>Kas Keluar — Manual</td><td style="text-align:right;font-weight:700;color:var(--color-danger);">-<?= e(format_money($data['manual_cash_out'])) ?></td></tr>
            <tr><td>Kas Keluar — Pengeluaran Operasional</td><td style="text-align:right;font-weight:700;color:var(--color-danger);">-<?= e(format_money($data['expenses'])) ?></td></tr>
            <tr><td>Kas Keluar — Pembayaran Hutang Supplier</td><td style="text-align:right;font-weight:700;color:var(--color-danger);">-<?= e(format_money($data['purchase_payments'])) ?></td></tr>
            <tr><td style="font-weight:700;font-size:15px;">Arus Kas Bersih</td><td style="text-align:right;font-weight:800;font-size:15px;"><?= e(format_money($data['net_cash_flow'])) ?></td></tr>
        </tbody>
    </table>
</div>
