<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class ParentAttendanceController extends Controller
{
    public function index (Request $request, int $student)
    {
        $child = $request->user()->children()
        ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
        ->findOrFail($student);

    $validated = $request->validate([
        'date_from' => ['nullable', 'date_format:Y-m-d'],
        'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
    ]);

    $base = Attendance::query()
    ->where('student_id', $child->id)
    ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
    ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date));

    $summary = (clone $base)
    ->selectRaw('status, COUNT(*) as total')
    ->groupBy('status')
    ->pluck('total', 'status');

    $data = (clone $base)
    ->with([
        'journal.schedule.teacher:id,name',
        'journal.schedule.schoolClass:id,class_name',
    ])
    ->latest('date')
    ->paginate(20);

    return response()->json([
        'data' => $data,
        'summary'=> $summary,
        'student' => $child->only('id', 'name', 'class_id'),
    ]);
    }
}
