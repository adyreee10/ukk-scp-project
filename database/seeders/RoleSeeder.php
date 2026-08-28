<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::insert([
            ['role_name' => 'Waka Kurikulum'],
            ['role_name' => 'Guru'],
            ['role_name' => 'Wali Kelas'],
            ['role_name' => 'Guru BK'],
            ['role_name' => 'Siswa'],
            ['role_name' => 'Orang Tua'],
        ]);
    }
}
