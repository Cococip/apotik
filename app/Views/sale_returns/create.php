<div class="breadcrumb-trail"><a href="/sale-returns">Retur Penjualan</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Buat Baru</div>
<div class="page-header">
    <div>
        <p class="page-title">Buat Retur Penjualan</p>
        <p class="page-subtitle">Cari transaksi berdasarkan nomor invoice.</p>
    </div>
</div>

<div class="card-surface card-pad" style="max-width:480px;margin-bottom:16px;">
    <form method="get" style="display:flex;gap:8px;">
        <input type="text" name="invoice" class="form-control" value="<?= e($invoice) ?>" placeholder="Contoh: INV-20260925-00001" required>
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>
</div>

<?php if ($sale): ?>
<form method="POST" action="/sale-returns" data-loading-text="Memproses retur...">
    <?= csrf_field() ?>
    <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
    <div class="card-surface card-pad" style="margin-bottom:16px;">
        <p class="section-card-title">Transaksi <?= e($sale['invoice_number']) ?></p>
        <p class="section-card-subtitle"><?= e(format_datetime($sale['transaction_date'])) ?> · Kasir <?= e($sale['cashier_name']) ?> · Total <?= e(format_money($sale['grand_total'])) ?></p>

        <?php if (empty($details)): ?>
            <div class="empty-state" style="padding:20px 8px;"><p>Tidak ada item pada transaksi ini.</p></div>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Obat</th><th>Qty Dibeli</th><th>Sudah Diretur</th><th>Qty Retur</th></tr></thead>
            <tbody>
            <?php foreach ($details as $d): $remaining = $d['qty'] - $d['returned_qty']; ?>
                <tr>
                    <td style="font-weight:600;"><?= e($d['medicine_name']) ?></td>
                    <td><?= (int) $d['qty'] ?></td>
                    <td><?= (int) $d['returned_qty'] ?></td>
                    <td style="width:120px;">
                        <?php if ($remaining > 0): ?>
                        <input type="number" name="qty[<?= $d['id'] ?>]" class="form-control" min="0" max="<?= $remaining ?>" placeholder="0">
                        <?php else: ?>
                        <span class="table-cell-muted">Habis</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="card-surface card-pad" style="max-width:480px;">
        <div class="form-group">
            <label class="form-label">Kondisi Barang<span class="required">*</span></label>
            <select name="condition" class="form-select">
                <option value="baik">Baik (dapat dijual kembali, stok dikembalikan)</option>
                <option value="rusak">Rusak (tidak dikembalikan ke stok)</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Alasan Retur<span class="required">*</span></label>
            <textarea name="reason" class="form-control" rows="2" required></textarea>
        </div>
        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;">Proses Retur</button>
    </div>
</form>
<?php endif; ?>
