<div class="breadcrumb-trail"><a href="/purchase-returns">Retur Pembelian</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> Buat Baru</div>
<div class="page-header">
    <div>
        <p class="page-title">Buat Retur Pembelian</p>
        <p class="page-subtitle">Cari berdasarkan nomor pembelian.</p>
    </div>
</div>

<div class="card-surface card-pad" style="max-width:480px;margin-bottom:16px;">
    <form method="get" style="display:flex;gap:8px;">
        <input type="text" name="purchase" class="form-control" value="<?= e($purchaseNumber) ?>" placeholder="Contoh: PO-BUY-20260925-0001" required>
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>
</div>

<?php if ($purchase): ?>
<form method="POST" action="/purchase-returns" data-loading-text="Memproses retur...">
    <?= csrf_field() ?>
    <input type="hidden" name="purchase_id" value="<?= $purchase['id'] ?>">
    <div class="card-surface card-pad" style="margin-bottom:16px;">
        <p class="section-card-title"><?= e($purchase['purchase_number']) ?> — <?= e($purchase['supplier_name']) ?></p>
        <?php if (empty($details)): ?>
            <div class="empty-state" style="padding:20px 8px;"><p>Tidak ada item pada pembelian ini.</p></div>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Obat</th><th>Batch</th><th>Qty Diterima</th><th>Sudah Diretur</th><th>Qty Retur</th></tr></thead>
            <tbody>
            <?php foreach ($details as $d): $remaining = $d['qty'] - $d['returned_qty']; ?>
                <tr>
                    <td style="font-weight:600;"><?= e($d['medicine_name']) ?></td>
                    <td class="table-cell-muted"><?= e($d['batch_number']) ?></td>
                    <td><?= (int) $d['qty'] ?></td>
                    <td><?= (int) $d['returned_qty'] ?></td>
                    <td style="width:120px;">
                        <?php if ($remaining > 0): ?>
                        <input type="number" name="qty[<?= $d['id'] ?>]" class="form-control" min="0" max="<?= $remaining ?>" placeholder="0">
                        <?php else: ?><span class="table-cell-muted">-</span><?php endif; ?>
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
            <label class="form-label">Alasan Retur<span class="required">*</span></label>
            <textarea name="reason" class="form-control" rows="2" required placeholder="Contoh: barang rusak saat pengiriman"></textarea>
        </div>
        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;">Proses Retur ke Supplier</button>
    </div>
</form>
<?php endif; ?>
