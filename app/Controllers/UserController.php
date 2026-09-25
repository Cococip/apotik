<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;

class UserController extends Controller
{
    public function index(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));

        $result = User::paginate($page, 10, [], $search ?: null, ['username', 'full_name', 'email'], 'full_name', 'ASC');

        $roleIds = array_unique(array_column($result['data'], 'role_id'));
        $roles = Role::all();
        $roleMap = array_column($roles, 'name', 'id');
        foreach ($result['data'] as &$row) {
            $row['role_name'] = $roleMap[$row['role_id']] ?? '-';
        }
        unset($row);

        $this->view('users.index', [
            'title' => 'User',
            'rows' => $result['data'],
            'pagination' => $result,
            'search' => $search,
            'roles' => $roles,
            'routeBase' => '/users',
            'openModal' => flash('open_modal'),
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        $validator = $this->validate($input, [
            'username' => 'required|min:3|max:60',
            'email' => 'required|email|max:150',
            'full_name' => 'required|max:150',
            'role_id' => 'required',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            $this->withOldAndErrors($input, $validator);
            Session::flash('open_modal', 'create');
            $this->redirect('/users');
            return;
        }

        try {
            $id = User::create([
                'username' => $input['username'],
                'email' => $input['email'],
                'password' => password_hash($input['password'], PASSWORD_DEFAULT),
                'full_name' => $input['full_name'],
                'phone' => $input['phone'] ?? null,
                'role_id' => $input['role_id'],
                'status' => $input['status'] ?? 'active',
            ]);
        } catch (\PDOException $e) {
            Session::flash('error', 'Username atau email sudah digunakan.');
            flash_old($input);
            Session::flash('open_modal', 'create');
            $this->redirect('/users');
            return;
        }

        AuditLogger::log('create', 'user', $id, null, ['username' => $input['username'], 'role_id' => $input['role_id']]);
        Session::flash('success', 'Pengguna berhasil ditambahkan.');
        $this->redirect('/users');
    }

    public function update(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        $existing = User::find($id);
        if (!$existing) {
            Session::flash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('/users');
            return;
        }

        $input = $request->all();
        $validator = $this->validate($input, [
            'username' => 'required|min:3|max:60',
            'email' => 'required|email|max:150',
            'full_name' => 'required|max:150',
            'role_id' => 'required',
        ]);

        if ($validator->fails()) {
            $this->withOldAndErrors($input, $validator);
            Session::flash('open_modal', 'edit:' . $id);
            $this->redirect('/users');
            return;
        }

        if ($id === Auth::id() && ($input['status'] ?? 'active') !== 'active') {
            Session::flash('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            $this->redirect('/users');
            return;
        }

        $data = [
            'username' => $input['username'],
            'email' => $input['email'],
            'full_name' => $input['full_name'],
            'phone' => $input['phone'] ?? null,
            'role_id' => $input['role_id'],
            'status' => $input['status'] ?? 'active',
        ];

        if (!empty($input['password'])) {
            $data['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        try {
            User::update($id, $data);
        } catch (\PDOException $e) {
            Session::flash('error', 'Username atau email sudah digunakan pengguna lain.');
            Session::flash('open_modal', 'edit:' . $id);
            $this->redirect('/users');
            return;
        }

        AuditLogger::log('update', 'user', $id, $existing, $data);
        Session::flash('success', 'Pengguna berhasil diperbarui.');
        $this->redirect('/users');
    }

    public function destroy(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        if ($id === Auth::id()) {
            Session::flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            $this->redirect('/users');
            return;
        }

        $existing = User::find($id);
        if ($existing) {
            User::delete($id);
            AuditLogger::log('delete', 'user', $id, $existing, null);
            Session::flash('success', 'Pengguna berhasil dinonaktifkan.');
        }

        $this->redirect('/users');
    }
}
