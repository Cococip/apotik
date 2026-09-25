<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'Dashboard') ?> — <?= e(config('app.name')) ?></title>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#2563A6">
<link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-shell">
    <?php \App\Core\View::partial('components.sidebar', ['currentUser' => $currentUser]); ?>

    <div class="app-main">
        <?php \App\Core\View::partial('components.topbar', ['currentUser' => $currentUser]); ?>

        <main class="app-content">
            <?php if ($msg = flash('success')): ?>
                <span hidden data-flash="<?= e($msg) ?>" data-flash-type="success"></span>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <span hidden data-flash="<?= e($msg) ?>" data-flash-type="error"></span>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>
<div class="toast-stack"></div>
<script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
