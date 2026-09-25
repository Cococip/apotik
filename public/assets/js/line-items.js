(function () {
  'use strict';

  document.querySelectorAll('[data-line-items]').forEach(function (container) {
    var tbody = container.querySelector('tbody');
    var template = container.querySelector('template');
    var addBtn = document.querySelector('[data-add-line="' + container.id + '"]');

    function addRow() {
      var clone = template.content.cloneNode(true);
      tbody.appendChild(clone);
    }

    if (addBtn) addBtn.addEventListener('click', addRow);

    tbody.addEventListener('click', function (e) {
      var removeBtn = e.target.closest('[data-remove-line]');
      if (!removeBtn) return;
      var row = removeBtn.closest('tr');
      if (tbody.querySelectorAll('tr').length > 1) {
        row.remove();
      } else {
        row.querySelectorAll('input, select').forEach(function (f) { f.value = ''; });
      }
    });

    if (!tbody.querySelector('tr')) addRow();
  });
})();
