<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuruSeeder extends Seeder
{
    public function run(): void
    {
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);

        // Akun Demo Bu Dinda
        User::updateOrCreate(
            ['nip' => '3403036308970002'],
            [
                'role_id'  => $guruRole->id,
                'name'     => 'DINDA AYU DEWANTI, S.Kom',
                'username' => 'guru_dinda',
                'email'    => 'guru_dinda@guru.smk.sch.id',
                'password' => Hash::make('password123'),
            ]
        );

        // Akun Demo Bu Mulati
        User::updateOrCreate(
            ['nip' => '196709222007012012'],
            [
                'role_id'  => $guruRole->id,
                'name'     => 'MULATI, S.Pd.',
                'username' => 'guru_mulati',
                'email'    => 'guru_mulati@guru.smk.sch.id',
                'password' => Hash::make('password123'),
            ]
        );

        // Akun Generic Cadangan
        User::updateOrCreate(
            ['username' => 'guru'],
            [
                'role_id'  => $guruRole->id,
                'name'     => 'Guru Demo',
                'nip'      => 'guru',
                'email'    => 'guru@sekolah.test',
                'password' => Hash::make('password123'),
            ]
        );
    }
}
