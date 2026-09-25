<?php
/** @var array $permissionGroups */
/** @var array $permissionIdBySlug */
/** @var array $assigned */
?>
<div class="breadcrumb-trail"><a href="/roles">Role & Permission</a> <svg class="icon" style="width:12px;height:12px;"><use href="/assets/icons/sprite.svg#ic-chevron-right"></use></svg> <?= e($role['name']) ?></div>
<div class="page-header">
    <div>
        <p class="page-title">Atur Permission — <?= e($role['name']) ?></p>
        <p class="page-subtitle"><?= e($role['description'] ?: 'Pilih hak akses yang dimiliki role ini.') ?></p>
    </div>
</div>

<?php if ($role['slug'] === 'super_admin'): ?>
<div class="badge badge-info" style="display:block;padding:12px 16px;margin-bottom:16px;white-space:normal;">
    Super Admin selalu memiliki akses penuh ke seluruh sistem secara otomatis, terlepas dari centang di bawah.
</div>
<?php endif; ?>

<form method="POST" action="/roles/<?= $role['id'] ?>" data-loading-text="Menyimpan...">
    <?= csrf_field() ?><input type="hidden" name="_method" value="PUT">

    <?php foreach ($permissionGroups as $groupName => $items): ?>
        <div class="card-surface card-pad" style="margin-bottom:16px;">
            <p class="section-card-title" style="margin-bottom:12px;">
                <?= e($groupName) ?>
                <button type="button" class="btn btn-sm btn-ghost" onclick="toggleGroup(this, true)">Pilih Semua</button>
            </p>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;" data-group>
                <?php foreach ($items as $slug => $description): ?>
                    <?php $permId = $permissionIdBySlug[$slug] ?? null; ?>
                    <label class="form-check" style="font-size:13px;">
                        <input type="checkbox" name="permissions[]" value="<?= $permId ?>" <?= in_array($permId, $assigned) ? 'checked' : '' ?>>
                        <span><?= e($description) ?> <span class="table-cell-muted">(<?= e($slug) ?>)</span></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div style="display:flex;justify-content:flex-end;gap:10px;">
        <a href="/roles" class="btn btn-outline">Kembali</a>
        <button type="submit" class="btn btn-primary">Simpan Permission</button>
    </div>
</form>

<script>
function toggleGroup(btn, forceCheck) {
    var group = btn.closest('.card-surface').querySelector('[data-group]');
    var boxes = group.querySelectorAll('input[type="checkbox"]');
    var allChecked = Array.from(boxes).every(function (b) { return b.checked; });
    boxes.forEach(function (b) { b.checked = !allChecked; });
    btn.textContent = allChecked ? 'Pilih Semua' : 'Batalkan Semua';
}
</script>
