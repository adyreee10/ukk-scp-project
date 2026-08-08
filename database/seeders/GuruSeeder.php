<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;


class GuruSeeder extends Seeder
{
    public function run(): void
    {
        $guruRole = Role::where('role_name', 'Guru')->first();

        User::create([
            'role_id' => $guruRole->id,
            'name' => 'Guru Demo',
            'username' => 'guru',
            'email' => 'guru@sekolah.test',
            'password' => 'guru123',
        ]);
    }
}
