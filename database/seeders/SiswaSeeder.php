<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SiswaSeeder extends Seeder
{
    public function run(): void
    {
        $siswaRole   = Role::firstOrCreate(['role_name' => 'Siswa']);
        $parentDemo  = User::where('username', 'ortu_17821')->first();
        $classXiiRpl = SchoolClass::where('class_name', 'XII RPL 2')->first() ?? SchoolClass::first();

        // Akun Demo Siswa (Rezky Aditya - 17821)
        User::updateOrCreate(
            ['nis' => '17821'],
            [
                'role_id'        => $siswaRole->id,
                'class_id'       => $classXiiRpl?->id,
                'parent_id'      => $parentDemo?->id,
                'name'           => 'REZKY ADITYA',
                'nis'            => '17821',
                'student_status' => 'Aktif',
                'email'          => 'adityarizkyy230172@gmail.com',
                'password'       => Hash::make('password123'),
            ]
        );

        // Akun Generic Cadangan
        $parentGeneric = User::where('username', 'orangtua')->first();
        $classXRpl     = SchoolClass::where('class_name', 'X RPL 1')->first() ?? SchoolClass::first();

        User::updateOrCreate(
            ['username' => 'siswa'],
            [
                'role_id'        => $siswaRole->id,
                'class_id'       => $classXRpl?->id,
                'parent_id'      => $parentGeneric?->id,
                'name'           => 'Siswa Demo',
                'nis'            => '12345',
                'student_status' => 'Aktif',
                'email'          => 'siswa@sekolah.test',
                'password'       => Hash::make('password123'),
            ]
        );
    }
}
