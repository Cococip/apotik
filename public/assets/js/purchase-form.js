(function () {
  'use strict';

  var container = document.getElementById('purchaseLineItems');
  if (!container) return;
  var tbody = container.querySelector('tbody');
  var addBtn = document.querySelector('[data-add-line="purchaseLineItems"]');

  function currentRows() { return tbody.querySelectorAll('tr'); }

  var prefill = window.__PO_PREFILL || [];
  if (prefill.length) {
    tbody.innerHTML = '';
    prefill.forEach(function () { addBtn.click(); });
    var rows = currentRows();
    prefill.forEach(function (item, idx) {
      var row = rows[idx];
      if (!row) return;
      row.querySelector('[name="medicine_id[]"]').value = item.medicine_id;
      row.querySelector('[name="qty[]"]').value = item.qty;
      row.querySelector('.purchase-price-input').value = item.unit_price;
    });
  }

  tbody.addEventListener('change', function (e) {
    if (!e.target.classList.contains('purchase-medicine-select')) return;
    var row = e.target.closest('tr');
    var opt = e.target.options[e.target.selectedIndex];
    var priceInput = row.querySelector('.purchase-price-input');
    var sellInput = row.querySelector('.sell-price-input');
    if (priceInput && !priceInput.value) priceInput.value = opt.getAttribute('data-price') || '';
    if (sellInput && !sellInput.value) sellInput.value = opt.getAttribute('data-sell') || '';
  });
})();
