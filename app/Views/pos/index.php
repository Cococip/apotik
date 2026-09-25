<div class="pos-grid">
    <section class="pos-panel-left">
        <div class="pos-search-row">
            <input type="text" id="posBarcodeInput" class="form-control pos-barcode-input" placeholder="Scan barcode di sini..." autocomplete="off">
        </div>
        <div class="pos-search-row">
            <div class="table-search" style="flex:1;">
                <svg class="icon"><use href="/assets/icons/sprite.svg#ic-search"></use></svg>
                <input type="text" id="posSearchInput" placeholder="Cari nama obat, kode, atau generik...">
            </div>
        </div>
        <div class="pos-category-chips" id="posCategoryChips">
            <button type="button" class="pos-chip is-active" data-category="">Semua</button>
            <?php foreach ($categories as $cat): ?>
                <button type="button" class="pos-chip" data-category="<?= $cat['id'] ?>"><?= e($cat['name']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="pos-product-grid" id="posProductGrid"></div>
    </section>

    <section class="pos-panel-right">
        <div style="margin-bottom:10px;">
            <button type="button" class="btn btn-sm btn-ghost" id="posToggleExtra" style="padding-left:0;">+ Info Pasien / Dokter / Resep (opsional)</button>
            <div id="posExtraFields" style="display:none;padding:10px 0;border-bottom:1px solid var(--color-border);margin-bottom:10px;">
                <div class="form-group" style="margin-bottom:8px;">
                    <label class="form-label" style="font-size:11.5px;">Pasien (wajib jika pembayaran kurang / piutang)</label>
                    <select id="posCustomerSelect" class="form-select">
                        <option value="">- Tanpa data pasien -</option>
                        <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['patient_number']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:11.5px;">Dokter</label>
                    <select id="posDoctorSelect" class="form-select">
                        <option value="">- Tanpa dokter -</option>
                        <?php foreach ($doctors as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="pos-cart-list" id="posCartList">
            <div class="pos-empty-cart">
                <p>Keranjang masih kosong.<br>Scan barcode atau pilih obat di sebelah kiri.</p>
            </div>
        </div>

        <div class="pos-summary">
            <div class="pos-summary-row"><span>Subtotal</span><span id="posSubtotal">Rp 0</span></div>
            <div class="pos-summary-row">
                <span>Diskon</span>
                <input type="number" id="posDiscount" class="form-control" style="width:120px;text-align:right;" value="0" min="0">
            </div>
            <div class="pos-summary-row total"><span>Total</span><span id="posGrandTotal">Rp 0</span></div>

            <div class="form-row" style="margin-top:10px;">
                <div class="form-group">
                    <label class="form-label" style="font-size:11.5px;">Metode Bayar</label>
                    <select id="posPaymentMethod" class="form-select">
                        <?php foreach ($paymentMethods as $m): ?><option value="<?= e($m) ?>"><?= e($m) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-size:11.5px;">Jumlah Bayar</label>
                    <input type="number" id="posPaidAmount" class="form-control" value="0" min="0">
                </div>
            </div>
            <div class="pos-summary-row"><span>Kembalian</span><strong id="posChange">Rp 0</strong></div>

            <button type="button" class="btn btn-primary" id="posCheckoutBtn" style="width:100%;justify-content:center;margin-top:8px;" disabled>Bayar &amp; Cetak Struk</button>
        </div>
    </section>
</div>

<div class="modal fade" id="posBarcodeNotFoundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Barcode Tidak Ditemukan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p>Barcode <strong id="posMissingBarcodeText"></strong> tidak terdaftar pada data obat.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Batalkan</button>
                <button type="button" class="btn btn-soft" id="posGoSearch" data-bs-dismiss="modal">Cari Obat</button>
                <a href="/medicines/create" target="_blank" class="btn btn-primary">Buat Obat Baru</a>
            </div>
        </div>
    </div>
</div>

<script>
window.POS_CONFIG = {
    csrfToken: <?= json_encode(csrf_token_value()) ?>,
};
</script>
<script src="/assets/js/offline-db.js" defer></script>
<script src="/assets/js/pos.js" defer></script>
