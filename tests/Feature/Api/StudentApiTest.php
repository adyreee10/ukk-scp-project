<?php

use App\Models\SchoolClass;

it('allows student to fetch their class schedules', function () {
    $kelas = SchoolClass::create(['class_name' => 'X RPL 1']);
    actingAsRole('Siswa', ['class_id' => $kelas->id]);

    $siswa = actingAsRole('Siswa', ['class_id' => $kelas->id]);

    \App\Models\SystemConfiguration::create([
        'id' => 1, 
        'active_cycle' => 'Putaran 1', 
        'is_schedule_locked' => false,
        'updated_by' => $siswa->id // <--- Berikan data siapa yang mengupdate
    ]);
    
    $this->getJson('/api/student/schedules')->assertOk();
});

it('allows student to fetch their attendance records', function () {
    actingAsRole('Siswa');
    $this->getJson('/api/student/attendances')->assertOk();
});

it('allows student to fetch their class journals', function () {
    actingAsRole('Siswa');
    $this->getJson('/api/student/journals')->assertOk();
});

it('prevents student from accessing teacher routes', function () {
    actingAsRole('Siswa');
    $this->getJson('/api/teacher/dashboard')->assertForbidden(); // TES NEGATIF RBAC
});
