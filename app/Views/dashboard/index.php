<?php
/** @var array $stats */
/** @var array $lowStock */
/** @var array $expiringSoon */
/** @var array $recentTransactions */
use App\Services\ExpiryStatusService;

$greetingHour = (int) date('G');
$greeting = $greetingHour < 11 ? 'Selamat pagi' : ($greetingHour < 15 ? 'Selamat siang' : ($greetingHour < 19 ? 'Selamat sore' : 'Selamat malam'));
?>
<div class="page-header">
    <div>
        <p class="page-title">Selamat datang kembali, <?= e(explode(' ', $currentUser['full_name'] ?? 'Pengguna')[0]) ?></p>
        <p class="page-subtitle"><?= e($greeting) ?> — <?= e(format_date_id(date('Y-m-d'))) ?></p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Penjualan Hari Ini</span>
            <span class="stat-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-dollar"></use></svg></span>
        </div>
        <span class="stat-value"><?= e(format_money($stats['sales_today'])) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Transaksi Hari Ini</span>
            <span class="stat-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-receipt"></use></svg></span>
        </div>
        <span class="stat-value"><?= (int) $stats['transactions_today'] ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Produk Terjual</span>
            <span class="stat-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-pill"></use></svg></span>
        </div>
        <span class="stat-value"><?= (int) $stats['items_sold_today'] ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Stok Menipis</span>
            <span class="stat-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-box"></use></svg></span>
        </div>
        <span class="stat-value"><?= (int) $stats['low_stock_count'] ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Akan Expired</span>
            <span class="stat-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-clock"></use></svg></span>
        </div>
        <span class="stat-value"><?= (int) $stats['expiring_soon_count'] ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Total Hutang</span>
            <span class="stat-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-credit-card"></use></svg></span>
        </div>
        <span class="stat-value"><?= e(format_money($stats['total_debt'])) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Total Piutang</span>
            <span class="stat-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-file-text"></use></svg></span>
        </div>
        <span class="stat-value"><?= e(format_money($stats['total_receivable'])) ?></span>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Estimasi Laba Hari Ini</span>
            <span class="stat-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-dollar"></use></svg></span>
        </div>
        <span class="stat-value"><?= e(format_money($stats['estimated_profit_today'])) ?></span>
    </div>
</div>

<div class="chart-grid">
    <div class="card-surface card-pad">
        <p class="section-card-title">Penjualan 7 &amp; 30 Hari Terakhir</p>
        <p class="section-card-subtitle">Total penjualan harian (Rp)</p>
        <div style="display:flex;gap:8px;margin-bottom:12px;">
            <button type="button" class="btn btn-sm btn-soft" data-range-toggle="7" onclick="ApotekCareDashboard.showRange(7)">7 Hari</button>
            <button type="button" class="btn btn-sm btn-ghost" data-range-toggle="30" onclick="ApotekCareDashboard.showRange(30)">30 Hari</button>
        </div>
        <canvas id="chartSales" height="110"></canvas>
    </div>
    <div class="card-surface card-pad">
        <p class="section-card-title">Kategori Terlaris</p>
        <p class="section-card-subtitle">30 hari terakhir, berdasarkan omzet</p>
        <canvas id="chartTopCategories" height="200"></canvas>
    </div>
</div>

<div class="chart-grid" style="grid-template-columns:1fr;">
    <div class="card-surface card-pad">
        <p class="section-card-title">Produk Terlaris</p>
        <p class="section-card-subtitle">30 hari terakhir, berdasarkan jumlah terjual</p>
        <canvas id="chartTopProducts" height="90"></canvas>
    </div>
</div>

<div class="widget-grid">
    <div class="card-surface card-pad">
        <p class="section-card-title">Stok Menipis</p>
        <p class="section-card-subtitle">Obat dengan stok di bawah minimum</p>
        <?php if (empty($lowStock)): ?>
            <div class="empty-state" style="padding:24px 8px;"><p>Tidak ada obat dengan stok menipis saat ini.</p></div>
        <?php else: ?>
            <?php foreach ($lowStock as $item): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--color-border);">
                    <div>
                        <div style="font-weight:600;font-size:13px;"><?= e($item['name']) ?></div>
                        <div class="table-cell-muted">Stok: <?= (int) $item['stock'] ?> / Min: <?= (int) $item['minimum_stock'] ?></div>
                    </div>
                    <span class="badge <?= $item['stock'] <= 0 ? 'badge-danger' : 'badge-warning' ?>"><?= $item['stock'] <= 0 ? 'Habis' : 'Menipis' ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card-surface card-pad">
        <p class="section-card-title">Akan Expired</p>
        <p class="section-card-subtitle">Batch mendekati tanggal kedaluwarsa</p>
        <?php if (empty($expiringSoon)): ?>
            <div class="empty-state" style="padding:24px 8px;"><p>Tidak ada batch yang mendekati expired.</p></div>
        <?php else: ?>
            <?php foreach ($expiringSoon as $batch): $status = ExpiryStatusService::statusFor($batch['expired_date']); ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--color-border);">
                    <div>
                        <div style="font-weight:600;font-size:13px;"><?= e($batch['medicine_name']) ?></div>
                        <div class="table-cell-muted">Batch <?= e($batch['batch_number']) ?> · Exp <?= e(format_date($batch['expired_date'])) ?> · Qty <?= (int) $batch['available_qty'] ?></div>
                    </div>
                    <span class="badge <?= e($status['badge']) ?>"><?= $status['days'] ?> hari</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card-surface card-pad">
        <p class="section-card-title">Transaksi Terbaru</p>
        <p class="section-card-subtitle">8 transaksi penjualan terakhir</p>
        <?php if (empty($recentTransactions)): ?>
            <div class="empty-state" style="padding:24px 8px;"><p>Belum ada transaksi.</p></div>
        <?php else: ?>
            <?php foreach ($recentTransactions as $trx): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--color-border);">
                    <div>
                        <div style="font-weight:600;font-size:13px;"><?= e($trx['invoice_number']) ?></div>
                        <div class="table-cell-muted"><?= e(format_datetime($trx['transaction_date'])) ?> · <?= e($trx['cashier_name']) ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:700;font-size:13px;"><?= e(format_money($trx['grand_total'])) ?></div>
                        <span class="badge badge-success">Selesai</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="/assets/vendor/chartjs/chart.umd.min.js"></script>
<script src="/assets/js/dashboard.js" defer></script>
