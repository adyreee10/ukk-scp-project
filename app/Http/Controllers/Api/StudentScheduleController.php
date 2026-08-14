<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;

class StudentScheduleController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();
        $schedules = Schedule::with([
            'teacher',
            'schoolClass',
            'room',
        ])
        ->where('class_id', $student->class_id)
        ->orderBy('day_of_week')
        ->orderBy('start_time')
        ->get();

        return response()->json([
            'message' => 'Jadwal siswa berhasil diambil.',
            'data' => $schedules
        ], 200);
    }
}
