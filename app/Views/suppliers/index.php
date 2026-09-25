<?php
use App\Core\Auth;
?>
<div class="page-header">
    <div>
        <p class="page-title">Supplier</p>
        <p class="page-subtitle">Data pemasok/distributor obat.</p>
    </div>
    <?php if (Auth::can('master.manage')): ?>
    <button type="button" class="btn btn-primary" data-open-create="supplierModal"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Tambah Supplier</button>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <div class="table-toolbar">
        <form class="table-search" method="get">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-search"></use></svg>
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Cari nama/kode supplier...">
        </form>
    </div>
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-users"></use></svg></div>
            <h3>Belum ada data supplier</h3>
            <p>Tambahkan data pemasok/distributor pertama.</p>
        </div>
    <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Kode</th><th>Nama</th><th>Kontak</th><th>Telepon</th><th>Termin</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="table-cell-muted"><?= e($row['code']) ?></td>
                    <td style="font-weight:600;"><?= e($row['name']) ?></td>
                    <td><?= e($row['contact_person'] ?: '-') ?></td>
                    <td class="table-cell-muted"><?= e($row['phone'] ?: '-') ?></td>
                    <td class="table-cell-muted"><?= (int) $row['payment_term_days'] ?> hari</td>
                    <td><span class="badge <?= $row['status'] === 'active' ? 'badge-success' : 'badge-muted' ?>"><?= $row['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td class="table-actions">
                        <?php if (Auth::can('master.manage')): ?>
                        <button type="button" class="btn btn-icon btn-ghost" data-open-edit="supplierModal" data-record='<?= e(json_encode($row)) ?>'><svg class="icon"><use href="/assets/icons/sprite.svg#ic-edit"></use></svg></button>
                        <form method="POST" action="/suppliers/<?= $row['id'] ?>" data-confirm="Hapus supplier ini?" style="display:inline;">
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

<div class="modal fade" id="supplierModal" tabindex="-1" data-crud-modal data-route-base="<?= e($routeBase) ?>" data-auto-open="<?= e($openModal ?? '') ?>" data-create-title="Tambah Supplier" data-edit-title="Edit Supplier">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= e($routeBase) ?>" data-loading-text="Menyimpan...">
                <?= csrf_field() ?><input type="hidden" name="_method" value="">
                <div class="modal-header"><h5 class="modal-title" data-modal-title>Tambah Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kode</label>
                            <input type="text" name="code" class="form-control" data-old="<?= e(old('code')) ?>" placeholder="Otomatis jika kosong">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Supplier<span class="required">*</span></label>
                            <input type="text" name="name" class="form-control" data-old="<?= e(old('name')) ?>" required>
                            <?php if ($err = field_error('name')): ?><div class="form-error"><?= e($err) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kontak Person</label>
                            <input type="text" name="contact_person" class="form-control" data-old="<?= e(old('contact_person')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="phone" class="form-control" data-old="<?= e(old('phone')) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" data-old="<?= e(old('email')) ?>">
                            <?php if ($err = field_error('email')): ?><div class="form-error"><?= e($err) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">NPWP</label>
                            <input type="text" name="npwp" class="form-control" data-old="<?= e(old('npwp')) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Termin Pembayaran (hari)</label>
                            <input type="number" name="payment_term_days" class="form-control" data-old="<?= e(old('payment_term_days')) ?>" min="0" value="30">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="2" data-old="<?= e(old('address')) ?>"></textarea>
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
