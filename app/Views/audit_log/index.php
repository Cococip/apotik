<?php
$actionLabels = ['login' => 'Login', 'logout' => 'Logout', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus'];
$actionBadge = ['login' => 'badge-success', 'logout' => 'badge-muted', 'create' => 'badge-info', 'update' => 'badge-warning', 'delete' => 'badge-danger'];
?>
<div class="page-header">
    <div>
        <p class="page-title">Audit Log</p>
        <p class="page-subtitle">Jejak aktivitas penting seluruh pengguna sistem.</p>
    </div>
</div>

<div class="card-surface card-pad">
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
        <input type="search" name="search" value="<?= e($search) ?>" class="form-control" style="max-width:220px;" placeholder="Cari user/aksi...">
        <select name="module" class="form-select" style="max-width:200px;">
            <option value="">Semua Modul</option>
            <?php foreach ($modules as $m): ?><option value="<?= e($m) ?>" <?= $module === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
        </select>
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="form-control" style="max-width:170px;">
        <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="form-control" style="max-width:170px;">
        <button type="submit" class="btn btn-outline">Filter</button>
        <a href="/audit-log" class="btn btn-ghost">Reset</a>
    </form>

    <?php if (empty($rows)): ?>
        <div class="empty-state"><p>Tidak ada aktivitas yang cocok dengan filter.</p></div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Modul</th><th>ID Data</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td class="table-cell-muted"><?= e(format_datetime($row['created_at'])) ?></td>
                <td style="font-weight:600;"><?= e($row['user_name'] ?: 'Sistem') ?></td>
                <td><span class="badge <?= $actionBadge[$row['action']] ?? 'badge-muted' ?>"><?= e($actionLabels[$row['action']] ?? $row['action']) ?></span></td>
                <td class="table-cell-muted"><?= e($row['module']) ?></td>
                <td class="table-cell-muted">#<?= e($row['record_id'] ?: '-') ?></td>
                <td class="table-cell-muted"><?= e($row['ip_address']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php render_pagination($pagination, '/audit-log', ['search' => $search, 'module' => $module, 'date_from' => $dateFrom, 'date_to' => $dateTo]); ?>
    <?php endif; ?>
</div>
