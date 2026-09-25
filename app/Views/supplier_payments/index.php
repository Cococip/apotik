<div class="page-header">
    <div>
        <p class="page-title">Hutang Supplier</p>
        <p class="page-subtitle">Daftar pembelian dengan sisa pembayaran ke supplier.</p>
    </div>
</div>

<div class="card-surface card-pad" style="margin-bottom:16px;">
    <?php if (empty($outstanding)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-credit-card"></use></svg></div>
            <h3>Tidak ada hutang tersisa</h3>
            <p>Semua pembelian sudah lunas.</p>
        </div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>No. Pembelian</th><th>Supplier</th><th>Total</th><th>Dibayar</th><th>Sisa</th><th>Jatuh Tempo</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($outstanding as $row): $remaining = $row['total'] - $row['paid_total']; ?>
            <tr>
                <td style="font-weight:600;"><?= e($row['purchase_number']) ?></td>
                <td><?= e($row['supplier_name']) ?></td>
                <td><?= e(format_money($row['total'])) ?></td>
                <td><?= e(format_money($row['paid_total'])) ?></td>
                <td style="color:var(--color-danger);font-weight:700;"><?= e(format_money($remaining)) ?></td>
                <td class="table-cell-muted"><?= $row['due_date'] ? e(format_date($row['due_date'])) : '-' ?></td>
                <td class="table-actions">
                    <button type="button" class="btn btn-sm btn-primary" data-open-pay
                        data-purchase-id="<?= $row['id'] ?>" data-purchase-number="<?= e($row['purchase_number']) ?>" data-remaining="<?= $remaining ?>">Bayar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <p class="section-card-title">Riwayat Pembayaran Terakhir</p>
    <?php if (empty($recentPayments)): ?>
        <div class="empty-state" style="padding:20px 8px;"><p>Belum ada pembayaran.</p></div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>No. Bayar</th><th>Supplier</th><th>No. Pembelian</th><th>Tanggal</th><th>Jumlah</th><th>Metode</th><th>Petugas</th></tr></thead>
        <tbody>
        <?php foreach ($recentPayments as $p): ?>
            <tr>
                <td style="font-weight:600;"><?= e($p['payment_number']) ?></td>
                <td><?= e($p['supplier_name']) ?></td>
                <td class="table-cell-muted"><?= e($p['purchase_number'] ?: '-') ?></td>
                <td class="table-cell-muted"><?= e(format_date($p['payment_date'])) ?></td>
                <td><?= e(format_money($p['amount'])) ?></td>
                <td><span class="badge badge-info"><?= e($p['payment_method']) ?></span></td>
                <td class="table-cell-muted"><?= e($p['user_name'] ?: '-') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/supplier-payments" data-loading-text="Menyimpan...">
                <?= csrf_field() ?>
                <input type="hidden" name="purchase_id" id="payPurchaseId">
                <div class="modal-header"><h5 class="modal-title">Bayar Hutang — <span id="payPurchaseNumber"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="table-cell-muted">Sisa hutang: <strong id="payRemainingText"></strong></p>
                    <div class="form-group">
                        <label class="form-label">Jumlah Dibayar<span class="required">*</span></label>
                        <input type="number" step="1" name="amount" id="payAmount" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Metode</label>
                        <select name="payment_method" class="form-select">
                            <option value="Cash">Cash</option>
                            <option value="Transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="note" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var modalEl = document.getElementById('payModal');
    var modal = new bootstrap.Modal(modalEl);
    document.querySelectorAll('[data-open-pay]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('payPurchaseId').value = btn.getAttribute('data-purchase-id');
            document.getElementById('payPurchaseNumber').textContent = btn.getAttribute('data-purchase-number');
            var remaining = parseFloat(btn.getAttribute('data-remaining'));
            document.getElementById('payRemainingText').textContent = 'Rp ' + remaining.toLocaleString('id-ID');
            document.getElementById('payAmount').max = remaining;
            document.getElementById('payAmount').value = remaining;
            modal.show();
        });
    });
    <?php if ($preselectPurchaseId): ?>
    var preBtn = document.querySelector('[data-purchase-id="<?= (int) $preselectPurchaseId ?>"]');
    if (preBtn) preBtn.click();
    <?php endif; ?>
})();
</script>
