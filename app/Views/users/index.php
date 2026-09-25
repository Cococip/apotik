<?php
use App\Core\Auth;
?>
<div class="page-header">
    <div>
        <p class="page-title">User</p>
        <p class="page-subtitle">Kelola akun pengguna sistem.</p>
    </div>
    <?php if (Auth::can('user.create')): ?>
    <button type="button" class="btn btn-primary" data-open-create="userModal"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Tambah User</button>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <div class="table-toolbar">
        <form class="table-search" method="get">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-search"></use></svg>
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Cari nama, username, atau email...">
        </form>
    </div>

    <div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Nama Lengkap</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Login Terakhir</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td style="font-weight:600;"><?= e($row['full_name']) ?><?= $row['id'] == ($currentUser['id'] ?? 0) ? ' <span class="badge badge-info">Anda</span>' : '' ?></td>
                <td class="table-cell-muted"><?= e($row['username']) ?></td>
                <td class="table-cell-muted"><?= e($row['email']) ?></td>
                <td><span class="badge badge-info"><?= e($row['role_name']) ?></span></td>
                <td><span class="badge <?= $row['status'] === 'active' ? 'badge-success' : 'badge-muted' ?>"><?= $row['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
                <td class="table-cell-muted"><?= $row['last_login_at'] ? e(format_datetime($row['last_login_at'])) : 'Belum pernah' ?></td>
                <td class="table-actions">
                    <?php if (Auth::can('user.update')): ?>
                    <button type="button" class="btn btn-icon btn-ghost" data-open-edit="userModal" data-record='<?= e(json_encode(['id' => $row['id'], 'username' => $row['username'], 'email' => $row['email'], 'full_name' => $row['full_name'], 'phone' => $row['phone'], 'role_id' => $row['role_id'], 'status' => $row['status']])) ?>'>
                        <svg class="icon"><use href="/assets/icons/sprite.svg#ic-edit"></use></svg>
                    </button>
                    <?php endif; ?>
                    <?php if (Auth::can('user.delete') && $row['id'] != ($currentUser['id'] ?? 0)): ?>
                    <form method="POST" action="/users/<?= $row['id'] ?>" data-confirm="Nonaktifkan user ini?" style="display:inline;">
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
</div>

<div class="modal fade" id="userModal" tabindex="-1" data-crud-modal data-route-base="<?= e($routeBase) ?>" data-auto-open="<?= e($openModal ?? '') ?>" data-create-title="Tambah User" data-edit-title="Edit User">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= e($routeBase) ?>" data-loading-text="Menyimpan...">
                <?= csrf_field() ?><input type="hidden" name="_method" value="">
                <div class="modal-header"><h5 class="modal-title" data-modal-title>Tambah User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Username<span class="required">*</span></label>
                            <input type="text" name="username" class="form-control" data-old="<?= e(old('username')) ?>" required>
                            <?php if ($err = field_error('username')): ?><div class="form-error"><?= e($err) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap<span class="required">*</span></label>
                            <input type="text" name="full_name" class="form-control" data-old="<?= e(old('full_name')) ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email<span class="required">*</span></label>
                            <input type="email" name="email" class="form-control" data-old="<?= e(old('email')) ?>" required>
                            <?php if ($err = field_error('email')): ?><div class="form-error"><?= e($err) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="phone" class="form-control" data-old="<?= e(old('phone')) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Role<span class="required">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span class="password-hint">(kosongkan jika tidak diubah saat edit)</span></label>
                        <input type="password" name="password" class="form-control" minlength="6" placeholder="Minimal 6 karakter">
                        <?php if ($err = field_error('password')): ?><div class="form-error"><?= e($err) ?></div><?php endif; ?>
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
