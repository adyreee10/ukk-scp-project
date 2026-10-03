<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function syncApprovedLeaves(Journal $journal): void
    {
        $journal->loadMissing('schedule');
        $date = $journal->date->toDateString();

        $studentIds = User::query()
        ->where('class_id', $journal->schedule->class_id)
        ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
        ->pluck('id');

        $leaves = LeaveRequest::query()
        ->approvedOn($date)
        ->whereIn('student_id', $studentIds)
        ->latest('updated_at')
        ->get()
        ->unique('student_id');

        foreach ($leaves as $leave) {
            Attendance::updateOrCreate(
                ['journal_id' => $journal->id, 'student_id' => $leave->student_id],
                ['status' => $leave->leave_type, 'date' => $date],
            );
        }
    }

    public function saveBatch (Journal $journal, array $rows): Collection
    {
        return DB::transaction(function () use ($journal, $rows) {
            $journal->loadMissing('schedule');
            $studentIds = collect($rows)->pluck('student_id');

            $validCount = User::query()
            ->whereIn('id', $studentIds)
            ->where('class_id', $journal->schedule->class_id)
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
            ->count();

            if ($validCount !== $studentIds->unique()->count()) {
                throw ValidationException::withMessages([
                'attendances' => 'Semua student_id harus siswa dari kelas jadwal.',
            ]);
            }

            $date = $journal->date->toDateString();
            $approved = LeaveRequest::query()
            ->approvedOn($date)
            ->whereIn('student_id', $studentIds)
            ->latest('updated_at')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');

            foreach($rows as $row) {
            $forcedStatus = $approved->get($row['student_id'])?->leave_type;

            Attendance::updateOrCreate(
            ['journal_id' => $journal->id, 'student_id' => $row['student_id']],
            ['status' => $forcedStatus ?? $row['status'], 'date' => $date], 
            );
            }
        
            return $journal->attendances()->with('student:id,name')->get();
        });
    }
}
