<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveRequestVerificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $this->visibleTo($request->user())
                ->with([
                    'student:id,name,class_id',
                    'student.schoolClass:id,class_name',
                    'applicant:id,name',
                    'verifier:id,name'
                ])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(Request $request, int $id)
    {
        return response()->json([
            'data' => $this->visibleTo($request->user())
                ->with([
                    'student:id,name,class_id',
                    'student.schoolClass:id,class_name',
                    'applicant:id,name',
                    'verifier:id,name'
                ])
                ->findOrFail($id),
        ]);
    }

    public function approve(Request $request, int $id)
    {
        return $this->transition($request, $id, 'Approved');
    }

    public function reject(Request $request, int $id)
    {
        return $this->transition($request, $id, 'Rejected');
    }

    private function transition(Request $request, int $id, string $target)
    {
        $leave = DB::transaction(function () use ($request, $id, $target) {
            $leave = $this->visibleTo($request->user())->lockForUpdate()->findOrFail($id);

            if ($leave->status_approval !== 'Pending') {
                abort(409, 'Pengajuan sudah pernah diverifikasi.');
            }

            $leave->update([
                'status_approval' => $target,
                'verified_by' => $request->user()->id,
            ]);

            return $leave->fresh();
        });

        return response()->json([
            'message' => $target === 'Approved' ? 'Pengajuan disetujui.' : 'Pengajuan ditolak.',
            'data' => $leave->load('student:id,name', 'verifier:id,name'),
        ]);
    }

    private function visibleTo(User $user): Builder
    {
        $query = LeaveRequest::query();

        if ($user->hasRole('Wali Kelas')) {
            abort_if($user->homeroom_class_id === null, 403, 'Kelas perwalian belum ditetapkan.');
            $query->whereHas('student', fn ($student) =>
                $student->where('class_id', $user->homeroom_class_id)
            );
        }

        return $query;
    }
}
