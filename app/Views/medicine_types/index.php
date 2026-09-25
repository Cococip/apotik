<?php
use App\Core\Auth;
?>
<div class="page-header">
    <div>
        <p class="page-title">Jenis Obat</p>
        <p class="page-subtitle">Bentuk sediaan obat: tablet, kapsul, sirup, dan lainnya.</p>
    </div>
    <?php if (Auth::can('master.manage')): ?>
    <button type="button" class="btn btn-primary" data-open-create="typeModal"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Tambah Jenis</button>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <div class="table-toolbar">
        <form class="table-search" method="get">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-search"></use></svg>
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Cari jenis obat...">
        </form>
    </div>
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-list"></use></svg></div>
            <h3>Belum ada data jenis obat</h3>
            <p>Tambahkan jenis obat pertama, contoh: Tablet, Kapsul, Sirup.</p>
        </div>
    <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Nama</th><th>Deskripsi</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td style="font-weight:600;"><?= e($row['name']) ?></td>
                    <td class="table-cell-muted"><?= e($row['description'] ?: '-') ?></td>
                    <td><span class="badge <?= $row['status'] === 'active' ? 'badge-success' : 'badge-muted' ?>"><?= $row['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td class="table-actions">
                        <?php if (Auth::can('master.manage')): ?>
                        <button type="button" class="btn btn-icon btn-ghost" data-open-edit="typeModal" data-record='<?= e(json_encode($row)) ?>'><svg class="icon"><use href="/assets/icons/sprite.svg#ic-edit"></use></svg></button>
                        <form method="POST" action="/medicine-types/<?= $row['id'] ?>" data-confirm="Hapus jenis obat ini?" style="display:inline;">
                            <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-icon btn-ghost"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-trash"></use></svg></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php render_pagination($pagination, $routeBase, ['search' => $search]); ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="typeModal" tabindex="-1" data-crud-modal data-route-base="<?= e($routeBase) ?>" data-auto-open="<?= e($openModal ?? '') ?>" data-create-title="Tambah Jenis Obat" data-edit-title="Edit Jenis Obat">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= e($routeBase) ?>" data-loading-text="Menyimpan...">
                <?= csrf_field() ?><input type="hidden" name="_method" value="">
                <div class="modal-header"><h5 class="modal-title" data-modal-title>Tambah Jenis Obat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Jenis<span class="required">*</span></label>
                        <input type="text" name="name" class="form-control" data-old="<?= e(old('name')) ?>" required>
                        <?php if ($err = field_error('name')): ?><div class="form-error"><?= e($err) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2" data-old="<?= e(old('description')) ?>"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="/assets/js/crud-modal.js" defer></script>
