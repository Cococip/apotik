<?php

namespace App\Core;

interface Middleware
{
    /**
     * Return true to continue to the next handler, or handle the
     * response/redirect itself and return false to stop propagation.
     */
    public function handle(Request $request): bool;
}
