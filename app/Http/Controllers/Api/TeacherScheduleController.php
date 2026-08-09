<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;

class TeacherScheduleController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $request->user();
        $schedules = Schedule::with([
            'schoolClass',
            'room'
        ])
        ->where('teacher_id', $teacher->id)
        ->orderBy('day_of_week')
        ->orderBy('start_time')
        ->get();

        return response()->json([
            'message' => 'Jadwal guru berhasil diambil.',
            'data' => $schedules,
        ], 200);
    }
}
