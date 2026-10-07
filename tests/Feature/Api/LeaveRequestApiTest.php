<?php

use App\Models\LeaveRequest;
use App\Models\SchoolClass;

it('allows student to submit a leave request', function () {
    $siswa = actingAsRole('Siswa');
    
    $payload = [
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'leave_type' => 'Izin',
        'reason' => 'Acara keluarga',
        'document' => \Illuminate\Http\UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
    ];

    $this->postJson('/api/leave-requests', $payload)
         ->assertCreated();

    expect(LeaveRequest::where('student_id', $siswa->id)->exists())->toBeTrue();
});

it('prevents student from updating approved leave request', function () {
    $siswa = actingAsRole('Siswa');
    
    $leave = LeaveRequest::create([
        'student_id' => $siswa->id,
        'applicant_id' => $siswa->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'leave_type' => 'Izin',
        'reason' => 'Sakit perut',
        'status_approval' => 'Approved', // SUDAH DISETUJUI
        'document_path' => 'leaves/dummy.pdf',
    ]);

    $this->putJson("/api/leave-requests/{$leave->id}", ['reason' => 'Alasan baru'])
         ->assertStatus(405); // Ditolak karena surat sudah disahkan
});

it('allows Wali Kelas to see pending leave requests in their class', function () {
    $kelas = SchoolClass::create(['class_name' => 'X RPL 1']);
    
    // Siswa di kelas X RPL 1
    $siswa = userWithRole('Siswa', ['class_id' => $kelas->id]);
    
    LeaveRequest::create([
        'student_id' => $siswa->id,
        'applicant_id' => $siswa->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'leave_type' => 'Izin',
        'reason' => 'Acara keluarga',
        'status_approval' => 'Pending',
        'document_path' => 'leaves/dummy.pdf',
    ]);

    // Wali Kelas X RPL 1
    actingAsRole('Wali Kelas', ['homeroom_class_id' => $kelas->id]);

    $this->getJson('/api/student-affairs/leave-requests?status=Pending')
         ->assertOk()
         ->assertJsonCount(1, 'data.data'); // Memastikan struktur paginasi berisi 1 data
});

it('prevents Kesiswaan from approving if status is already rejected', function () {
    $siswa = userWithRole('Siswa');
    
    $leave = LeaveRequest::create([
        'student_id' => $siswa->id,
        'applicant_id' => $siswa->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'leave_type' => 'Izin',
        'reason' => 'Bolos',
        'status_approval' => 'Rejected', // SUDAH DITOLAK
        'document_path' => 'leaves/dummy.pdf',
    ]);

    actingAsRole('Guru BK');

    // Coba di-approve
    $this->putJson("/api/student-affairs/leave-requests/{$leave->id}/approve")
         ->assertStatus(409); // Gagal karena benda yang sudah ditolak tidak boleh disetujui (State Invalid)
});
