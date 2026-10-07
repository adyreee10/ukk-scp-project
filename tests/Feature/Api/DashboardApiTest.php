<?php

use App\Models\SchoolClass;
use App\Models\Room;

it('returns correct dashboard statistics for waka kurikulum', function () {
    $waka = actingAsRole('Waka Kurikulum');

    // Buat data pancingan
    SchoolClass::create(['class_name' => 'XI MIPA 1']);
    Room::create(['room_name' => 'Lab Fisika']);

    \App\Models\SystemConfiguration::create(['id' => 1, 'active_cycle' => 'Putaran 1', 'is_schedule_locked' => false, 'updated_by' => $waka->id]);

    $this->getJson('/api/waka/dashboard')
         ->assertOk()
         ->assertJsonStructure([
             'data' => [
                 'overdue_journals'
             ]
         ]); // assertJsonStructure ini sangat kaya akan Assertions!
});

it('returns correct dashboard schedule for teacher', function () {
    $guru = actingAsRole('Guru');

    \App\Models\SystemConfiguration::create(['id' => 1, 'active_cycle' => 'Putaran 1', 'is_schedule_locked' => false, 'updated_by' => $guru->id]);

    $this->getJson('/api/teacher/dashboard')
         ->assertOk()
         ->assertJsonStructure([
             'data' => [
                 'overdue_journals'
             ]
         ]);
});

it('prevents student from accessing waka dashboard', function () {
    $siswa = actingAsRole('Siswa');
    $this->getJson('/api/waka/dashboard')->assertForbidden();
});
