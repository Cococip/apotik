<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'POS Kasir') ?> — <?= e(config('app.name')) ?></title>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#2563A6">
<link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/pos.css">
</head>
<body>
<div class="pos-shell">
    <header class="pos-topbar">
        <a href="/" class="topbar-btn" aria-label="Kembali ke Dashboard">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-home"></use></svg>
        </a>
        <div class="pos-topbar-title">
            <strong>POS Kasir</strong>
            <span class="table-cell-muted">Shift <?= e($shift['shift_number'] ?? '-') ?></span>
        </div>
        <div class="topbar-right">
            <span class="connection-badge" id="connectionBadge"><span class="dot"></span><span class="conn-label">ONLINE</span></span>
            <span class="badge badge-info" id="syncQueueBadge" style="display:none;"></span>
            <a href="/shift" class="btn btn-sm btn-outline">Tutup Shift</a>
            <form method="POST" action="/logout"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-ghost">Keluar</button></form>
        </div>
    </header>
    <main class="pos-content">
        <?= $content ?>
    </main>
</div>
<div class="toast-stack"></div>
<script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
