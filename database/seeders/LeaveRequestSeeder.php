<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::where('username', 'siswa')->first();
        $parent = User::where('username', 'orangtua')->first();

        LeaveRequest::create([
            'student_id' => $student->id,
            'applicant_id' => $parent->id,
            'verified_by' => null,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'reason' => 'Siswa sedang sakit dan perlu beristirahat di rumah.',
            'document_path' => 'documents/surat-sakit-siswa.pdf',
            'status_approval' => 'Pending',
        ]);
    }
}
