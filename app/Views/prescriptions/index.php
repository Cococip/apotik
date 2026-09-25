<?php
use App\Core\Auth;
$statusLabels = ['draft'=>'Draft','pending_verification'=>'Menunggu Verifikasi','verified'=>'Diverifikasi','processed'=>'Diproses','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
$statusBadge = ['draft'=>'badge-muted','pending_verification'=>'badge-warning','verified'=>'badge-info','processed'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger'];
?>
<div class="page-header">
    <div>
        <p class="page-title">Resep</p>
        <p class="page-subtitle">Pengelolaan resep dokter dan verifikasi apoteker.</p>
    </div>
    <?php if (Auth::can('prescription.create')): ?>
    <a href="/prescriptions/create" class="btn btn-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Input Resep</a>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
        <a href="/prescriptions" class="btn btn-sm <?= $status==='' ? 'btn-soft' : 'btn-outline' ?>">Semua</a>
        <?php foreach ($statusLabels as $key => $label): ?>
            <a href="?status=<?= $key ?>" class="btn btn-sm <?= $status===$key ? 'btn-soft' : 'btn-outline' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-clipboard"></use></svg></div>
            <h3>Belum ada resep</h3>
            <p>Input resep pertama untuk mulai proses verifikasi.</p>
        </div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>No. Resep</th><th>Tanggal</th><th>Pasien</th><th>Dokter</th><th>Diinput Oleh</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td style="font-weight:600;"><?= e($row['prescription_number']) ?></td>
                <td class="table-cell-muted"><?= e(format_date($row['prescription_date'])) ?></td>
                <td><?= e($row['customer_name'] ?: '-') ?></td>
                <td><?= e($row['doctor_name'] ?: '-') ?></td>
                <td class="table-cell-muted"><?= e($row['input_by_name'] ?: '-') ?></td>
                <td><span class="badge <?= $statusBadge[$row['status']] ?>"><?= $statusLabels[$row['status']] ?></span></td>
                <td class="table-actions"><a href="/prescriptions/<?= $row['id'] ?>" class="btn btn-sm btn-soft">Detail</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
