<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WakaSeeder extends Seeder
{
    public function run(): void
    {
        $wakaRole = Role::firstOrCreate(['role_name' => 'Waka Kurikulum']);

        User::updateOrCreate(
            ['username' => 'waka'],
            [
                'role_id'  => $wakaRole->id,
                'name'     => 'Waka Kurikulum',
                'nip'      => 'waka',
                'email'    => 'waka@sekolah.test',
                'password' => Hash::make('password123'),
            ]
        );
    }
}
