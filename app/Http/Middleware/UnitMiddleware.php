<?php
// filepath: app/Http/Middleware/UnitMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UnitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // PASTIKAN USER SUDAH LOGIN
        if (!$user) {
            return redirect('/login');
        }

        // PASTIKAN USER MEMILIKI ROLE UNIT
        if (!$user->isUnitUser()) {
            abort(403, 'Unauthorized access. Unit role required.');
        }

        // PASTIKAN USER TERHUBUNG KE UNIT
        if (!$user->unit) {
            abort(403, 'No unit assigned to this user.');
        }

        return $next($request);
    }
}