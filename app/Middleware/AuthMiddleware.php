<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware implements Middleware
{
    public function handle(Request $request): bool
    {
        if (!Auth::check()) {
            if (str_starts_with($request->uri(), '/api/')) {
                Response::error('Anda harus login terlebih dahulu.', 'unauthorized', 401);
                return false;
            }

            Session::flash('error', 'Silakan login terlebih dahulu.');
            Response::redirect('/login');
            return false;
        }

        return true;
    }
}
