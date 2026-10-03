<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Journal;
use App\Services\AttendanceService;
use Illuminate\Validation\Rule;

class TeacherAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $request->user();

        $attendances = Attendance::with([
            'student',
            'journal.schedule.schoolClass',
            'journal.schedule.room',
        ])
        ->whereHas('journal.schedule', function ($query) use ($teacher) {
            $query->where('teacher_id', $teacher->id);
        })
    
        ->orderByDesc('date')
        ->get();

        return response()->json([
            'message' => 'Data absensi berhasil diambil.',
            'data' => $attendances,
        ], 200);
    }

    public function store(Request $request, AttendanceService $service)
    {
        $validated = $request->validate([
            'journal_id' => ['required', 'exists:journals,id'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'attendances.*.status' => ['required', Rule::in(Attendance::STATUSES)],
        ]);

        $journal = Journal::whereHas('schedule', fn ($query) => $query->where('teacher_id', $request->user()->id)
        )->findOrFail($validated['journal_id']);

        $data = $service->saveBatch($journal, $validated['attendances']);

        return response()->json([
            'message' => 'Presensi berhasil disimpan.',
            'data' => $data,
        ]);
    }
}
