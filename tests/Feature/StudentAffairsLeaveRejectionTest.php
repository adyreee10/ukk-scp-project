<?php

use App\Models\LeaveRequest;
use App\Models\User;

it('requires rejection_note when rejecting leave request via web', function () {
    $bk = userWithRole('Guru BK');
    $siswa = userWithRole('Siswa');

    $leave = LeaveRequest::create([
        'student_id' => $siswa->id,
        'applicant_id' => $siswa->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'leave_type' => 'Izin',
        'reason' => 'Keperluan keluarga',
        'document_path' => 'dummy.pdf',
        'status_approval' => 'Pending',
    ]);

    $this->actingAs($bk)
         ->post(route('student-affairs.leaves.reject', $leave->id), [
             'rejection_note' => '',
         ])
         ->assertSessionHasErrors(['rejection_note']);

    expect($leave->fresh()->status_approval)->toBe('Pending');
});

it('successfully rejects leave request with rejection_note and records verifier', function () {
    $bk = userWithRole('Guru BK');
    $siswa = userWithRole('Siswa');

    $leave = LeaveRequest::create([
        'student_id' => $siswa->id,
        'applicant_id' => $siswa->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'leave_type' => 'Sakit',
        'reason' => 'Demam tinggi',
        'document_path' => 'dummy.pdf',
        'status_approval' => 'Pending',
    ]);

    $note = 'Surat keterangan dokter tidak terbaca dengan jelas.';

    $this->actingAs($bk)
         ->post(route('student-affairs.leaves.reject', $leave->id), [
             'rejection_note' => $note,
         ])
         ->assertSessionHas('success')
         ->assertRedirect();

    $updated = $leave->fresh();
    expect($updated->status_approval)->toBe('Rejected');
    expect($updated->rejection_note)->toBe($note);
    expect($updated->verified_by)->toBe($bk->id);
});
