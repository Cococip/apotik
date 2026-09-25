<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Role;
use App\Services\AuditLogger;

class RoleController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::connection();
        $rows = $db->query(
            "SELECT r.*, COUNT(DISTINCT rp.permission_id) AS permission_count, COUNT(DISTINCT u.id) AS user_count
             FROM roles r
             LEFT JOIN role_permissions rp ON rp.role_id = r.id
             LEFT JOIN users u ON u.role_id = r.id AND u.deleted_at IS NULL
             GROUP BY r.id
             ORDER BY r.id ASC"
        )->fetchAll();

        $this->view('roles.index', [
            'title' => 'Role & Permission',
            'rows' => $rows,
            'openModal' => flash('open_modal'),
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        $validator = $this->validate($input, ['name' => 'required|max:60']);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Session::flash('open_modal', 'create');
            $this->redirect('/roles');
            return;
        }

        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $input['name']), '_'));

        try {
            $id = Role::create(['name' => $input['name'], 'slug' => $slug, 'description' => $input['description'] ?? null, 'is_system' => 0]);
        } catch (\PDOException $e) {
            Session::flash('error', 'Nama role sudah digunakan.');
            $this->redirect('/roles');
            return;
        }

        AuditLogger::log('create', 'role', $id, null, $input);
        Session::flash('success', 'Role berhasil dibuat. Silakan atur permission-nya.');
        $this->redirect('/roles/' . $id . '/edit');
    }

    public function edit(Request $request, string $id): void
    {
        $id = (int) $id;
        $role = Role::find($id);
        if (!$role) {
            Session::flash('error', 'Role tidak ditemukan.');
            $this->redirect('/roles');
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT permission_id FROM role_permissions WHERE role_id = :id');
        $stmt->execute(['id' => $id]);
        $assigned = array_column($stmt->fetchAll(), 'permission_id');

        $this->view('roles.edit', [
            'title' => 'Edit Role — ' . $role['name'],
            'role' => $role,
            'permissionGroups' => config('permissions'),
            'permissionIdBySlug' => $this->permissionIdMap(),
            'assigned' => $assigned,
        ]);
    }

    public function update(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        $role = Role::find($id);
        if (!$role) {
            Session::flash('error', 'Role tidak ditemukan.');
            $this->redirect('/roles');
            return;
        }

        $permissionIds = array_map('intval', (array) $request->input('permissions', []));

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM role_permissions WHERE role_id = :id')->execute(['id' => $id]);
            $insert = $db->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
            foreach ($permissionIds as $permissionId) {
                $insert->execute(['role_id' => $id, 'permission_id' => $permissionId]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Gagal menyimpan permission.');
            $this->redirect('/roles/' . $id . '/edit');
            return;
        }

        AuditLogger::log('update', 'role_permission', $id, null, ['permission_ids' => $permissionIds]);
        Session::flash('success', 'Permission untuk role ' . $role['name'] . ' berhasil disimpan.');
        $this->redirect('/roles/' . $id . '/edit');
    }

    public function destroy(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        $role = Role::find($id);
        if (!$role) {
            Session::flash('error', 'Role tidak ditemukan.');
            $this->redirect('/roles');
            return;
        }

        if ((int) $role['is_system'] === 1) {
            Session::flash('error', 'Role bawaan sistem tidak dapat dihapus.');
            $this->redirect('/roles');
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT COUNT(*) AS c FROM users WHERE role_id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        if ((int) $stmt->fetch()['c'] > 0) {
            Session::flash('error', 'Role masih digunakan oleh pengguna aktif, tidak dapat dihapus.');
            $this->redirect('/roles');
            return;
        }

        $db->prepare('DELETE FROM role_permissions WHERE role_id = :id')->execute(['id' => $id]);
        Role::delete($id);
        AuditLogger::log('delete', 'role', $id, $role, null);
        Session::flash('success', 'Role berhasil dihapus.');
        $this->redirect('/roles');
    }

    private function permissionIdMap(): array
    {
        $stmt = Database::connection()->query('SELECT id, slug FROM permissions');
        return array_column($stmt->fetchAll(), 'id', 'slug');
    }
}
