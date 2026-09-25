<div class="breadcrumb-trail"><a href="/stock/opname">Stok Opname</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> <?= e($opname['opname_number']) ?></div>
<div class="page-header">
    <div>
        <p class="page-title">Detail Opname <?= e($opname['opname_number']) ?></p>
        <p class="page-subtitle">Tanggal <?= e(format_date($opname['opname_date'])) ?> · Petugas <?= e($opname['user_name'] ?: '-') ?><?= $opname['note'] ? ' · ' . e($opname['note']) : '' ?></p>
    </div>
</div>

<div class="card-surface card-pad">
    <?php if (empty($details)): ?>
        <div class="empty-state"><p>Tidak ada item pada sesi ini.</p></div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Obat</th><th>Batch</th><th>Stok Sistem</th><th>Stok Fisik</th><th>Selisih</th></tr></thead>
        <tbody>
        <?php foreach ($details as $d): ?>
            <tr>
                <td style="font-weight:600;"><?= e($d['medicine_name']) ?></td>
                <td class="table-cell-muted"><?= e($d['batch_number']) ?></td>
                <td><?= (int) $d['system_qty'] ?></td>
                <td><?= (int) $d['physical_qty'] ?></td>
                <td>
                    <?php if ($d['difference_qty'] == 0): ?>
                        <span class="badge badge-success">Sesuai</span>
                    <?php elseif ($d['difference_qty'] > 0): ?>
                        <span class="badge badge-info">+<?= (int) $d['difference_qty'] ?></span>
                    <?php else: ?>
                        <span class="badge badge-danger"><?= (int) $d['difference_qty'] ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
