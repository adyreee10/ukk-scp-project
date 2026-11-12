<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\SystemConfiguration;
use App\Services\ScheduleService;
use App\Models\Schedule;

class TeacherScheduleController extends Controller
{
    public function __construct (private readonly ScheduleService $service)
    {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'cycle' => ['nullable', Rule::in(SystemConfiguration::CYCLES)],
        ]);

        $cycle = $validated ['cycle'] 
        ?? SystemConfiguration::current()->active_cycle;

        $data = Schedule::with([
            'schoolClass:id,class_name', 'room:id,room_name'
        ])
        ->where('teacher_id', $request->user()->id)
        ->where('cycle_type', $cycle)
        ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 END")
        ->orderBy('start_time')
        ->get();

        return response()->json([
            'data' => $data,
            'meta' => [
                'active_cycle' => SystemConfiguration::current()->active_cycle,
                'selected_cycle' => $cycle,
                'is_schedule_locked' => SystemConfiguration::current()->isCycleLocked($cycle),
                'is_cycle_1_locked' => SystemConfiguration::current()->isCycleLocked('Putaran 1'),
                'is_cycle_2_locked' => SystemConfiguration::current()->isCycleLocked('Putaran 2'),
            ],
        ]);
    }

    public function store (Request $request)
    {
        $schedule =
        $this->service->create($request->user(),
        $this->validated($request));
        return response()->json([
            'message' => 'Jadwal berhasil dibuat.',
            'data' => $schedule
        ], 201);
    }

    public function update (Request $request, int $id)
    {
        $schedule =
        $this->owned($request, $id);
        $schedule =
        $this->service->update($schedule,
        $this->validated($request));
        return response()->json([
            'message' => 'Jadwal berhasil diperbarui.',
            'data' => $schedule
        ]);
    }

    public function destroy (Request $request, int $id)
    {
        $this->service->delete(
            $this->owned($request, $id)
        );
        return response()->noContent();
    }

    private function owned (Request $request, int $id): Schedule
    {
        return Schedule::where(
            'teacher_id', $request->user()->id
        )->findOrFail($id);
    }

    private function validated (Request $request): array
    {
        return $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'subject' => ['required', 'string', 'max:100'],
            'day_of_week' => ['required', Rule::in(Schedule::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'cycle_type' => ['required', Rule::in(SystemConfiguration::CYCLES)],
        ]);
    }
}
