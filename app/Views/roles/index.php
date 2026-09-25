<?php
use App\Core\Auth;
?>
<div class="page-header">
    <div>
        <p class="page-title">Role &amp; Permission</p>
        <p class="page-subtitle">Kelola peran pengguna dan hak akses granular per modul.</p>
    </div>
    <button type="button" class="btn btn-primary" data-open-create="roleModal"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Tambah Role</button>
</div>

<div class="card-surface card-pad">
    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Nama Role</th><th>Deskripsi</th><th>Jumlah Permission</th><th>Jumlah User</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td style="font-weight:600;"><?= e($row['name']) ?> <?php if ($row['is_system']): ?><span class="badge badge-muted">Sistem</span><?php endif; ?></td>
                <td class="table-cell-muted"><?= e($row['description'] ?: '-') ?></td>
                <td><?= (int) $row['permission_count'] ?> permission</td>
                <td><?= (int) $row['user_count'] ?> user</td>
                <td class="table-actions">
                    <a href="/roles/<?= $row['id'] ?>/edit" class="btn btn-sm btn-soft">Atur Permission</a>
                    <?php if (!$row['is_system'] && (int) $row['user_count'] === 0): ?>
                    <form method="POST" action="/roles/<?= $row['id'] ?>" data-confirm="Hapus role ini?" style="display:inline;">
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
</div>

<div class="modal fade" id="roleModal" tabindex="-1" data-crud-modal data-route-base="/roles" data-auto-open="<?= e($openModal ?? '') ?>" data-create-title="Tambah Role">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/roles" data-loading-text="Menyimpan...">
                <?= csrf_field() ?><input type="hidden" name="_method" value="">
                <div class="modal-header"><h5 class="modal-title" data-modal-title>Tambah Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Role<span class="required">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <p class="form-help">Setelah dibuat, Anda akan diarahkan untuk mengatur permission role ini.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan &amp; Atur Permission</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="/assets/js/crud-modal.js" defer></script>
