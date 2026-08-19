<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Journal;
use App\Models\Schedule;

class TeacherJournalController extends Controller
{
    // GET semua jurnal milik guru
    public function index(Request $request)
    {
        $teacher = $request->user();

        $journals = Journal::with([
            'schedule.schoolClass',
            'schedule.room'
        ])
        ->whereHas('schedule', function ($query) use ($teacher) {
            $query->where('teacher_id', $teacher->id);
        })
        ->orderByDesc('date')
        ->get();

        return response()->json([
            'message' => 'Jurnal guru berhasil diambil.',
            'data' => $journals,
        ], 200);
    }


    // POST membuat jurnal
    public function store(Request $request)
    {
        $teacher = $request->user();

        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'date' => 'required|date',
            'topic_description' => 'required|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Pastikan jadwal memang milik guru yang login
        $schedule = Schedule::where('id', $request->schedule_id)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (!$schedule) {
            return response()->json([
                'message' => 'Jadwal tidak ditemukan atau bukan milik guru.'
            ], 403);
        }

        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')
                ->store('journals', 'public');
        }

        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'date' => $request->date,
            'topic_description' => $request->topic_description,
            'photo_path' => $photoPath,
        ]);

        $journal->load([
            'schedule.schoolClass',
            'schedule.room'
        ]);

        return response()->json([
            'message' => 'Jurnal berhasil dibuat.',
            'data' => $journal,
        ], 201);
    }


    // GET detail jurnal
    public function show(Request $request, $id)
    {
        $teacher = $request->user();

        $journal = Journal::with([
            'schedule.schoolClass',
            'schedule.room'
        ])
        ->whereHas('schedule', function ($query) use ($teacher) {
            $query->where('teacher_id', $teacher->id);
        })
        ->find($id);

        if (!$journal) {
            return response()->json([
                'message' => 'Jurnal tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail jurnal berhasil diambil.',
            'data' => $journal,
        ], 200);
    }
}