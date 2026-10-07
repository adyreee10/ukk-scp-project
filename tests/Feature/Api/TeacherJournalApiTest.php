<?php

use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;

it('allows teacher to get their journals', function () {
    actingAsRole('Guru');
    
    $this->getJson('/api/teacher/journals')
         ->assertOk();
});

it('prevents teacher from creating journal outside their schedule', function () {
    $guru = actingAsRole('Guru');
    $guruLain = userWithRole('Guru'); // Jadwal ini milik guru lain
    
    $kelas = SchoolClass::create(['class_name' => 'X RPL 1']);
    $ruangan = Room::create(['room_name' => 'Lab Komputer']);
    
    SystemConfiguration::create([
        'id' => 1,
        'active_cycle' => 'Putaran 1',
        'is_schedule_locked' => false,
        'updated_by' => $guru->id,
    ]);

    $schedule = Schedule::create([
        'teacher_id' => $guruLain->id, // <--- MILIK GURU LAIN
        'class_id' => $kelas->id,
        'room_id' => $ruangan->id,
        'subject' => 'Web',
        'day_of_week' => now()->locale('id')->isoFormat('dddd'),
        'start_time' => '08:00',
        'end_time' => '10:00',
        'cycle_type' => 'Putaran 1',
    ]);

    $this->postJson('/api/teacher/journals', [
        'schedule_id' => $schedule->id,
        'date' => now()->startOfWeek()->toDateString(),
        'topic_description' => 'Materi Hack',
        'photo' => \Illuminate\Http\UploadedFile::fake()->image('fake.jpg'),
    ])->assertNotFound(); // Sistem akan pura-pura tidak menemukan jadwal tersebut (RBAC Query Scope)
});
