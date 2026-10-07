<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('issues a sanctum token for valid credentials', function () {
    $user = userWithRole('Guru', [
        'username' => 'guru.test',
        'password' => Hash::make('password123'),
    ]);

    $this->postJson('/api/login', [
        'username' => 'guru.test',
        'password' => 'password123',
        'device_name' => 'pest',
    ])->assertOk()
    ->assertJsonStructure([
    'data' => [
        'token', 'token_type', 'user'
        ]
    ]);


    expect($user->tokens()->count())->toBe(1);
});

it('rejects unauthenticated and wrong-role access', function () {
    $this->getJson('/api/teacher/schedules')
    ->assertUnauthorized();

    actingAsRole('Siswa');
    $this->getJson('/api/teacher/schedules')
    ->assertForbidden();
});