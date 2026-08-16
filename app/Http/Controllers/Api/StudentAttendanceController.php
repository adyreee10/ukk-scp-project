<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;

class StudentAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();
        $attendances = Attendance::with([
            'journal.schedule.schoolClass',
            'journal.schedule.teacher',
            'journal.schedule.room',
        ])
        ->where('student_id', $student->id)
        ->orderByDesc('date')
        ->get();

        return response()->json([
            'message' => 'Data absensi siswa berhasil diambil.',
            'data' => $attendances,
        ], 200);
    }
}
