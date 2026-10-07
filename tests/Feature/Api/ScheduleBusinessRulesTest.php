<?php

use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;

it ('rejects overlap and accepts touching time boundaries', function () {
    $teacher = actingAsRole('Guru');
    $class = SchoolClass::create ([
        'class_name' => 'X RPL 1'
    ]);

    $room = Room::create([
        'room_name' => 'Lab RPL'
    ]);

    SystemConfiguration::create([
        'id' => 1,
        'active_cycle' => 'Putaran 1',
        'is_schedule_locked' => false,
        'updated_by' => $teacher->id,
    ]);

    $base = [
        'class_id' => $class->id,
        'room_id' => $room->id,
        'subject' => 'Web',
        'day_of_week' => 'Senin',
        'cycle_type' => 'Putaran 1',
    ];

    $this->postJson('/api/teacher/schedules', [
        ...$base,
        'start_time' => '08:00',
        'end_time' => '10:00',
    ])->assertCreated();

    $this->postJson('/api/teacher/schedules', [
        ...$base, 
        'start_time' => '09:00',
        'end_time' => '11:00'
    ])->assertUnprocessable();

    $this->postJson('/api/teacher/schedules', [
        ...$base,
        'start_time' => '10:00',
        'end_time' => '12:00'
    ])->assertCreated();
});

it ('blocks all schedule mutations when locked', function () {
    $teacher = actingAsRole('Guru');
    $class = SchoolClass::create([
        'class_name' => 'XI RPL 1'
    ]);
    $room = Room::create([
        'room_name' => 'Ruang 101'
    ]);
    SystemConfiguration::create([
        'id' => 1,
        'active_cycle' => 'Putaran 1',
        'is_schedule_locked' => true,
        'updated_by' => $teacher->id,
    ]);

$schedule = Schedule::create([
    'teacher_id' => $teacher->id,
    'class_id' => $class->id,
    'room_id' => $room->id,
    'subject' => 'Web',
    'day_of_week' => 'Senin',
    'start_time' => '08:00',
    'end_time' => '10:00',
    'cycle_type' => 'Putaran 1',
]);

$payload = [
    'class_id' => $class->id,
    'room_id' => $room->id,
    'subject' => 'Web Lanjut',
    'day_of_week' => 'Senin',
    'start_time' => '10:00',
    'end_time' => '12:00',
    'cycle_type' => 'Putaran 1',
];

$this->postJson('/api/teacher/schedules', $payload
)->assertForbidden();
$this->putJson("/api/teacher/schedules/{$schedule->id}", $payload
)->assertForbidden();
$this->deleteJson("/api/teacher/schedules/{$schedule->id}"
)->assertForbidden();

});