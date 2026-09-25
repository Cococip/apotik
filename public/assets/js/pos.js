(function () {
  'use strict';

  function money(v) { return 'Rp ' + Math.round(v).toLocaleString('id-ID'); }

  function getDeviceId() {
    var key = 'apotekcare_device_id';
    var id = localStorage.getItem(key);
    if (!id) {
      id = 'dev-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
      localStorage.setItem(key, id);
    }
    return id;
  }

  function uuidv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = (Math.random() * 16) | 0, v = c === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  var els = {
    barcodeInput: document.getElementById('posBarcodeInput'),
    searchInput: document.getElementById('posSearchInput'),
    chips: document.getElementById('posCategoryChips'),
    grid: document.getElementById('posProductGrid'),
    cartList: document.getElementById('posCartList'),
    subtotal: document.getElementById('posSubtotal'),
    grandTotal: document.getElementById('posGrandTotal'),
    discount: document.getElementById('posDiscount'),
    paymentMethod: document.getElementById('posPaymentMethod'),
    paidAmount: document.getElementById('posPaidAmount'),
    change: document.getElementById('posChange'),
    checkoutBtn: document.getElementById('posCheckoutBtn'),
    toggleExtra: document.getElementById('posToggleExtra'),
    extraFields: document.getElementById('posExtraFields'),
    customerSelect: document.getElementById('posCustomerSelect'),
    doctorSelect: document.getElementById('posDoctorSelect'),
    missingBarcodeText: document.getElementById('posMissingBarcodeText'),
    goSearch: document.getElementById('posGoSearch'),
    syncBadge: document.getElementById('syncQueueBadge'),
  };

  if (!els.grid) return; // not on POS page

  var cart = [];
  var activeCategory = '';
  var searchDebounce = null;
  var missingBarcodeModal = new bootstrap.Modal(document.getElementById('posBarcodeNotFoundModal'));
  var offlineDB = window.ApotekOfflineDB;

  function focusBarcode() {
    els.barcodeInput.focus();
  }

  /* ---------------- Offline catalog cache ---------------- */
  function refreshCatalogCache() {
    if (!offlineDB) return;
    fetch('/api/pos/catalog')
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) offlineDB.cacheMedicines(res.data);
      })
      .catch(function () { /* offline — keep using whatever was cached before */ });
  }

  /* ---------------- Sync queue badge + flush ---------------- */
  function refreshSyncBadge() {
    if (!offlineDB || !els.syncBadge) return;
    offlineDB.countPending().then(function (count) {
      if (count > 0) {
        els.syncBadge.style.display = 'inline-flex';
        els.syncBadge.textContent = count + ' transaksi menunggu sinkronisasi';
      } else {
        els.syncBadge.style.display = 'none';
        els.syncBadge.textContent = '';
      }
    });
  }

  function flushSyncQueue() {
    if (!offlineDB || !navigator.onLine) return;
    offlineDB.getPendingSales().then(function (pending) {
      if (!pending.length) return;

      fetch('/api/sync/push', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          _csrf_token: window.POS_CONFIG.csrfToken,
          device_id: getDeviceId(),
          device_name: navigator.userAgent.slice(0, 60),
          items: pending.map(function (p) { return { uuid: p.uuid, payload: p.payload }; }),
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res.success) return;
          var results = res.data.results || [];
          var syncedCount = 0, conflictCount = 0;
          results.forEach(function (r) {
            if (r.status === 'synced') {
              offlineDB.markSynced(r.uuid);
              syncedCount++;
            } else if (r.status === 'conflict') {
              offlineDB.markSynced(r.uuid); // remove from local queue; server has recorded the conflict for admin review
              conflictCount++;
            }
            // 'failed' stays queued locally for automatic retry later
          });
          if (syncedCount > 0) window.ApotekCare.toast(syncedCount + ' transaksi berhasil disinkronkan.', 'success');
          if (conflictCount > 0) window.ApotekCare.toast(conflictCount + ' transaksi berkonflik stok — cek menu Sinkronisasi.', 'warning');
          refreshSyncBadge();
        })
        .catch(function () { /* still offline or server unreachable, keep queue for next attempt */ });
    });
  }

  window.addEventListener('online', flushSyncQueue);
  if (els.syncBadge) els.syncBadge.addEventListener('click', flushSyncQueue);
  setInterval(flushSyncQueue, 30000);

  /* ---------------- Product search (online-first, offline fallback) ---------------- */
  function renderProducts(items) {
    if (!items.length) {
      els.grid.innerHTML = '<div class="pos-empty-cart" style="grid-column:1/-1;"><p>Tidak ada obat ditemukan.</p></div>';
      return;
    }
    els.grid.innerHTML = items.map(function (p) {
      var disabled = p.stock <= 0;
      var price = p.selling_price;
      return '<button type="button" class="pos-product-card' + (disabled ? ' is-disabled' : '') + '" data-product=\'' + JSON.stringify(p).replace(/'/g, '&#39;') + '\'' + (disabled ? ' disabled' : '') + '>' +
        '<div class="pos-product-name">' + p.name + (parseInt(p.requires_prescription) ? '<span class="pos-rx-badge">RESEP</span>' : '') + '</div>' +
        '<div class="pos-product-price">' + money(price) + '</div>' +
        '<div class="pos-product-stock">Stok: ' + Math.trunc(p.stock) + ' ' + (p.unit_symbol || '') + '</div>' +
        '</button>';
    }).join('');
  }

  function loadProducts() {
    var q = els.searchInput.value.trim();
    var params = new URLSearchParams();
    if (q) params.set('q', q);
    if (activeCategory) params.set('category_id', activeCategory);

    fetch('/api/pos/search?' + params.toString())
      .then(function (r) { return r.json(); })
      .then(function (res) { if (res.success) renderProducts(res.data); })
      .catch(function () {
        if (!offlineDB) {
          els.grid.innerHTML = '<div class="pos-empty-cart" style="grid-column:1/-1;"><p>Gagal memuat data obat. Periksa koneksi Anda.</p></div>';
          return;
        }
        offlineDB.searchMedicines(q).then(renderProducts);
      });
  }

  function addToCart(product, qty) {
    qty = qty || 1;
    var existing = cart.find(function (i) { return i.medicine_id === product.id; });
    var price = parseFloat(product.selling_price);
    if (existing) {
      if (existing.qty + qty > product.stock) {
        window.ApotekCare.toast('Qty melebihi stok tersedia (' + Math.trunc(product.stock) + ').', 'error');
        return;
      }
      existing.qty += qty;
    } else {
      if (qty > product.stock) {
        window.ApotekCare.toast('Stok tidak mencukupi.', 'error');
        return;
      }
      cart.push({
        medicine_id: product.id, name: product.name, price: price, qty: qty,
        stock: product.stock, requires_prescription: !!parseInt(product.requires_prescription),
        selling_price: product.selling_price, selling_price_prescription: product.selling_price_prescription,
      });
    }
    renderCart();
  }

  function renderCart() {
    if (!cart.length) {
      els.cartList.innerHTML = '<div class="pos-empty-cart"><p>Keranjang masih kosong.<br>Scan barcode atau pilih obat di sebelah kiri.</p></div>';
    } else {
      els.cartList.innerHTML = cart.map(function (item, idx) {
        return '<div class="pos-cart-item" data-idx="' + idx + '">' +
          '<div class="pos-cart-item-info">' +
            '<div class="pos-cart-item-name">' + item.name + (item.requires_prescription ? '<span class="pos-rx-badge">RESEP</span>' : '') + '</div>' +
            '<div class="pos-cart-item-price">' + money(item.price) + ' / item</div>' +
          '</div>' +
          '<div class="pos-qty-control">' +
            '<button type="button" data-action="dec">-</button>' +
            '<input type="number" value="' + item.qty + '" data-action="set">' +
            '<button type="button" data-action="inc">+</button>' +
          '</div>' +
          '<div class="pos-cart-item-subtotal">' + money(item.price * item.qty) + '</div>' +
          '<button type="button" class="pos-cart-item-remove" data-action="remove" aria-label="Hapus">&times;</button>' +
        '</div>';
      }).join('');
    }
    updateSummary();
  }

  els.cartList.addEventListener('click', function (e) {
    var itemEl = e.target.closest('.pos-cart-item');
    if (!itemEl) return;
    var idx = parseInt(itemEl.getAttribute('data-idx'));
    var action = e.target.getAttribute('data-action');
    if (action === 'inc') {
      if (cart[idx].qty + 1 > cart[idx].stock) { window.ApotekCare.toast('Stok tidak mencukupi.', 'error'); return; }
      cart[idx].qty++;
    } else if (action === 'dec') {
      cart[idx].qty--;
      if (cart[idx].qty <= 0) cart.splice(idx, 1);
    } else if (action === 'remove') {
      cart.splice(idx, 1);
    } else {
      return;
    }
    renderCart();
  });

  els.cartList.addEventListener('change', function (e) {
    if (e.target.getAttribute('data-action') !== 'set') return;
    var itemEl = e.target.closest('.pos-cart-item');
    var idx = parseInt(itemEl.getAttribute('data-idx'));
    var val = parseFloat(e.target.value) || 0;
    if (val <= 0) { cart.splice(idx, 1); renderCart(); return; }
    if (val > cart[idx].stock) {
      window.ApotekCare.toast('Qty melebihi stok tersedia.', 'error');
      val = cart[idx].stock;
    }
    cart[idx].qty = val;
    renderCart();
  });

  function subtotal() {
    return cart.reduce(function (sum, i) { return sum + i.price * i.qty; }, 0);
  }

  function updateSummary() {
    var sub = subtotal();
    var discount = parseFloat(els.discount.value) || 0;
    var grand = Math.max(0, sub - discount);
    els.subtotal.textContent = money(sub);
    els.grandTotal.textContent = money(grand);

    var paid = parseFloat(els.paidAmount.value) || 0;
    var change = Math.max(0, paid - grand);
    els.change.textContent = money(change);

    els.checkoutBtn.disabled = cart.length === 0;
  }

  els.discount.addEventListener('input', updateSummary);
  els.paidAmount.addEventListener('input', updateSummary);

  els.toggleExtra.addEventListener('click', function () {
    var visible = els.extraFields.style.display !== 'none';
    els.extraFields.style.display = visible ? 'none' : 'block';
  });

  els.searchInput.addEventListener('input', function () {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(loadProducts, 300);
  });

  els.chips.addEventListener('click', function (e) {
    var chip = e.target.closest('.pos-chip');
    if (!chip) return;
    els.chips.querySelectorAll('.pos-chip').forEach(function (c) { c.classList.remove('is-active'); });
    chip.classList.add('is-active');
    activeCategory = chip.getAttribute('data-category');
    loadProducts();
  });

  els.grid.addEventListener('click', function (e) {
    var card = e.target.closest('.pos-product-card');
    if (!card || card.disabled) return;
    var product = JSON.parse(card.getAttribute('data-product').replace(/&#39;/g, "'"));
    addToCart(product, 1);
    focusBarcode();
  });

  var missingCode = '';
  els.barcodeInput.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    var code = els.barcodeInput.value.trim();
    els.barcodeInput.value = '';
    if (!code) return;

    fetch('/api/pos/barcode/' + encodeURIComponent(code))
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          addToCart(res.data, 1);
        } else {
          missingCode = code;
          els.missingBarcodeText.textContent = code;
          missingBarcodeModal.show();
        }
      })
      .catch(function () {
        if (!offlineDB) {
          window.ApotekCare.toast('Gagal mencari barcode. Periksa koneksi Anda.', 'error');
          return;
        }
        offlineDB.getByBarcode(code).then(function (product) {
          if (product) {
            addToCart(product, 1);
          } else {
            missingCode = code;
            els.missingBarcodeText.textContent = code + ' (mode offline)';
            missingBarcodeModal.show();
          }
        });
      })
      .finally(focusBarcode);
  });

  els.goSearch.addEventListener('click', function () {
    els.searchInput.value = missingCode;
    loadProducts();
    els.searchInput.focus();
  });

  /* ---------------- Offline receipt (no server invoice number yet) ---------------- */
  function openOfflineReceipt(uuid, lines, grand, paid, change, method) {
    var win = window.open('', '_blank');
    if (!win) {
      // Popup blocked — the sale is already safely queued in IndexedDB either
      // way; the receipt is a convenience, not the source of truth, so don't
      // let a blocked popup abort the rest of the post-checkout cleanup.
      window.ApotekCare.toast('Struk sementara tidak dapat dibuka (popup diblokir). Transaksi tetap tersimpan.', 'warning');
      return;
    }
    var itemsHtml = lines.map(function (i) {
      return '<div class="receipt-item"><div>' + i.name + '</div><div class="receipt-row"><span>' + i.qty + ' x ' + money(i.price) + '</span><span>' + money(i.price * i.qty) + '</span></div></div>';
    }).join('');
    win.document.write(
      '<html><head><title>Struk Sementara</title><link rel="stylesheet" href="/assets/css/tokens.css"><link rel="stylesheet" href="/assets/css/print.css"></head>' +
      '<body class="print-body"><div class="label-toolbar no-print"><button class="btn btn-primary" onclick="window.print()">Cetak</button></div>' +
      '<div class="receipt receipt-80mm">' +
      '<div class="receipt-center"><strong>ApotekCare</strong><br><span style="color:#c0392b;font-weight:700;">BELUM TERSINKRONISASI</span></div>' +
      '<div class="receipt-divider"></div>' +
      '<div class="receipt-row"><span>Ref. Sementara</span><span>' + uuid.slice(0, 8) + '</span></div>' +
      '<div class="receipt-row"><span>Tanggal</span><span>' + new Date().toLocaleString('id-ID') + '</span></div>' +
      '<div class="receipt-divider"></div>' + itemsHtml + '<div class="receipt-divider"></div>' +
      '<div class="receipt-row receipt-total"><span>Total</span><span>' + money(grand) + '</span></div>' +
      '<div class="receipt-row"><span>Bayar (' + method + ')</span><span>' + money(paid) + '</span></div>' +
      '<div class="receipt-row"><span>Kembali</span><span>' + money(change) + '</span></div>' +
      '<div class="receipt-divider"></div>' +
      '<div class="receipt-center">Nomor invoice resmi akan diberikan setelah tersinkronisasi ke server.</div>' +
      '</div></body></html>'
    );
    win.document.close();
  }

  els.checkoutBtn.addEventListener('click', function () {
    if (!cart.length) return;

    var grand = Math.max(0, subtotal() - (parseFloat(els.discount.value) || 0));
    var paid = parseFloat(els.paidAmount.value) || 0;
    var customerId = els.customerSelect.value || null;

    if (paid < grand && !customerId) {
      window.ApotekCare.toast('Pilih data pasien terlebih dahulu untuk transaksi dengan pembayaran kurang (piutang).', 'error');
      els.extraFields.style.display = 'block';
      return;
    }

    var uuid = uuidv4();
    var method = els.paymentMethod.value;
    var discountVal = parseFloat(els.discount.value) || 0;
    var cartSnapshot = cart.slice();

    var payload = {
      _csrf_token: window.POS_CONFIG.csrfToken,
      items: cart.map(function (i) { return { medicine_id: i.medicine_id, qty: i.qty }; }),
      payment_method: method,
      paid_amount: paid,
      discount: discountVal,
      customer_id: customerId,
      doctor_id: els.doctorSelect.value || null,
      device_id: getDeviceId(),
      uuid: uuid,
    };

    function resetCart() {
      cart = [];
      els.discount.value = 0;
      els.paidAmount.value = 0;
      els.customerSelect.value = '';
      els.doctorSelect.value = '';
      renderCart();
      focusBarcode();
    }

    els.checkoutBtn.disabled = true;
    els.checkoutBtn.textContent = 'Memproses...';

    fetch('/api/pos/checkout', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (r) { return r.json().then(function (data) { return { status: r.status, data: data }; }); })
      .then(function (res) {
        if (res.data.success) {
          window.ApotekCare.toast('Transaksi ' + res.data.data.invoice_number + ' berhasil.', 'success');
          resetCart();
          window.open('/pos/receipt/' + res.data.data.sale_id, '_blank');
          loadProducts();
        } else {
          window.ApotekCare.toast(res.data.message || 'Transaksi gagal.', 'error');
          els.checkoutBtn.disabled = false;
          els.checkoutBtn.textContent = 'Bayar & Cetak Struk';
        }
      })
      .catch(function () {
        // Network failure (not a business-rule rejection) — queue offline (§11/§12/§28).
        if (!offlineDB) {
          window.ApotekCare.toast('Gagal terhubung ke server. Transaksi belum tersimpan.', 'error');
          els.checkoutBtn.disabled = false;
          els.checkoutBtn.textContent = 'Bayar & Cetak Struk';
          return;
        }

        offlineDB.queueSale({
          uuid: uuid,
          status: 'pending',
          created_at: new Date().toISOString(),
          device_id: payload.device_id,
          payload: payload,
        }).then(function () {
          cartSnapshot.forEach(function (i) { offlineDB.adjustCachedStock(i.medicine_id, -i.qty); });
          window.ApotekCare.toast('Sedang offline — transaksi disimpan di perangkat dan akan disinkronkan otomatis.', 'warning');
          // Queue is already durable at this point — cart/badge/list refresh must
          // happen regardless of what the receipt popup does (§ blocked-popup fix).
          refreshSyncBadge();
          resetCart();
          loadProducts();
          try {
            openOfflineReceipt(uuid, cartSnapshot, grand, paid, Math.max(0, paid - grand), method);
          } catch (e) {
            window.ApotekCare.toast('Struk sementara gagal ditampilkan, tapi transaksi tetap tersimpan.', 'warning');
          }
        });
      })
      .finally(function () {
        els.checkoutBtn.disabled = cart.length === 0;
        els.checkoutBtn.textContent = 'Bayar & Cetak Struk';
      });
  });

  refreshCatalogCache();
  refreshSyncBadge();
  flushSyncQueue();
  loadProducts();
  focusBarcode();
})();
