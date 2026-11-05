<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Tampilkan formulir permintaan kode OTP lupa kata sandi.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Buat kode OTP 6 digit, simpan ke database, dan kirimkan ke email pengguna.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email'    => 'Format alamat email tidak valid.',
            'email.exists'   => 'Alamat email ini tidak terdaftar di sistem sekolah.',
        ]);

        $email = $request->email;

        // 1. Buat 6 digit kode OTP acak numerik
        $otp = (string) random_int(100000, 999999);

        // 2. Simpan OTP ke tabel password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token'      => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        // 3. Simpan email di session agar form verifikasi langsung terisi
        session([
            'reset_email' => $email,
        ]);


                // 4. Ambil data nama pengguna untuk pesan personal
        $user = User::where('email', $email)->first();
        $userName = $user ? $user->name : 'Pengguna';

        // 5. Kirim email berisi OTP berformat HTML
        try {
            Mail::send('emails.otp', [
                'otp'      => $otp,
                'userName' => $userName,
                'email'    => $email,
            ], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Kode OTP Reset Kata Sandi Akun SCP');
            });
        } catch (\Throwable $e) {
            // Abaikan kegagalan koneksi jika sedang offline
        }

        return redirect()->route('password.reset')
            ->with('status', "Kode OTP 6 digit telah dikirimkan ke email {$email}.");
    }
}
