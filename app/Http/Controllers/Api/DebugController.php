<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebugController
{
    public function auth(Request $request)
    {
        $data = [
            'authenticated' => Auth::check(),
            'user' => Auth::user() ? [
                'id' => Auth::user()->id,
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
                'role' => Auth::user()->role?->name ?? Auth::user()->role,
            ] : null,
            'guards' => [
                'web' => Auth::guard('web')->check(),
                'sanctum' => Auth::guard('sanctum')->check(),
                'default' => Auth::getDefaultDriver(),
            ],
            'session' => [
                'id' => session()->getId(),
                'exists' => session()->exists(),
            ],
            'cookies' => [
                'laravel_session' => isset($_COOKIE['laravel_session']) ? 'present' : 'missing',
                'XSRF-TOKEN' => isset($_COOKIE['XSRF-TOKEN']) ? 'present' : 'missing',
            ],
            'headers' => [
                'authorization' => $request->header('Authorization') ? 'present' : 'missing',
                'referer' => $request->header('Referer'),
                'origin' => $request->header('Origin'),
            ],
            'user_role' => Auth::user()?->getRoleNames()->toArray() ?? [],
        ];

        return response()->json($data);
    }

    public function adminCheck(Request $request)
    {
        $user = Auth::user();

        $roleInfo = null;
        if ($user) {
            $roles = $user->roles;
            $roleInfo = [
                'count' => $roles->count(),
                'roles' => $roles->pluck('name')->toArray(),
                'has_admin_via_hasRole' => $user->hasRole('admin'),
                'has_admin_via_roles' => $user->roles->contains('name', 'admin'),
            ];
        }

        return response()->json([
            'authenticated' => Auth::check(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'role_info' => $roleInfo,
        ]);
    }

    public function middlewareInfo(Request $request)
    {
        return response()->json([
            'route' => $request->route() ? $request->route()->getName() : null,
            'middleware' => $request->route() ? $request->route()->gatherMiddleware() : [],
            'uri' => $request->getUri(),
            'method' => $request->method(),
            'user' => Auth::user() ? [
                'id' => Auth::user()->id,
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
            ] : null,
            'authenticated' => Auth::check(),
        ]);
    }
}
