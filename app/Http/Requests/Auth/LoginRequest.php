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
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Email, Username, NIS, NIP, atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        // Login ke sesi web Laravel
        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Pastikan request tidak melebihi batas percobaan (Rate Limiting).
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Kunci throttle rate-limiting berbasis input login dan IP user.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
