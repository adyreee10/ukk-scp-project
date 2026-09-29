<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\SystemConfiguration;

class StudentScheduleController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();

        if ($student->class_id === null)
            {
                return response()->json([
                    'message' => 'Akun siswa belum ditempatkan ke kelas.',
                ], 422);
            }
            
        $schedules = Schedule::with([
            'teacher',
            'schoolClass',
            'room',
        ])
        ->where('class_id', $student->class_id)
        ->where('cycle_type', SystemConfiguration::current()->active_cycle)
        ->orderBy('day_of_week')
        ->orderBy('start_time')
        ->get();

        return response()->json([
            'message' => 'Jadwal siswa berhasil diambil.',
            'data' => $schedules
        ], 200);
    }
}
