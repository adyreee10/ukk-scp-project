<?php

use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use Illuminate\Support\Carbon;

it('hides reminder before 30 minutes and shows it after 30 minutes', function () {
    $guru = userWithRole('Guru');
    $kelas = SchoolClass::create(['class_name' => 'X RPL 1']);
    $ruangan = Room::create(['room_name' => 'Lab Komputer']);
    
    SystemConfiguration::create([
        'id' => 1,
        'active_cycle' => 'Putaran 1',
        'is_schedule_locked' => false,
        'updated_by' => $guru->id,
    ]);

    $schedule = Schedule::create([
        'teacher_id' => $guru->id,
        'class_id' => $kelas->id,
        'room_id' => $ruangan->id,
        'subject' => 'Web',
        'day_of_week' => 'Senin',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'cycle_type' => 'Putaran 1',
    ]);

    \Laravel\Sanctum\Sanctum::actingAs($guru);

    // Skenario 1: Waktu TEPAT 30 menit setelah jam 10:00
    Carbon::setTestNow('2026-08-24 10:30:00'); // Senin
    
    $response = $this->getJson('/api/teacher/dashboard');
    $response->assertOk();
    // Harusnya reminder BELUM muncul karena belum lewat 30 menit
    expect($response->json('data.overdue_journals'))->toBeEmpty();

    // Skenario 2: Waktu LEWAT 1 menit (10:31:00)
    Carbon::setTestNow('2026-08-24 10:31:00'); // Senin

    $response2 = $this->getJson('/api/teacher/dashboard');
    $response2->assertOk();
    // Harusnya reminder MUNCUL (1 buah) karena sudah lebih dari 30 menit
    expect($response2->json('data.overdue_journals'))->toHaveCount(1);
    
    // Kembalikan waktu ke normal
    Carbon::setTestNow();
});
