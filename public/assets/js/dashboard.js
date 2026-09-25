(function () {
  'use strict';

  if (typeof Chart === 'undefined') return;

  var PALETTE = ['#2563A6', '#5B9FE3', '#8FC7F5', '#BFDFFF', '#1B4C82'];

  Chart.defaults.font.family = "-apple-system, 'Segoe UI', Inter, Roboto, sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.color = '#5B6B82';

  var salesChart = null;
  var seriesCache = { 7: null, 30: null };

  function money(v) {
    return 'Rp ' + Number(v).toLocaleString('id-ID');
  }

  function renderSalesChart(series) {
    var ctx = document.getElementById('chartSales');
    if (!ctx) return;
    var config = {
      type: 'line',
      data: {
        labels: series.labels,
        datasets: [{
          label: 'Penjualan',
          data: series.data,
          borderColor: PALETTE[0],
          backgroundColor: 'rgba(37, 99, 166, 0.08)',
          fill: true,
          tension: 0.35,
          pointRadius: 2.5,
          pointBackgroundColor: PALETTE[0],
        }],
      },
      options: {
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return money(c.parsed.y); } } } },
        scales: {
          y: { ticks: { callback: function (v) { return money(v); } }, grid: { color: '#EEF3FA' } },
          x: { grid: { display: false } },
        },
      },
    };
    if (salesChart) {
      salesChart.data = config.data;
      salesChart.update();
    } else {
      salesChart = new Chart(ctx, config);
    }
  }

  window.ApotekCareDashboard = {
    showRange: function (days) {
      document.querySelectorAll('[data-range-toggle]').forEach(function (btn) {
        var active = btn.getAttribute('data-range-toggle') === String(days);
        btn.className = 'btn btn-sm ' + (active ? 'btn-soft' : 'btn-ghost');
      });
      if (seriesCache[days]) renderSalesChart(seriesCache[days]);
    },
  };

  fetch('/api/dashboard/charts')
    .then(function (r) { return r.json(); })
    .then(function (res) {
      if (!res.success) return;
      var d = res.data;
      seriesCache[7] = d.sales_7d;
      seriesCache[30] = d.sales_30d;
      renderSalesChart(d.sales_7d);

      var topProductsCtx = document.getElementById('chartTopProducts');
      if (topProductsCtx) {
        new Chart(topProductsCtx, {
          type: 'bar',
          data: { labels: d.top_products.labels, datasets: [{ label: 'Terjual', data: d.top_products.data, backgroundColor: PALETTE[1], borderRadius: 6, maxBarThickness: 36 }] },
          options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#EEF3FA' } }, y: { grid: { display: false } } } },
        });
      }

      var topCategoriesCtx = document.getElementById('chartTopCategories');
      if (topCategoriesCtx) {
        new Chart(topCategoriesCtx, {
          type: 'doughnut',
          data: { labels: d.top_categories.labels, datasets: [{ data: d.top_categories.data, backgroundColor: PALETTE }] },
          options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 14 } } }, cutout: '62%' },
        });
      }
    })
    .catch(function () {
      if (window.ApotekCare) window.ApotekCare.toast('Gagal memuat grafik dashboard.', 'error');
    });
})();
