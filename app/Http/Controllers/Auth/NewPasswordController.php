<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Tampilkan formulir verifikasi OTP dan kata sandi baru.
     */
    public function create(Request $request): View
    {
        $email = session('reset_email', $request->query('email', ''));
        $cooldown = 0;
        if ($email) {
            $throttleKey = 'otp-resend:' . strtolower(trim($email));
            if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
                $cooldown = RateLimiter::availableIn($throttleKey);
            }
        }
        if (session()->has('otp_cooldown')) {
            $cooldown = max($cooldown, (int) session('otp_cooldown'));
        }

        return view('auth.reset-password', compact('email', 'cooldown'));
    }

    /**
     * Validasi kode OTP dan perbarui kata sandi pengguna.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'                 => ['required', 'email', 'exists:users,email'],
            'otp'                   => ['required', 'string', 'digits:6'],
            'password'              => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.required'        => 'Alamat email wajib diisi.',
            'otp.required'          => 'Kode OTP 6 digit wajib dimasukkan.',
            'otp.digits'            => 'Kode OTP harus berupa 6 digit angka.',
            'password.required'     => 'Kata sandi baru wajib diisi.',
            'password.confirmed'    => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $email = strtolower(trim($request->email));
        $otp   = trim($request->otp);

        // 1. Ambil token OTP dari database
        $resetRecord = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$resetRecord) {
            return back()->withInput()->withErrors([
                'otp' => 'Permintaan reset kata sandi tidak ditemukan atau sudah kedaluwarsa. Silakan minta kode OTP baru.',
            ]);
        }

        // 2. Periksa masa kedaluwarsa (5 menit)
        $createdAt = Carbon::parse($resetRecord->created_at);
        if ($createdAt->addMinutes(5)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return back()->withInput()->withErrors([
                'otp' => 'Kode OTP telah kedaluwarsa (hanya berlaku 5 menit). Silakan minta kode baru.',
            ]);
        }

        // 3. Verifikasi kecocokan OTP (baik via Hash atau Plain)
        if (!Hash::check($otp, $resetRecord->token) && $resetRecord->token !== $otp) {
            return back()->withInput()->withErrors([
                'otp' => 'Kode OTP yang Anda masukkan salah. Silakan periksa kembali.',
            ]);
        }

        // 4. Update kata sandi pengguna
        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withInput()->withErrors([
                'email' => 'Pengguna dengan email ini tidak ditemukan.',
            ]);
        }

        $user->forceFill([
            'password'       => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        // 5. Hapus OTP yang sudah digunakan agar tidak bisa dipakai ulang
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        RateLimiter::clear('otp-resend:' . $email);
        session()->forget(['reset_email']);

        event(new PasswordReset($user));

        return redirect()->route('login')
            ->with('status', 'Kata sandi akun Anda berhasil diperbarui! Silakan masuk dengan kata sandi baru.');
    }
}
