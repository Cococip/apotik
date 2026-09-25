<?php
use App\Services\ExpiryStatusService;
?>
<div class="page-header">
    <div>
        <p class="page-title">Batch &amp; Expired</p>
        <p class="page-subtitle">Seluruh batch obat di semua produk, diurutkan berdasarkan tanggal expired.</p>
    </div>
</div>

<div class="card-surface card-pad">
    <div class="table-toolbar">
        <form class="table-search" method="get" style="flex:1;">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-search"></use></svg>
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Cari nama obat atau nomor batch...">
        </form>
        <div style="display:flex;gap:8px;">
            <?php $filters = ['all' => 'Semua', 'active' => 'Aktif', 'expiring' => 'Akan Expired', 'expired' => 'Expired']; ?>
            <?php foreach ($filters as $key => $label): ?>
                <a href="?filter=<?= $key ?>&search=<?= e($search) ?>" class="btn btn-sm <?= $filter === $key ? 'btn-soft' : 'btn-outline' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-layers"></use></svg></div>
            <h3>Tidak ada batch ditemukan</h3>
            <p>Coba ubah filter atau kata kunci pencarian.</p>
        </div>
    <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Obat</th><th>Batch</th><th>Diterima</th><th>Expired</th><th>Qty Tersedia</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $batch): $es = ExpiryStatusService::statusFor($batch['expired_date']); ?>
                <tr>
                    <td><a href="/medicines/<?= $batch['medicine_id'] ?>/edit" style="font-weight:600;color:var(--color-ink);"><?= e($batch['medicine_name']) ?></a><div class="table-cell-muted"><?= e($batch['medicine_code']) ?></div></td>
                    <td class="table-cell-muted"><?= e($batch['batch_number']) ?></td>
                    <td class="table-cell-muted"><?= e(format_date($batch['received_date'])) ?></td>
                    <td class="table-cell-muted"><?= e(format_date($batch['expired_date'])) ?></td>
                    <td><?= (int) $batch['available_qty'] ?></td>
                    <td>
                        <?php if ($batch['status'] === 'recalled'): ?><span class="badge badge-muted">Rusak/Ditarik</span>
                        <?php elseif ($batch['status'] === 'depleted'): ?><span class="badge badge-muted">Habis</span>
                        <?php else: ?><span class="badge <?= e($es['badge']) ?>"><?= e($es['label']) ?></span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php render_pagination($pagination, '/stock/batches', ['search' => $search, 'filter' => $filter]); ?>
    <?php endif; ?>
</div>
