<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
public function run(): void
{
    foreach ([
        'Waka Kurikulum',
        'Guru',
        'Wali Kelas',
        'Guru BK',
        'Siswa',
        'Orang Tua',
    ] as $roleName){
        Role::updateOrCreate ([
            'role_name' => $roleName
        ]);
    }

    foreach ([
        'X RPL 1',
        'X RPL 2',
        'XI RPL 1',
        'XI RPL 2',
        'XII RPL 1',
        'XII RPL 2',
    ] as $name){
        SchoolClass::updateOrCreate([
            'class_name' => $name
        ]);
    }

    foreach ([
        'Ruang 101',
        'Ruang 102',
        'Lab RPL',
        'Lab TKJ',
        'Lab Multimedia',
    ] as $name){
        Room::updateOrCreate([
            'room_name' => $name
        ]);
    }

    $class = SchoolClass::where('class_name', 'X RPL 1')
        ->firstOrFail();

    $waka = $this->user('waka', [
        'role_id' => $this->role('Waka Kurikulum'),
        'name' => 'Waka Kurikulum',
        'email' => 'waka@sekolah.test',
        'password' => Hash::make('password123'),
    ]);

    $this->user('guru', [
        'role_id' => $this->role('Guru'),
        'name' => 'Guru Demo',
        'email' => 'guru@sekolah.test',
        'password' => Hash::make('password123'),
    ]);

    $this->user('walikelas', [
        'role_id' => $this->role('Wali Kelas'), 'homeroom_class_id' => $class->id,
        'name' => 'Wali Kelas Demo',
        'email' => 'walikelas@sekolah.test',
        'password' => Hash::make('password123'),
    ]);

    $this->user('gurubk', [
        'role_id' => $this->role('Guru BK'),
        'name' => 'Guru BK Demo',
        'email' => 'gurubk@sekolah.test',
        'password' => Hash::make('password123'),
    ]);

    $parent = $this->user('orangtua', [
        'role_id' => $this->role('Orang Tua'),
        'name' => 'Orang Tua Demo',
        'email' => 'orangtua@sekolah.test',
        'password' => Hash::make('password123'),
    ]);

    $this->user('siswa', [
        'role_id' => $this->role('Siswa'), 'class_id' => $class->id, 'parent_id' => $parent->id,
        'name' => 'Siswa Demo',
        'email' => 'siswa@sekolah.test',
        'password' => Hash::make('password123'),
    ]);

    SystemConfiguration::updateOrCreate(
        ['id' => 1],
        [
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
            'updated_by' => $waka->id,
        ],
        );
}

private function role(string $name): int
{
    return Role::where('role_name', $name)
    ->firstOrFail()
    ->id;
}

private function user(string $username, array $attributes): User
{
    return
    User::updateOrCreate(['username' => $username], $attributes);
}
}
