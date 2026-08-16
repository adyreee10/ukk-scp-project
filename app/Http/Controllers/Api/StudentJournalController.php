<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Journal;

class StudentJournalController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();
        $journals = Journal::with([
            'schedule.schoolClass',
            'schedule.teacher',
            'schedule.room',
        ])
        ->whereHas('schedule', function ($query) use ($student){
            $query->where('class_id', $student->class_id);
        })
        ->orderByDesc('date')
        ->get();

        return response()->json([
            'message' => 'Jurnal siswa berhasil diambil.',
            'data' => $journals,
        ], 200);
    }
}
