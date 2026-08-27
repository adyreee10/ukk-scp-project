# Fase 04 — Sanctum, Autentikasi, dan Profil API

## Tujuan

Mempertahankan Sanctum yang sudah benar, lalu menutup kekurangan profil, registrasi, dan reset password.

## 1. Jangan instal Sanctum ulang

Jangan menjalankan `composer require laravel/sanctum` atau publish migration lagi. Paket, trait, config, migration token, dan middleware sudah tersedia.

## 2. Hapus registrasi publik API

SRS tidak mewajibkan self-registration. Akun sekolah seharusnya dibuat Waka agar role, kelas, dan parent valid. Hapus route berikut dari `routes/api.php`:

```php
Route::post('/register', [AuthController::class, 'register']);
```

Hapus method `register()` dari API AuthController. Registrasi web Breeze juga sebaiknya dinonaktifkan dengan menghapus route register pada `routes/auth.php` jika aplikasi hanya memakai akun sekolah.

## 3. Perbaiki login

Gunakan validasi nama device, eager load role, dan regenerasi token terkontrol:

```php
public function login(Request $request)
{
    $validated = $request->validate([
        'username' => ['required', 'string'],
        'password' => ['required', 'string'],
        'device_name' => ['nullable', 'string', 'max:100'],
    ]);

    $user = User::with('role')
        ->where('username', $validated['username'])
        ->first();

    if (! $user || ! Hash::check($validated['password'], $user->password)) {
        return response()->json(['message' => 'Username atau password salah.'], 401);
    }

    $token = $user->createToken($validated['device_name'] ?? 'api-client');

    return response()->json([
        'message' => 'Login berhasil.',
        'token' => $token->plainTextToken,
        'token_type' => 'Bearer',
        'user' => $this->userPayload($user),
    ]);
}

private function userPayload(User $user): array
{
    return [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'role' => $user->role?->role_name,
        'class_id' => $user->class_id,
        'homeroom_class_id' => $user->homeroom_class_id,
        'parent_id' => $user->parent_id,
    ];
}
```

Tambahkan throttle pada login di route final Fase 12.

## 4. Logout aman

```php
public function logout(Request $request)
{
    $request->user()->currentAccessToken()?->delete();

    return response()->json(['message' => 'Logout berhasil.']);
}
```

## 5. Forgot password tidak boleh membocorkan token

Ganti method lama yang mengembalikan token dengan:

```php
public function forgotPassword(Request $request)
{
    $validated = $request->validate(['email' => ['required', 'email']]);

    Password::sendResetLink(['email' => $validated['email']]);

    return response()->json([
        'message' => 'Jika email terdaftar, tautan reset password telah dikirim.',
    ]);
}
```

Respons sengaja sama untuk email terdaftar/tidak agar akun tidak mudah ditebak. Untuk demo offline tanpa mail, fitur reset dapat dipresentasikan melalui Mailpit/log mail, bukan mengembalikan token ke client produksi.

## 6. Tambah profil REST

Buat `app/Http/Controllers/Api/ProfileController.php`:

```php
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
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data' => $user->fresh()->load('role', 'schoolClass'),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Password berhasil diperbarui.']);
    }
}
```

Role, kelas, homeroom, dan parent tidak boleh diubah lewat profil. Field tersebut hanya dikelola Waka.

## Gerbang selesai

- [ ] Semua role dapat login menggunakan Bearer token.
- [ ] Endpoint privat menolak request tanpa token.
- [ ] Logout hanya mencabut current token.
- [ ] Profil tidak dapat menaikkan role sendiri.
- [ ] Forgot password tidak membocorkan keberadaan akun atau token.
- [ ] Registrasi publik dinonaktifkan.

Commit:

```bash
git add app/Http/Controllers/Api routes
git commit -m "feat: amankan autentikasi Sanctum dan profil API"
```

