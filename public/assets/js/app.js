(function () {
  'use strict';

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/service-worker.js').catch(function () {
        // PWA shell caching is best-effort; app still works fully online without it.
      });
    });
  }
})();

/**
 * Global utilities available on every layout (main app shell, POS, auth) —
 * intentionally NOT gated behind `.app-shell`, since the POS layout uses its
 * own `.pos-shell` and still needs toast/confirm/loading-state/online-badge.
 */
(function () {
  'use strict';

  /* ---------------- Toasts ---------------- */
  window.ApotekCare = window.ApotekCare || {};
  window.ApotekCare.toast = function (message, type) {
    var stack = document.querySelector('.toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      document.body.appendChild(stack);
    }
    var item = document.createElement('div');
    item.className = 'toast-item toast-' + (type || 'info');
    item.textContent = message;
    stack.appendChild(item);
    setTimeout(function () {
      item.style.opacity = '0';
      item.style.transition = 'opacity .2s ease';
      setTimeout(function () { item.remove(); }, 200);
    }, 4200);
  };
  document.querySelectorAll('[data-flash]').forEach(function (el) {
    window.ApotekCare.toast(el.getAttribute('data-flash'), el.getAttribute('data-flash-type') || 'info');
  });

  /* ---------------- Online / offline indicator ---------------- */
  var connBadge = document.getElementById('connectionBadge');
  function setConnectionState(isOnline) {
    if (!connBadge) return;
    connBadge.classList.toggle('is-offline', !isOnline);
    connBadge.querySelector('.conn-label').textContent = isOnline ? 'ONLINE' : 'OFFLINE';
  }
  function pingServer() {
    fetch('/manifest.json', { method: 'HEAD', cache: 'no-store' })
      .then(function () { setConnectionState(true); })
      .catch(function () { setConnectionState(false); });
  }
  window.addEventListener('online', pingServer);
  window.addEventListener('offline', function () { setConnectionState(false); });
  if (connBadge) {
    pingServer();
    setInterval(pingServer, 20000);
  }

  /* ---------------- Theme toggle ---------------- */
  var THEME_KEY = 'apotekcare_theme';
  var themeBtn = document.getElementById('themeToggle');
  var savedTheme = localStorage.getItem(THEME_KEY);
  if (savedTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
  }
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (isDark) {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem(THEME_KEY, 'light');
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem(THEME_KEY, 'dark');
      }
    });
  }

  /* ---------------- Confirm dialogs for destructive actions ---------------- */
  document.querySelectorAll('[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });

  /* ---------------- Submit loading state ---------------- */
  document.querySelectorAll('form[data-loading-text]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var submitBtn = form.querySelector('[type="submit"]');
      if (submitBtn && !submitBtn.disabled) {
        submitBtn.disabled = true;
        submitBtn.dataset.originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = form.getAttribute('data-loading-text');
      }
    });
  });
})();

/**
 * Main app shell only (sidebar/topbar) — these elements genuinely don't
 * exist on the POS or auth layouts, so this block can stay gated.
 */
(function () {
  'use strict';

  var shell = document.querySelector('.app-shell');
  if (!shell) return;

  /* ---------------- Sidebar collapse (desktop) ---------------- */
  var collapseBtn = document.getElementById('sidebarCollapseToggle');
  var COLLAPSE_KEY = 'apotekcare_sidebar_collapsed';
  if (localStorage.getItem(COLLAPSE_KEY) === '1') {
    shell.classList.add('is-collapsed');
  }
  if (collapseBtn) {
    collapseBtn.addEventListener('click', function () {
      shell.classList.toggle('is-collapsed');
      localStorage.setItem(COLLAPSE_KEY, shell.classList.contains('is-collapsed') ? '1' : '0');
    });
  }

  /* ---------------- Sidebar drawer (mobile) ---------------- */
  var drawerBtn = document.getElementById('sidebarDrawerToggle');
  var backdrop = document.querySelector('.sidebar-backdrop');
  function closeDrawer() {
    shell.classList.remove('is-drawer-open');
    if (backdrop) backdrop.classList.remove('is-visible');
  }
  if (drawerBtn) {
    drawerBtn.addEventListener('click', function () {
      shell.classList.add('is-drawer-open');
      if (backdrop) backdrop.classList.add('is-visible');
    });
  }
  if (backdrop) backdrop.addEventListener('click', closeDrawer);
  document.querySelectorAll('.sidebar-link').forEach(function (link) {
    link.addEventListener('click', function () {
      if (window.innerWidth <= 991) closeDrawer();
    });
  });

  /* ---------------- Submenu expand/collapse ---------------- */
  document.querySelectorAll('.sidebar-group-toggle').forEach(function (toggle) {
    var submenu = document.getElementById(toggle.getAttribute('aria-controls'));
    var chevron = toggle.querySelector('.sidebar-chevron');
    if (submenu && submenu.classList.contains('is-open')) {
      if (chevron) chevron.classList.add('is-open');
    }
    toggle.addEventListener('click', function () {
      if (!submenu) return;
      var isOpen = submenu.classList.toggle('is-open');
      if (chevron) chevron.classList.toggle('is-open', isOpen);
    });
  });

  /* ---------------- Dropdowns (user menu / notifications) ---------------- */
  document.querySelectorAll('[data-dropdown-toggle]').forEach(function (toggle) {
    var menu = document.getElementById(toggle.getAttribute('data-dropdown-toggle'));
    if (!menu) return;
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var willOpen = !menu.classList.contains('is-open');
      document.querySelectorAll('.dropdown-menu.is-open').forEach(function (m) { m.classList.remove('is-open'); });
      if (willOpen) menu.classList.add('is-open');
    });
  });
  document.addEventListener('click', function () {
    document.querySelectorAll('.dropdown-menu.is-open').forEach(function (m) { m.classList.remove('is-open'); });
  });

  /* ---------------- Notifications ---------------- */
  var bell = document.getElementById('notificationBell');
  var notifList = document.getElementById('notificationList');
  var notifCount = document.getElementById('notificationCount');

  function renderNotifications(items) {
    if (!notifList) return;
    if (!items.length) {
      notifList.innerHTML = '<div class="empty-state" style="padding:28px 16px;"><p>Tidak ada notifikasi baru.</p></div>';
      return;
    }
    notifList.innerHTML = items.map(function (n) {
      return '<button type="button" class="notif-item' + (n.is_read ? '' : ' is-unread') + '" data-id="' + n.id + '">' +
        '<span class="notif-title">' + n.title + '</span>' +
        '<span class="notif-message">' + n.message + '</span>' +
        '<span class="notif-time">' + n.created_at + '</span>' +
        '</button>';
    }).join('');
  }

  function loadNotifications() {
    if (!notifList) return;
    fetch('/api/notifications', { headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.success) return;
        renderNotifications(res.data.items || []);
        var unread = res.data.unread_count || 0;
        if (notifCount) {
          notifCount.textContent = unread;
          notifCount.style.display = unread > 0 ? 'flex' : 'none';
        }
      })
      .catch(function () { /* offline — keep last known list */ });
  }

  if (notifList) {
    notifList.addEventListener('click', function (e) {
      var btn = e.target.closest('.notif-item');
      if (!btn) return;
      var id = btn.getAttribute('data-id');
      fetch('/api/notifications/' + id + '/read', { method: 'POST', headers: { 'X-Requested-With': 'fetch' } })
        .then(function () { btn.classList.remove('is-unread'); loadNotifications(); });
    });
  }

  if (bell) {
    loadNotifications();
    setInterval(loadNotifications, 45000);
  }
})();
