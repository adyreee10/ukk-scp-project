<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\User;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $journal = Journal::first();
        $students = User::where('username', 'siswa')->first();

        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $students->id,
            'status' => 'Hadir',
            'date' => $journal->date,
        ]);
    }
}
