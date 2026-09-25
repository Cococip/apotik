<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'Masuk') ?> — <?= e(config('app.name')) ?></title>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#2563A6">
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?= $content ?>
<div class="toast-stack"></div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
