<?php

namespace App\Http\Controllers\Api;

use App\Models\SystemConfiguration;
use App\Support\IndonesianDay;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Journal;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Validation\ValidationException;
use App\Services\AttendanceService;

class TeacherJournalController extends Controller
{
    public function __construct (private readonly AttendanceService $attendanceService) {}

    // GET semua jurnal milik guru
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $teacher = $request->user();

        $journals = Journal::with([
            'schedule.schoolClass',
            'schedule.room'
        ])
        ->whereHas('schedule', function ($query) use ($teacher) {
            $query->where('teacher_id', $teacher->id);
        })
        ->when($request->date_from, function ($q, $date)
        {
            $q->whereDate('date', '>=', $date);
        })
        ->when($request->date_to, function ($q, $date)
        {
            $q->whereDate('date', '<=', $date);
        })
        ->orderByDesc('date')
        ->paginate(15);

        return response()->json([
            'message' => 'Jurnal guru berhasil diambil.',
            'data' => $journals,
        ], 200);
    }


    // POST membuat jurnal
    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'topic_description' => ['required', 'string', 'max:5000'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Pastikan jadwal memang milik guru yang login
        $schedule = Schedule::where('teacher_id', $request->user()->id)
            ->findOrFail($validated['schedule_id']);

        $date = Carbon::createFromFormat ('Y-m-d', $validated['date'])->startOfDay();
        $configuration = SystemConfiguration::current();

        if ($schedule->cycle_type !== $configuration->active_cycle) 
        {
            throw ValidationException::withMessages([
                'schedule_id' => 'Jadwal bukan bagian dari putaran yang sedang aktif.'
            ]);
        }

        if (IndonesianDay::from($date) !== $schedule->day_of_week)
        {
            throw ValidationException::withMessages([
                'date' => 'Hari pada tanggal jurnal tidak sesuai dengan hari jadwal.',
            ]);
        }

        if (Journal::where('schedule_id', $schedule->id)->whereDate('date', $date)->exists())
        {
            return response()->json([
                'message' => 'Jurnal untuk jadwal dan tanggal ini sudah ada.'
            ], 409);
        }

        $photoPath = $request->file('photo')->store('journals', 'public');

        try {
            $journal = DB::transaction(function () use ($schedule, $date, $validated, $photoPath)
            {
                Schedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();

                if (Journal::where('schedule_id', $schedule->id)
                    ->whereDate('date', $date)
                    ->exists()) {
                        abort(409,
                        'Jurnal untuk jadwal dan tanggal ini sudah ada.');
                    }

                    $journal = Journal::create([
                        'schedule_id' => $schedule->id,
                        'date' => $date->toDateString(),
                        'topic_description' => $validated ['topic_description'],
                        'photo_path' => $photoPath,
                    ]);

                    $this->attendanceService->syncApprovedLeaves($journal);

                    return $journal;
            });
        }

        catch (Throwable $exception) 
        {
            Storage::disk('public')->delete($photoPath);
            throw $exception;
        }

        return response()->json([
            'message' => 'Jurnal berhasil dibuat.',
            'data' => $journal->load('schedule.schoolClass', 'schedule.room'),
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