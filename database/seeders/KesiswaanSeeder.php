<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class KesiswaanSeeder extends Seeder
{
    public function run(): void
    {
        $kesiswaanRole = Role::where('role_name', 'Kesiswaan')->first();

        User::create([
            'role_id' => $kesiswaanRole->id,
            'name' => 'Tim Kesiswaan',
            'username' => 'kesiswaan',
            'email' => 'kesiswaan@sekolah.test',
            'password' => 'kesiswaan123',
        ]);
    }
}
