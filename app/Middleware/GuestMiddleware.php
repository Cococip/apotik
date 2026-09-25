<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

class GuestMiddleware implements Middleware
{
    public function handle(Request $request): bool
    {
        if (Auth::check()) {
            Response::redirect('/');
            return false;
        }

        return true;
    }
}
