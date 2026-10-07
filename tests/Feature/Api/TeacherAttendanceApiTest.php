<?php

use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\SystemConfiguration;
use App\Models\Journal;

it('allows teacher to submit attendance for a journal', function () {
    $guru = actingAsRole('Guru');
    $kelas = SchoolClass::create(['class_name' => 'XII TKJ 1']);
    $ruang = Room::create(['room_name' => 'Lab Jaringan']);
    
    // 2 Siswa untuk diabsen
    $siswa1 = userWithRole('Siswa', ['class_id' => $kelas->id]);
    $siswa2 = userWithRole('Siswa', ['class_id' => $kelas->id]);

    SystemConfiguration::create(['id' => 1, 'active_cycle' => 'Putaran 1', 'is_schedule_locked' => false, 'updated_by' => $guru->id]);

    $schedule = Schedule::create([
        'teacher_id' => $guru->id,
        'class_id' => $kelas->id,
        'room_id' => $ruang->id,
        'subject' => 'Infrastruktur Jaringan',
        'day_of_week' => 'Senin',
        'start_time' => '07:00',
        'end_time' => '09:00',
        'cycle_type' => 'Putaran 1',
    ]);

    $journal = Journal::create([
        'schedule_id' => $schedule->id,
        'date' => now()->toDateString(),
        'topic_description' => 'Materi Router',
        'photo_path' => 'dummy.jpg',
    ]);

    // Uji Submit Presensi (GURU MENGABSEM)
    $this->postJson('/api/teacher/attendances', [
        'journal_id' => $journal->id,
        'attendances' => [
            ['student_id' => $siswa1->id, 'status' => 'Hadir'],
            ['student_id' => $siswa2->id, 'status' => 'Sakit'],
        ]
    ])->assertOk();

    // Memastikan data absensi benar-benar masuk ke Database!
    $this->assertDatabaseHas('attendances', [
        'journal_id' => $journal->id,
        'student_id' => $siswa1->id,
        'status' => 'Hadir',
    ]);

    $this->assertDatabaseHas('attendances', [
        'journal_id' => $journal->id,
        'student_id' => $siswa2->id,
        'status' => 'Sakit',
    ]);
});

it('prevents teacher from submitting attendance for unowned journal', function () {
    $guruPenyusup = actingAsRole('Guru'); // Ini adalah Guru yang login
    $guruPemilik = userWithRole('Guru');  // Ini adalah Guru Pemilik Jurnal
    
    $kelas = SchoolClass::create(['class_name' => 'XII TKJ 1']);
    $ruang = Room::create(['room_name' => 'Lab Jaringan']);
    $siswa = userWithRole('Siswa', ['class_id' => $kelas->id]);

    SystemConfiguration::create(['id' => 1, 'active_cycle' => 'Putaran 1', 'is_schedule_locked' => false, 'updated_by' => $guruPemilik->id]);

    $schedule = Schedule::create([
        'teacher_id' => $guruPemilik->id, // <--- JADWAL MILIK GURU LAIN
        'class_id' => $kelas->id,
        'room_id' => $ruang->id,
        'subject' => 'Infrastruktur',
        'day_of_week' => 'Selasa',
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

    // Guru Penyusup mencoba mengabsen siswa di jurnal milik Guru Pemilik
    $this->postJson('/api/teacher/attendances', [
        'journal_id' => $journal->id,
        'attendances' => [
            ['student_id' => $siswa->id, 'status' => 'Hadir'],
        ]
    ])->assertNotFound(); // Akan dicegat oleh sistem keamanan!
});
