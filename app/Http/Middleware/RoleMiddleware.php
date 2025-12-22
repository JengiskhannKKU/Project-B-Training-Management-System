<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        if (!$user->role) {
            abort(403, 'No role assigned');
        }

        $roleNames = $roles ?: [];

        if (!in_array($user->role->name, $roleNames, true)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
