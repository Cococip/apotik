<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Enforces a permission slug server-side (§25 — backend must validate,
 * not just hide the menu item). Usage in routes:
 *   [PermissionMiddleware::class, 'medicine.view']
 */
class PermissionMiddleware implements Middleware
{
    private string $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

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

        if (!Auth::can($this->permission)) {
            if (str_starts_with($request->uri(), '/api/')) {
                Response::error('Anda tidak memiliki akses untuk aksi ini.', 'forbidden', 403);
                return false;
            }
            Response::forbidden();
            return false;
        }

        return true;
    }
}
