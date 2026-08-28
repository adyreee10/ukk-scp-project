<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class GuruBKSeeder extends Seeder
{
    public function run(): void
    {
        $guruBKrole = Role::where('role_name', 'Guru BK')->first();

        User::create([
            'role_id' => $guruBKrole->id,
            'name' => 'Guru BK',
            'username' => 'gurubk',
            'email' => 'gurubk@sekolah.test',
            'password' => 'gurubk123',
        ]);
    }
}
