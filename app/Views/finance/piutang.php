<div class="page-header">
    <div>
        <p class="page-title">Piutang</p>
        <p class="page-subtitle">Transaksi penjualan dengan pembayaran belum lunas.</p>
    </div>
    <span class="badge badge-danger" style="font-size:13px;padding:8px 14px;">Total Piutang: <?= e(format_money($totalOutstanding)) ?></span>
</div>

<div class="card-surface card-pad">
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-file-text"></use></svg></div>
            <h3>Tidak ada piutang</h3>
            <p>Semua transaksi penjualan sudah lunas.</p>
        </div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Invoice</th><th>Tanggal</th><th>Pasien</th><th>Total</th><th>Dibayar</th><th>Sisa</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): $remaining = $row['grand_total'] - $row['paid_amount']; ?>
            <tr>
                <td><a href="/sales/<?= $row['id'] ?>" style="font-weight:600;color:var(--color-ink);"><?= e($row['invoice_number']) ?></a></td>
                <td class="table-cell-muted"><?= e(format_datetime($row['transaction_date'])) ?></td>
                <td><?= e($row['customer_name'] ?: '-') ?><?= $row['customer_phone'] ? ' · ' . e($row['customer_phone']) : '' ?></td>
                <td><?= e(format_money($row['grand_total'])) ?></td>
                <td><?= e(format_money($row['paid_amount'])) ?></td>
                <td style="color:var(--color-danger);font-weight:700;"><?= e(format_money($remaining)) ?></td>
                <td class="table-actions">
                    <button type="button" class="btn btn-sm btn-primary" data-open-pay
                        data-sale-id="<?= $row['id'] ?>" data-invoice="<?= e($row['invoice_number']) ?>" data-remaining="<?= $remaining ?>">Bayar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="payPiutangModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/finance/piutang/pay" data-loading-text="Menyimpan...">
                <?= csrf_field() ?>
                <input type="hidden" name="sale_id" id="ppSaleId">
                <div class="modal-header"><h5 class="modal-title">Bayar Piutang — <span id="ppInvoice"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="table-cell-muted">Sisa piutang: <strong id="ppRemainingText"></strong></p>
                    <div class="form-group">
                        <label class="form-label">Jumlah Dibayar<span class="required">*</span></label>
                        <input type="number" step="1" name="amount" id="ppAmount" class="form-control" required>
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
    var modal = new bootstrap.Modal(document.getElementById('payPiutangModal'));
    document.querySelectorAll('[data-open-pay]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('ppSaleId').value = btn.getAttribute('data-sale-id');
            document.getElementById('ppInvoice').textContent = btn.getAttribute('data-invoice');
            var remaining = parseFloat(btn.getAttribute('data-remaining'));
            document.getElementById('ppRemainingText').textContent = 'Rp ' + remaining.toLocaleString('id-ID');
            document.getElementById('ppAmount').max = remaining;
            document.getElementById('ppAmount').value = remaining;
            modal.show();
        });
    });
})();
</script>
