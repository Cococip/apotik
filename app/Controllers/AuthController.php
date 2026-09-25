<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditLogger;

class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        $this->view('auth.login', ['title' => 'Masuk'], 'auth');
    }

    public function login(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        $validator = $this->validate(['username' => $username, 'password' => $password], [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $this->withOldAndErrors(['username' => $username], $validator);
            $this->redirect('/login');
            return;
        }

        $result = Auth::attempt($username, $password, $request->ip());

        if (!$result['success']) {
            Session::flash('error', $result['message']);
            flash_old(['username' => $username]);
            $this->redirect('/login');
            return;
        }

        AuditLogger::log('login', 'auth', (int) $result['user']['id']);
        Session::flash('success', 'Selamat datang kembali, ' . $result['user']['full_name'] . '.');
        $this->redirect('/');
    }

    public function logout(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $userId = Auth::id();
        Auth::logout();
        AuditLogger::log('logout', 'auth', $userId);
        $this->redirect('/login');
    }
}
