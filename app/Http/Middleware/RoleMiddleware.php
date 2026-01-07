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

        // Admin inheritance: if 'trainer' is allowed, also allow 'admin'
        $allowedRoles = $roleNames;
        if (in_array('trainer', $roleNames, true) && !in_array('admin', $roleNames, true)) {
            $allowedRoles[] = 'admin';
        }

        if (!in_array($user->role->name, $allowedRoles, true)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
