<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class LeaveRequestController extends Controller
{
    public function index (Request $request)
    {
        return response()->json([
            'data' => $this->visibleTo($request->user())
            ->with(['student:id,name,class_id', 'applicant:id,name', 'verifier:id,name'])
            ->latest()
            ->paginate(20),
        ]);
    }

    public function store (Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'student_id' => [Rule::requiredIf($user->hasRole('Orang Tua')), 'nullable', 'integer', 'exists:users,id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'leave_type' => ['required', Rule::in(LeaveRequest::TYPES)],
            'reason' => ['required', 'string', 'max:2000'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
        ]);

        $student = $this->resolveStudent($user, $validated ['student_id'] ?? null);

        $isOverlap = LeaveRequest::where('student_id', $student->id)
            ->whereIn('status_approval', ['Pending', 'Approved'])
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($isOverlap) {
            return response()->json([
                'message' => 'Siswa sudah memiliki permohonan izin aktif atau pending pada rentang tanggal tersebut.',
            ], 409);
        }

        $path = $request->file('document')->store('leave_documents', 'public');

        try {
            $leave = LeaveRequest::create([
                'student_id' => $student->id,
                'applicant_id' => $user->id,
                'verified_by' => null,
                'start_date' => $validated ['start_date'],
                'end_date' => $validated ['end_date'],
                'leave_type' => $validated ['leave_type'],
                'reason' => $validated ['reason'],
                'document_path' => $path,
                'status_approval' => 'Pending'
            ]);
        } 
        catch (Throwable $exception) 
        {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return response()->json([
            'message' => 'Pengajuan izin berhasil dibuat.',
            'data' => $leave->load('student:id,name', 'applicant:id,name'),
        ], 201);
    }

    public function show (Request $request, int $id)
    {
        $leave = $this->visibleTo($request->user())
        ->with([
            'student:id,name,class_id',
            'applicant:id,name',
            'verifier:id,name'
        ])
        ->findOrFail($id);

        return response()->json([
            'data' => $leave
        ]);
    }

    private function resolveStudent(User $user, ? int $studentId): User
    {
        if ($user->hasRole('Siswa')) {
            if ($studentId !== null && $studentId !== $user->id) {
                throw ValidationException::withMessages([
                    'student_id' => 'Siswa hanya dapat mengajukan untuk dirinya.'
                ]);
            }
            return $user;
        }

        return $user->children()
        ->whereHas('role', fn ($query) => $query->where ('role_name', 'Siswa'))
        ->findOrFail($studentId);
    }

    private function visibleTo (User $user): Builder
    {
        $query = LeaveRequest::query();

        if ($user->hasRole('Siswa')) {
            return $query->where('student_id', $user->id);
        }

        return $query->whereIn ('student_id', $user->children()->select('id'));
    }
}
