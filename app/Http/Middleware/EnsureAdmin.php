<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user && method_exists($user, 'isAdmin') && $user->isAdmin(), 403, 'Admins only.');
        return $next($request);
    }
}
