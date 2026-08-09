<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Journal;
use App\Models\Schedule;

class TeacherJournalController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $request->user();
        $journals = Journal::with([
            'schedule.schoolClass',
            'schedule.room'
        ])
        ->whereHas('schedule', function($query) use($teacher){
            $query->where('teacher_id', $teacher->id);
        })
        ->orderByDesc('date')
        ->get();

        return response()->json([
            'message' => 'Jurnal guru berhasil diambil.',
            'data' => $journals,
        ], 200);
    }
}
