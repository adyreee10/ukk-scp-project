<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\SchoolClass;

class SiswaSeeder extends Seeder
{

    public function run(): void
    {
          $siswaRole = Role::where('role_name', 'Siswa')->first();
          $parent = User::where('username', 'orangtua')->first();
          $class = SchoolClass::first();

        User::create([
            'role_id' => $siswaRole->id,
            'class_id' => $class->id,
            'parent_id' => $parent->id,
            'name' => 'Siswa Demo',
            'username' => 'siswa',
            'email' => 'siswa@sekolah.test',
            'password' => 'siswa123',
        ]);
    }
}
