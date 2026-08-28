<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Journal;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;


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
            'date' => 'required|date_format:Y-m-d',
            'topic_description' => 'required|string',
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
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

        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $journalDate = Carbon::createFromFormat (
            'Y-m-d',
            $request->date,
            config('app.timezone')
        );

        if ($dayNames[$journalDate->dayOfWeekIso] !== $schedule->day_of_week){
            return response()->json([
                'message' => 'Tanggal jurnal tidak sesuai dengan hari jadwal.',
                'errors' => [
                    'date' => [
                        'Tanggal jurnal harus memiliki hari yang sama dengan jadwal.',
                    ],
                ],
            ], 422);
        }

        $journalAlreadyExists = Journal::where(
            'schedule_id',
            $schedule->id
        )
            ->whereDate('date', $request->date)
            ->exists();
        if ($journalAlreadyExists){
            return response()->json([
                'message' => 'Jurnal untuk jadwal dan tanggal tersebut sudah tersedia.',
                'errors' => [
                    'journal' => [
                        'Jurnal yang sama tidak dapat dibuat lebih dari satu kali.',
                    ],
                ],
            ], 409);
        }

        $photoPath = $request->file('photo')
            ->store('journals', 'public');

        try {
            $journal = DB::transaction(function () use (
                $schedule,
                $request,
                $photoPath
            ){
                return Journal::create([
                    'schedule_id' => $schedule->id,
                    'date' => $request->date,
                    'topic_description' => $request->topic_description,
                    'photo_path' => $photoPath,
                ]);
            });
        } catch (Throwable $exception){
            Storage::disk('public')->delete($photoPath);
            throw $exception;
        }

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