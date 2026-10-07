<?php

use Illuminate\Support\Facades\Hash;

it('fetches the authenticated user profile', function () {
    $user = actingAsRole('Guru', ['name' => 'Budi Guru']);

    $this->getJson('/api/me')
         ->assertOk()
         ->assertJsonPath('data.user.name', 'Budi Guru')
         ->assertJsonPath('data.user.role', 'Guru');
});

it('updates user profile data', function () {
    $user = actingAsRole('Siswa', ['name' => 'Siswa Lama']);

    $this->putJson('/api/profile', [
        'name' => 'Siswa Baru',
        'email' => $user->email,
        'username' => $user->username,
    ])->assertOk();

    expect($user->fresh()->name)->toBe('Siswa Baru');
});

it('updates user password correctly', function () {
    $user = actingAsRole('Guru', ['password' => Hash::make('password_lama')]);

    $this->putJson('/api/profile/password', [
        'current_password' => 'password_lama',
        'password' => 'password_baru',
        'password_confirmation' => 'password_baru'
    ])->assertOk();

    expect(Hash::check('password_baru', $user->fresh()->password))->toBeTrue();
});

it('logs out the user and revokes token', function () {
    $user = userWithRole('Guru');
    // Beri user satu token aktif
    $token = $user->createToken('test')->plainTextToken;

    // Logout menggunakan token tersebut
    $this->withHeaders(['Authorization' => 'Bearer ' . $token])
         ->postJson('/api/logout')
         ->assertOk();

    // Pastikan token benar-benar hancur dari database
    expect($user->tokens()->count())->toBe(0);
});
