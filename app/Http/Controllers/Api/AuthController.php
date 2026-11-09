<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($validated['username']) . '|' . $request->ip());
        $attemptsKey = 'login_attempts:' . $throttleKey;
        $lockoutKey  = 'login_lockout:' . $throttleKey;

        // Cek apakah akun sedang ditangguhkan
        if (RateLimiter::tooManyAttempts($lockoutKey, 1)) {
            $seconds = RateLimiter::availableIn($lockoutKey);
            if ($seconds <= 0) $seconds = 1;
            return response()->json([
                'message' => "Akun Anda sedang ditangguhkan sementara karena 5 kali gagal login. Silakan tunggu {$seconds} detik lagi.",
                'retry_after' => $seconds,
            ], 429);
        }

        $user = User::with('role')
            ->where(function ($query) use ($validated) {
                $query->where('username', $validated['username'])
                      ->orWhere('email', $validated['username'])
                      ->orWhere('nis', $validated['username'])
                      ->orWhere('nip', $validated['username']);
            })
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($attemptsKey, 600);

            $attempts = RateLimiter::attempts($attemptsKey);
            if ($attempts >= 5) {
                // Tepat pada kegagalan ke-5: MULAI PENANGGUHAN 60 DETIK PENUH DARI DETIK INI
                RateLimiter::clear($attemptsKey);
                RateLimiter::clear($lockoutKey);
                RateLimiter::hit($lockoutKey, 60);

                $seconds = RateLimiter::availableIn($lockoutKey);
                if ($seconds <= 0) $seconds = 60;

                return response()->json([
                    'message' => "Anda telah gagal login sebanyak 5 kali. Akun Anda ditangguhkan sementara selama {$seconds} detik demi keamanan server. Silakan coba lagi setelah 1 menit.",
                    'retry_after' => $seconds,
                ], 429);
            }

            $remaining = 5 - $attempts;
            return response()->json([
                'message' => "Username atau password salah. (Sisa percobaan: {$remaining} kali sebelum akun ditangguhkan 1 menit).",
            ], 401);
        }

        // Cek apakah akun adalah siswa yang telah lulus / alumni
        if ($user->student_status === 'Lulus') {
            return response()->json([
                'message' => 'Akun Anda telah dinonaktifkan karena telah dinyatakan Lulus / menjadi Alumni SMK Negeri 1 Bantul.',
            ], 403);
        }

        RateLimiter::clear($attemptsKey);
        RateLimiter::clear($lockoutKey);

            $token = $user
                ->createToken($validated['device_name'] ?? 'api-client'
                );

            return response()->json([
                'message' => 'Login berhasil',
                'data' => [
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'user' => $this->userPayload($user),
                ],
            ], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('role', 'schoolClass', 'parent');

            return response()->json([
                'message' => 'Data user berhasil diambil',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                        'role_id' => $user->role_id,
                        'role' => $user->role?->role_name,
                        'class_id' => $user->class_id,
                        'class' => $user->schoolClass?->class_name,
                        'parent_id' => $user->parent_id,
                        'parent' => $user->parent?->name,
                        'homeroom_class_id' => $user->homeroom_class_id,
                    ]
                ],

            ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
            'data' => null,
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        return response()->json([
            'message' => 'Jika email terdaftar, tautan reset password telah dikirim.',
        ], 200);
    }
    
    public function resetPassword(Request $request)
    {
         $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::broker()->reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token',
            ),
            function ($user, $password){
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET){
            return response()->json([
                'message' => __($status),
                'errors' => [
                    'token' => [__($status)],
                ],
            ], 422);
        }
        
        return response()->json([
            'message' => 'Password berhasil direset.',
            'data' => null,
        ], 200);
    }

    private function userPayload (User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role' => $user->role?->role_name,
            'class_id' => $user->class_id,
            'class' => $user->schoolClass?->class_name,
            'parent_id' => $user->parent_id,
            'parent' => $user->parent?->name,
            'homeroom_class_id' => $user->homeroom_class_id,
        ];
    }

}
