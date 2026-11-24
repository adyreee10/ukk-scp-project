<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrangTuaSeeder extends Seeder
{
    public function run(): void
    {
        $orangtuaRole = Role::firstOrCreate(['role_name' => 'Orang Tua']);

        // Akun Demo Orang Tua (Wali dari Rezky Aditya - 17821)
        $parentDemo = User::updateOrCreate(
            ['username' => 'ortu_17821'],
            [
                'role_id'  => $orangtuaRole->id,
                'name'     => 'Orang tua REZKY ADITYA',
                'email'    => 'ortu_17821@wali.smk.sch.id',
                'password' => Hash::make('p17821'),
            ]
        );

        // Sinkronkan ke siswa anak jika sudah ada
        $child = User::where('nis', '17821')->first();
        if ($child) {
            $child->update(['parent_id' => $parentDemo->id]);
        }

        // Akun Generic Cadangan
        $parentGeneric = User::updateOrCreate(
            ['username' => 'orangtua'],
            [
                'role_id'  => $orangtuaRole->id,
                'name'     => 'Orang Tua Demo',
                'email'    => 'orangtua@sekolah.test',
                'password' => Hash::make('portu123'),
            ]
        );

        $childGeneric = User::where('username', 'siswa')->first();
        if ($childGeneric) {
            $childGeneric->update(['parent_id' => $parentGeneric->id]);
        }
    }
}
