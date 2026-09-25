<?php
use App\Core\Auth;
$statusLabels = ['draft'=>'Draft','pending_verification'=>'Menunggu Verifikasi','verified'=>'Diverifikasi','processed'=>'Diproses','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
$statusBadge = ['draft'=>'badge-muted','pending_verification'=>'badge-warning','verified'=>'badge-info','processed'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger'];
$actionLabels = ['pending_verification'=>'Ajukan Verifikasi','verified'=>'Verifikasi Resep','processed'=>'Tandai Diproses','completed'=>'Tandai Selesai','cancelled'=>'Batalkan Resep'];
?>
<div class="breadcrumb-trail"><a href="/prescriptions">Resep</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> <?= e($prescription['prescription_number']) ?></div>
<div class="page-header">
    <div>
        <p class="page-title"><?= e($prescription['prescription_number']) ?></p>
        <p class="page-subtitle">
            <?= e(format_date($prescription['prescription_date'])) ?> ·
            Pasien <?= e($prescription['customer_name'] ?: '-') ?> · Dokter <?= e($prescription['doctor_name'] ?: '-') ?>
            · <span class="badge <?= $statusBadge[$prescription['status']] ?>"><?= $statusLabels[$prescription['status']] ?></span>
        </p>
    </div>
    <div style="display:flex;gap:8px;">
        <?php foreach ($nextStatuses as $next): ?>
            <?php if ($next === 'verified' && !Auth::can('prescription.verify')) continue; ?>
            <form method="POST" action="/prescriptions/<?= $prescription['id'] ?>/transition" data-confirm="<?= e($actionLabels[$next]) ?>?">
                <?= csrf_field() ?>
                <input type="hidden" name="status" value="<?= $next ?>">
                <button type="submit" class="btn <?= $next === 'cancelled' ? 'btn-outline' : 'btn-primary' ?>"><?= e($actionLabels[$next]) ?></button>
            </form>
        <?php endforeach; ?>
    </div>
</div>

<div class="card-surface card-pad">
    <p class="section-card-title">Item Obat</p>
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Obat</th><th>Dosis</th><th>Frekuensi</th><th>Qty</th><th>Aturan Pakai</th></tr></thead>
        <tbody>
        <?php foreach ($details as $d): ?>
            <tr>
                <td style="font-weight:600;"><?= e($d['medicine_name']) ?></td>
                <td class="table-cell-muted"><?= e($d['dosage'] ?: '-') ?></td>
                <td class="table-cell-muted"><?= e($d['frequency'] ?: '-') ?></td>
                <td><?= (int) $d['qty'] ?></td>
                <td class="table-cell-muted"><?= e($d['usage_instructions'] ?: '-') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php if ($prescription['notes']): ?><p class="table-cell-muted" style="margin-top:12px;">Catatan: <?= e($prescription['notes']) ?></p><?php endif; ?>
    <?php if ($prescription['verified_by_name']): ?>
        <p class="table-cell-muted" style="margin-top:6px;">Diverifikasi oleh <?= e($prescription['verified_by_name']) ?> pada <?= e(format_datetime($prescription['verified_at'])) ?></p>
    <?php endif; ?>
</div>
