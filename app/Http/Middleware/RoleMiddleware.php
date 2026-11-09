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

        // Cek apakah akun adalah siswa yang telah lulus / alumni (akun dinonaktifkan)
        if ($user->student_status === 'Lulus') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Akun Anda telah dinonaktifkan karena telah lulus / menjadi alumni SMK Negeri 1 Bantul.'
                ], 403);
            }
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'login' => 'Akun Anda telah dinonaktifkan karena telah lulus / menjadi alumni SMK Negeri 1 Bantul.'
            ]);
        }

        // Ambil nama role user
        $roleName = $user->role?->role_name;

        // Cek apakah role user diperbolehkan
        if (!$roleName || !in_array($roleName, $roles, true)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke endpoint ini.'
            ], 403);
        }

        return $next($request);
    }
}