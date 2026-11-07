<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
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

        $email = strtolower(trim($request->email));
        $throttleKey = 'otp-resend:' . $email;

        // 1. Jeda waktu 1 menit (60 detik) HANYA berlaku jika ini aksi KIRIM ULANG kode OTP (is_resend = 1).
        // Permintaan awal di halaman /forgot-password selalu langsung diproses tanpa jeda waktu.
        if ($request->boolean('is_resend')) {
            if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
                $remainingSeconds = RateLimiter::availableIn($throttleKey);
                return back()->withInput()->withErrors([
                    'email' => "Mohon tunggu {$remainingSeconds} detik lagi sebelum mengirim ulang kode OTP baru.",
                ])->with('otp_cooldown', $remainingSeconds);
            }
        }

        // 2. Buat 6 digit kode OTP acak numerik
        $otp = (string) random_int(100000, 999999);

        // 3. Simpan OTP ke tabel password_reset_tokens dengan masa berlaku 5 menit
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token'      => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        // 4. Pasang jeda waktu 60 detik untuk kirim ulang kode OTP berikutnya
        RateLimiter::clear($throttleKey);
        RateLimiter::hit($throttleKey, 60);

        // 5. Simpan email di session agar form verifikasi langsung terisi
        session([
            'reset_email' => $email,
        ]);

        // 5. Ambil data nama pengguna untuk pesan personal
        $user = User::where('email', $email)->first();
        $userName = $user ? $user->name : 'Pengguna';

        // 6. Kirim email berisi OTP berformat HTML
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
            ->with('status', "Kode OTP 6 digit telah dikirimkan ke email {$email}. (Berlaku selama 5 menit)")
            ->with('otp_cooldown', 60);
    }
}
