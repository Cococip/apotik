<?php
use App\Core\Auth;
?>
<div class="page-header">
    <div>
        <p class="page-title">Pasien</p>
        <p class="page-subtitle">Data pasien untuk riwayat transaksi dan resep.</p>
    </div>
    <?php if (Auth::can('master.manage')): ?>
    <button type="button" class="btn btn-primary" data-open-create="patientModal"><svg class="icon"><use href="/assets/icons/sprite.svg#ic-plus"></use></svg> Tambah Pasien</button>
    <?php endif; ?>
</div>

<div class="card-surface card-pad">
    <div class="table-toolbar">
        <form class="table-search" method="get">
            <svg class="icon"><use href="/assets/icons/sprite.svg#ic-search"></use></svg>
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Cari nama/no. pasien...">
        </form>
    </div>
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg class="icon" style="width:26px;height:26px;"><use href="/assets/icons/sprite.svg#ic-user"></use></svg></div>
            <h3>Belum ada data pasien</h3>
            <p>Tambahkan data pasien pertama.</p>
        </div>
    <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>No. Pasien</th><th>Nama</th><th>J. Kelamin</th><th>Telepon</th><th>Alamat</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="table-cell-muted"><?= e($row['patient_number']) ?></td>
                    <td style="font-weight:600;"><?= e($row['name']) ?></td>
                    <td><?= $row['gender'] === 'L' ? 'Laki-laki' : ($row['gender'] === 'P' ? 'Perempuan' : '-') ?></td>
                    <td class="table-cell-muted"><?= e($row['phone'] ?: '-') ?></td>
                    <td class="table-cell-muted"><?= e($row['address'] ?: '-') ?></td>
                    <td class="table-actions">
                        <?php if (Auth::can('master.manage')): ?>
                        <button type="button" class="btn btn-icon btn-ghost" data-open-edit="patientModal" data-record='<?= e(json_encode($row)) ?>'><svg class="icon"><use href="/assets/icons/sprite.svg#ic-edit"></use></svg></button>
                        <form method="POST" action="/patients/<?= $row['id'] ?>" data-confirm="Hapus pasien ini?" style="display:inline;">
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

<div class="modal fade" id="patientModal" tabindex="-1" data-crud-modal data-route-base="<?= e($routeBase) ?>" data-auto-open="<?= e($openModal ?? '') ?>" data-create-title="Tambah Pasien" data-edit-title="Edit Pasien">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= e($routeBase) ?>" data-loading-text="Menyimpan...">
                <?= csrf_field() ?><input type="hidden" name="_method" value="">
                <div class="modal-header"><h5 class="modal-title" data-modal-title>Tambah Pasien</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">No. Pasien</label>
                            <input type="text" name="patient_number" class="form-control" data-old="<?= e(old('patient_number')) ?>" placeholder="Otomatis jika kosong">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Pasien<span class="required">*</span></label>
                            <input type="text" name="name" class="form-control" data-old="<?= e(old('name')) ?>" required>
                            <?php if ($err = field_error('name')): ?><div class="form-error"><?= e($err) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="birth_date" class="form-control" data-old="<?= e(old('birth_date')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="gender" class="form-select">
                                <option value="">-</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="phone" class="form-control" data-old="<?= e(old('phone')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" data-old="<?= e(old('email')) ?>">
                            <?php if ($err = field_error('email')): ?><div class="form-error"><?= e($err) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="2" data-old="<?= e(old('address')) ?>"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2" data-old="<?= e(old('notes')) ?>"></textarea>
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
