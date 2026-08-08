<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class WakaSeeder extends Seeder
{
    
    public function run(): void
    {
        $wakaRole = Role::where('role_name', 'Waka')->first();

        User::create([
            'role_id' => $wakaRole->id,
            'name' => 'Waka Kurikulum',
            'username' => 'waka',
            'email' => 'waka@sekolah.test',
            'password' => 'waka123',
        ]);
    }
}
