<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Check if user has a role assigned
        if (!$user->role) {
            abort(403, 'No role assigned');
        }

        if ($user->role->name !== $role) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
