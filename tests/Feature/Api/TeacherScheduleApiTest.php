<?php

use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\SystemConfiguration;

it('allows teacher to manage their own schedules (CRUD)', function () {
    $guru = actingAsRole('Guru');
    $kelas = SchoolClass::create(['class_name' => 'XII IPA 1']);
    $ruang = Room::create(['room_name' => 'Lab Biologi']);
    
    SystemConfiguration::create(['id' => 1, 'active_cycle' => 'Putaran 1', 'is_schedule_locked' => false, 'updated_by' => $guru->id]);

    // 1. UJI CREATE
    $response = $this->postJson('/api/teacher/schedules', [
        'class_id' => $kelas->id,
        'room_id' => $ruang->id,
        'subject' => 'Biologi',
        'day_of_week' => 'Senin',
        'start_time' => '07:00',
        'end_time' => '09:00',
        'cycle_type' => 'Putaran 1',
    ])->assertCreated();

    $scheduleId = $response->json('data.id');
    expect($scheduleId)->not->toBeNull();

    // 2. UJI UPDATE
    $this->putJson("/api/teacher/schedules/{$scheduleId}", [
        'subject' => 'Fisika Quantum',
        'start_time' => '07:00',
        'end_time' => '09:00',
        'day_of_week' => 'Senin',
        'cycle_type' => 'Putaran 1',
        'class_id' => $kelas->id,
        'room_id' => $ruang->id,
    ])->assertOk();

    expect(Schedule::find($scheduleId)->subject)->toBe('Fisika Quantum');

    // 3. UJI DELETE
    $this->deleteJson("/api/teacher/schedules/{$scheduleId}")->assertNoContent(); // <--- Pakai assertNoContent
    
    expect(Schedule::find($scheduleId))->toBeNull(); // Pastikan benar-benar terhapus
});
