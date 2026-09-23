<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 
                'email', 
                'max:255', 
                Rule::unique('users')->ignore($user->id)
            ],
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users')->ignore($user->id)
            ],
        ]); 

        $user->update($validated);
        return response()->json([
            'message' => 'Profil berhasil diperbarui,',
            'data' => $user->fresh()->load('role', 'schoolClass'),
        ], 200);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password berhasil diperbarui.',
            'data' => $user->fresh()->load('role', 'schoolClass'),
        ], 200);
    }
}
