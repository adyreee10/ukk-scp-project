<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {

        // Cek apakah user sudah login
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Ambil user yang sedang login
        $user = $request->user();

        // Ambil nama role user
        $roleName = $user->role?->role_name;

        // Cek apakah role user diperbolehkan
        if (!$roleName || !in_array($roleName, $roles)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke endpoint ini.'
            ], 403);
        }

        return $next($request);
    }
}