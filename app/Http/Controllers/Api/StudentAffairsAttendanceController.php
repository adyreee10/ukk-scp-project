<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class StudentAffairsAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $user = $request->user();
        if ($user->hasRole('Wali Kelas')) {
            $classId = $user->homeroom_class_id;
            abort_if($classId === null, 403, 'Kelas perwalian belum ditetapkan.');
        } elseif ($user->hasRole('Guru BK')) {
            $handledClassIds = $user->getHandledClassIds();
            $requestedClassId = $validated['class_id'] ?? null;
            if ($requestedClassId && in_array($requestedClassId, $handledClassIds)) {
                $classId = $requestedClassId;
            } else {
                $classId = $handledClassIds[0] ?? null;
            }
            abort_if($classId === null, 403, 'Kelas binaan belum ditetapkan.');
        } else {
            $classId = $validated['class_id'] ?? null;
        }

        $base = Attendance::query()
        ->when($classId, fn ($query, $id) => $query->whereHas('student', fn ($student) => $student->where('class_id', $id)))
        ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
        ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date));

        $summary = (clone $base)
        ->selectRaw('status, COUNT(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status');

        $data = (clone $base)
        ->with([
            'student:id,name,class_id',
            'journal.schedule.teacher:id,name'
        ])
        ->latest('date')
        ->paginate(20);

        return response()->json([
            'data' => $data,
            'summary' => $summary
        ]);
    }
}
