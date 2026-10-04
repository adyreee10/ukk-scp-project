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
        $base = Attendance::where('student_id', $student->id);

        $summary = (clone $base)
        ->selectRaw('status, COUNT(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status');

        $data = (clone $base)
        ->with('journal.schedule.teacher:id,name', 'journal.schedule.schoolClass:id,class_name')
        ->latest('date')
        ->paginate(20);

        return response()->json([
            'message' => 'Data absensi siswa berhasil diambil.',
            'summary' => $summary,
            'data' => $data,
        ], 200);
    }
}
