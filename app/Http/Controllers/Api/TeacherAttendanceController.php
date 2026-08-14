<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;

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
}
