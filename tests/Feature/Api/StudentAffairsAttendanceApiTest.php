<?php

use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\SystemConfiguration;
use App\Models\Schedule;
use App\Models\Journal;
use App\Models\Attendance;

it('allows Guru BK to view attendance recap for a specific class', function () {
    $bk = actingAsRole('Guru BK');
    
    $kelas = SchoolClass::create(['class_name' => 'XI AKL 1']);
    $ruang = Room::create(['room_name' => 'Lab Akuntansi']);
    $guru = userWithRole('Guru');
    
    $siswa1 = userWithRole('Siswa', ['class_id' => $kelas->id]);

    SystemConfiguration::create(['id' => 1, 'active_cycle' => 'Putaran 1', 'is_schedule_locked' => false, 'updated_by' => $bk->id]);

    $schedule = Schedule::create([
        'teacher_id' => $guru->id,
        'class_id' => $kelas->id,
        'room_id' => $ruang->id,
        'subject' => 'Akuntansi',
        'day_of_week' => 'Rabu',
        'start_time' => '07:00',
        'end_time' => '09:00',
        'cycle_type' => 'Putaran 1',
    ]);

    $journal = Journal::create([
        'schedule_id' => $schedule->id,
        'date' => now()->toDateString(),
        'topic_description' => 'Materi',
        'photo_path' => 'dummy.jpg',
    ]);

    // Data Pancingan
    Attendance::create([
        'journal_id' => $journal->id,
        'student_id' => $siswa1->id,
        'status' => 'Hadir',
        'date' => now()->toDateString()
    ]);

    $this->getJson("/api/student-affairs/attendance-recap?class_id={$kelas->id}")
         ->assertOk()
         ->assertJsonStructure([
             'data' => [ // Karena menggunakan paginate(20), hasil aslinya akan ada di dalam 'data'
                 'data' => [
                     '*' => [
                         'id',
                         'journal_id',
                         'student_id',
                         'status',
                         'date',
                         'student' => [
                             'id',
                             'name',
                             'class_id'
                         ],
                         'journal' => [
                             'schedule' => [
                                 'teacher' => [
                                     'id',
                                     'name'
                                 ]
                             ]
                         ]
                     ]
                 ]
             ],
             'summary'
         ]);
});

it('prevents student from accessing attendance recap', function () {
    actingAsRole('Siswa');
    $this->getJson('/api/student-affairs/attendance-recap')->assertForbidden();
});
