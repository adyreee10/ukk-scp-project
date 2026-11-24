<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuruBKSeeder extends Seeder
{
    public function run(): void
    {
        $guruBKRole = Role::firstOrCreate(['role_name' => 'Guru BK']);

        // Akun Demo Bu Rya Widyaningtyas
        $guruBk = User::updateOrCreate(
            ['nip' => '199503272022212001'],
            [
                'role_id'  => $guruBKRole->id,
                'name'     => 'RYAWIDYANINGTYAS, S.Pd.',
                'username' => 'guru_ryawidyaningtyas',
                'email'    => 'guru_ryawidyaningtyas@guru.smk.sch.id',
                'password' => Hash::make('password123'),
            ]
        );

        // Hubungkan ke seluruh rombel kelas bimbingan
        $classes = SchoolClass::all();
        if ($classes->isNotEmpty()) {
            $guruBk->counselorClasses()->sync($classes->pluck('id'));
        }

        // Akun Generic Cadangan
        $genericBk = User::updateOrCreate(
            ['username' => 'gurubk'],
            [
                'role_id'  => $guruBKRole->id,
                'name'     => 'Guru BK Demo',
                'nip'      => 'gurubk',
                'email'    => 'gurubk@sekolah.test',
                'password' => Hash::make('password123'),
            ]
        );
        if ($classes->isNotEmpty()) {
            $genericBk->counselorClasses()->sync($classes->pluck('id'));
        }
    }
}
