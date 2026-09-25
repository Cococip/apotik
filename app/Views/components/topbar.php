<?php
/** @var array|null $currentUser */
$initials = '';
if (!empty($currentUser['full_name'])) {
    $parts = explode(' ', trim($currentUser['full_name']));
    $initials = strtoupper(substr($parts[0], 0, 1) . (substr($parts[1] ?? '', 0, 1)));
}
?>
<header class="app-topbar">
    <div style="display:flex;align-items:center;gap:10px;">
        <button type="button" class="topbar-btn" id="sidebarDrawerToggle" aria-label="Buka menu" style="display:none;">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-menu"></use></svg>
        </button>
        <button type="button" class="topbar-btn" id="sidebarCollapseToggle" aria-label="Ciutkan menu">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-menu"></use></svg>
        </button>
        <form class="topbar-search" action="/medicines" method="get">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-search"></use></svg>
            <input type="search" name="search" placeholder="Cari obat, kode, atau barcode..." aria-label="Cari obat">
        </form>
    </div>

    <div class="topbar-right">
        <span class="connection-badge" id="connectionBadge">
            <span class="dot"></span><span class="conn-label">ONLINE</span>
        </span>

        <button type="button" class="topbar-btn" id="themeToggle" aria-label="Ganti tema">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-settings"></use></svg>
        </button>

        <div class="dropdown-wrap">
            <button type="button" class="topbar-btn notification-bell-wrap" id="notificationBell" data-dropdown-toggle="notificationMenu" aria-label="Notifikasi">
                <svg class="icon"><use href="/assets/icons/sprite.svg#ic-bell"></use></svg>
                <span class="notification-count" id="notificationCount">0</span>
            </button>
            <div class="dropdown-menu dropdown-wide" id="notificationMenu">
                <div class="dropdown-header"><span>Notifikasi</span></div>
                <div class="notif-list" id="notificationList">
                    <div class="empty-state" style="padding:28px 16px;"><p>Memuat notifikasi...</p></div>
                </div>
            </div>
        </div>

        <div class="dropdown-wrap">
            <button type="button" class="user-chip" data-dropdown-toggle="userMenu">
                <span class="user-avatar"><?= e($initials ?: 'U') ?></span>
                <span class="user-chip-meta">
                    <span class="user-chip-name"><?= e($currentUser['full_name'] ?? 'Pengguna') ?></span>
                    <span class="user-chip-role"><?= e($currentUser['role_name'] ?? '-') ?></span>
                </span>
            </button>
            <div class="dropdown-menu" id="userMenu">
                <div class="dropdown-header"><span><?= e($currentUser['email'] ?? '') ?></span></div>
                <div class="dropdown-divider"></div>
                <form method="POST" action="/logout">
                    <?= csrf_field() ?>
                    <button type="submit" class="dropdown-item">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#ic-log-out"></use></svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
