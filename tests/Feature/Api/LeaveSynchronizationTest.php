<?php

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Room;
use Illuminate\Http\UploadedFile;

it('synchronizes approved leave to attendance when journal is created', function () {
    $waliKelas = userWithRole('Wali Kelas');
    $guru = userWithRole('Guru');
    $siswa = userWithRole('Siswa');
    
    $kelas = SchoolClass::create(['class_name' => 'X RPL 1']);
    $ruangan = Room::create([
        'room_name' => 'Lab Komputer'
    ]);
        \App\Models\SystemConfiguration::create([
        'id' => 1,
        'active_cycle' => 'Putaran 1',
        'is_schedule_locked' => false,
        'updated_by' => $guru->id,
    ]);

    $siswa->update(['class_id' => $kelas->id]);
    $waliKelas->update(['homeroom_class_id' => $kelas->id]);

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

    $date = now()->startOfWeek()->toDateString();

    $leave = LeaveRequest::create([
        'student_id' => $siswa->id,
        'applicant_id' => $siswa->id,
        'start_date' => $date,
        'end_date' => $date,
        'leave_type' => 'Sakit',
        'reason' => 'Demam',
        'status_approval' => 'Pending',
        'document_path' => 'leaves/dummy.pdf',
    ]);

    \Laravel\Sanctum\Sanctum::actingAs($waliKelas);
    
    expect(Attendance::count())->toBe(0);

    $this->putJson("/api/student-affairs/leave-requests/{$leave->id}/approve")
         ->assertOk();

    expect(Attendance::count())->toBe(0); 

    \Laravel\Sanctum\Sanctum::actingAs($guru);

    $this->post('/api/teacher/journals', [
        'schedule_id' => $schedule->id,
        'date' => $date,
        'topic_description' => 'Materi pengujian',
        'photo' => UploadedFile::fake()->image('kelas.jpg'),
    ], ['Accept' => 'application/json'])->assertCreated();

    expect(Attendance::where([
        'student_id' => $siswa->id,
        'status' => 'Sakit',
    ])->count())->toBe(1);
});
