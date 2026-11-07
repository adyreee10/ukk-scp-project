<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Persiapkan data sebelum validasi.
     * Menjaga kompatibilitas jika ada input lama bernama 'email'.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email') && ! $this->has('login')) {
            $this->merge(['login' => $this->email]);
        }
    }

    /**
     * Aturan validasi login.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Pesan validasi kustom.
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Email, Username, NIS, atau NIP wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }

    /**
     * Autentikasi multi-identifier:
     * Mencocokkan input dengan Email, Username, NIS (Siswa), atau NIP (Guru/Staff).
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim($this->input('login'));

        // Cari user yang cocok dengan salah satu kolom: email, username, nis, atau nip
        $user = User::where(function ($query) use ($login) {
            $query->where('email', $login)
                  ->orWhere('username', $login)
                  ->orWhere('nis', $login)
                  ->orWhere('nip', $login);
        })->first();

        // Cek apakah user ditemukan dan password cocok
        if (! $user || ! Hash::check($this->input('password'), $user->password)) {
            RateLimiter::hit($this->attemptsKey(), 600);

            $attempts = RateLimiter::attempts($this->attemptsKey());
            if ($attempts >= 5) {
                // Tepat pada kegagalan ke-5: MULAI PENANGGUHAN 60 DETIK PENUH DARI DETIK INI
                RateLimiter::clear($this->attemptsKey());
                RateLimiter::clear($this->lockoutKey());
                RateLimiter::hit($this->lockoutKey(), 60);

                event(new Lockout($this));
                $seconds = RateLimiter::availableIn($this->lockoutKey());
                if ($seconds <= 0) $seconds = 60;

                session()->flash('lockout_seconds', $seconds);

                throw ValidationException::withMessages([
                    'login' => "Anda telah gagal login sebanyak 5 kali. Akun Anda ditangguhkan sementara selama {$seconds} detik demi keamanan server. Silakan coba lagi setelah 1 menit.",
                ]);
            }

            $remaining = 5 - $attempts;
            throw ValidationException::withMessages([
                'login' => "Email, Username, NIS, NIP, atau kata sandi yang Anda masukkan salah. (Sisa percobaan: {$remaining} kali sebelum akun ditangguhkan 1 menit).",
            ]);
        }

        // Login berhasil: bersihkan counter percobaan dan status penangguhan
        RateLimiter::clear($this->attemptsKey());
        RateLimiter::clear($this->lockoutKey());

        // Login ke sesi web Laravel
        Auth::login($user, $this->boolean('remember'));
    }

    /**
     * Pastikan request tidak melebihi batas percobaan (Rate Limiting).
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->lockoutKey(), 1)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->lockoutKey());
        if ($seconds <= 0) $seconds = 1;

        session()->flash('lockout_seconds', $seconds);

        throw ValidationException::withMessages([
            'login' => "Akun Anda sedang ditangguhkan sementara karena 5 kali gagal login. Silakan tunggu {$seconds} detik lagi sebelum mencoba masuk kembali.",
        ]);
    }

    /**
     * Kunci throttle rate-limiting berbasis input login dan IP user.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }

    public function attemptsKey(): string
    {
        return 'login_attempts:' . $this->throttleKey();
    }

    public function lockoutKey(): string
    {
        return 'login_lockout:' . $this->throttleKey();
    }
}
