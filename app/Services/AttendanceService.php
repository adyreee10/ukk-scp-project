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
        ->where('student_status', 'Aktif')
        ->pluck('id');

        $leaves = LeaveRequest::query()
        ->approvedOn($date)
        ->whereIn('student_id', $studentIds)
        ->latest('updated_at')
        ->get()
        ->unique('student_id');

        if ($leaves->isEmpty()) {
            return;
        }

        $now = now();
        $records = [];
        foreach ($leaves as $leave) {
            $records[] = [
                'journal_id' => $journal->id,
                'student_id' => $leave->student_id,
                'status'     => $leave->leave_type,
                'note'       => null,
                'date'       => $date,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Attendance::upsert(
            $records,
            ['journal_id', 'student_id'],
            ['status', 'date', 'updated_at']
        );
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
            ->where('student_status', 'Aktif')
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

            $now = now();
            $records = [];
            foreach($rows as $row) {
                $forcedStatus = $approved->get($row['student_id'])?->leave_type;

                $records[] = [
                    'journal_id' => $journal->id,
                    'student_id' => (int) $row['student_id'],
                    'status'     => $forcedStatus ?? $row['status'],
                    'note'       => !empty($row['note']) ? trim($row['note']) : null,
                    'date'       => $date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($records)) {
                Attendance::upsert(
                    $records,
                    ['journal_id', 'student_id'],
                    ['status', 'note', 'date', 'updated_at']
                );
            }

            return $journal->attendances()->with('student:id,name')->get();
        });
    }
}
