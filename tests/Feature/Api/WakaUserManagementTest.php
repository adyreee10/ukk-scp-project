<?php

use App\Models\User;
use App\Models\Role;

it('allows waka to fetch all users', function () {
    actingAsRole('Waka Kurikulum');
    
    $this->getJson('/api/waka/users')
         ->assertOk()
         ->assertJsonStructure(['data' => ['data']]);
});

it('allows waka to create a new teacher', function () {
    actingAsRole('Waka Kurikulum');
    $role = Role::firstOrCreate(['role_name' => 'Guru']);

    $this->postJson('/api/waka/users', [
        'name' => 'Guru Baru',
        'username' => 'guru.baru',
        'email' => 'guru@sekolah.com',
        'password' => 'password123',
        'role_id' => $role->id,
        'class_id' => null,
        'homeroom_class_id' => null,
        'parent_id' => null,
    ])->assertCreated();

    expect(User::where('username', 'guru.baru')->exists())->toBeTrue();
});
