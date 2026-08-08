<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class OrangTuaSeeder extends Seeder
{
    public function run(): void
    {
         $orangtuaRole = Role::where('role_name', 'Orang Tua')->first();

        User::create([
            'role_id' => $orangtuaRole->id,
            'name' => 'Orang Tua Demo',
            'username' => 'orangtua',
            'email' => 'orangtua@sekolah.test',
            'password' => 'orangtua123',
        ]);
    }
}
