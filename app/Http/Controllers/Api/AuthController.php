<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        //Validasi Input
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        //Cari user berdasarkan username
        $user = User::with('role')->where('username', $request->username)->first();

        //username atau password salah
        if (!$user || !Hash::check($request->password, $user->password)) 
            {
                return response()->json([
                    'message' => 'Username atau password salah.'
                ], 401);
            }

            //Buat token sanctum
            $token = $user->createToken('mobile-app')->plainTextToken;
            
            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'role' => $user->role?->role_name,
                    'class_id' => $user->class_id,
                    'parent_id' => $user->parent_id,
                ],
            ], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('role', 'schoolClass', 'parent');

            return response()->json([
                'message' => 'Data user berhasil diambil',
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
                ],
            ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.'
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:225',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:225|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $studentRole = \App\Models\Role::where('role_name', 'Siswa')->first();

        if (!$studentRole){
            return response()->json([
                'message' => 'Role siswa belum tersedia.'
            ], 500);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $studentRole->id,
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;
        
        return response()->json([
            'message' => 'Registrasi berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role' => $studentRole->role_name,
                'class_id' => $user->class_id,
                'parent_id' => $user->parent_id,
            ],
        ], 201); 
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user){
            return response()->json([
                'message' => 'Email tidak ditemukan.'
            ], 404);
        }
    

    $token = Password::broker()->createToken($user);

    return response()->json([
        'message' => 'Reset password berhasil dibuat.',
        'email' => $user->email,
        'token' => $token,
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
            ], 400);
        }
        
        return response()->json([
            'message' => 'Password berhasil direset.',
        ], 200);
}
}
