(function () {
  'use strict';

  document.querySelectorAll('[data-crud-modal]').forEach(function (modalEl) {
    var form = modalEl.querySelector('form');
    if (!form) return;
    var routeBase = modalEl.getAttribute('data-route-base');
    var titleEl = modalEl.querySelector('[data-modal-title]');
    var methodField = form.querySelector('input[name="_method"]');

    function showModal() {
      if (window.bootstrap && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
      } else {
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
      }
    }

    function openCreate() {
      form.reset();
      form.action = routeBase;
      if (methodField) methodField.value = '';
      if (titleEl) titleEl.textContent = modalEl.getAttribute('data-create-title') || 'Tambah Data';
      showModal();
    }

    function openEdit(record) {
      form.reset();
      Object.keys(record).forEach(function (key) {
        var field = form.querySelector('[name="' + key + '"]');
        if (field) field.value = record[key] === null ? '' : record[key];
      });
      form.action = routeBase + '/' + record.id;
      if (methodField) methodField.value = 'PUT';
      if (titleEl) titleEl.textContent = modalEl.getAttribute('data-edit-title') || 'Edit Data';
      showModal();
    }

    function prefillOld() {
      form.querySelectorAll('[data-old]').forEach(function (field) {
        var val = field.getAttribute('data-old');
        if (val !== null && val !== '') field.value = val;
      });
    }

    var createBtn = document.querySelector('[data-open-create="' + modalEl.id + '"]');
    if (createBtn) createBtn.addEventListener('click', openCreate);

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-open-edit="' + modalEl.id + '"]');
      if (!btn) return;
      openEdit(JSON.parse(btn.getAttribute('data-record')));
    });

    var auto = modalEl.getAttribute('data-auto-open');
    if (auto === 'create') {
      openCreate();
      prefillOld();
    } else if (auto && auto.indexOf('edit:') === 0) {
      openEdit({ id: auto.split(':')[1] });
      prefillOld();
    }
  });
})();
