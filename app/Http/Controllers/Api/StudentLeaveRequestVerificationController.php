<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;

class StudentLeaveRequestVerificationController extends Controller
{
    // GET semua pengajuan izin

    private function scopedQuery(Request $request)
    {
        $query = LeaveRequest::query();

        if ($request->user()->role?->role_name === 'Wali Kelas'){
            $query->whereHas('student', function ($studentQuery) use ($request){
                $studentQuery->where(
                    'class_id',
                    $request->user()->homeroom_class_id
                );
            });
        }
        return $query;
    }

    public function index(Request $request)
    {
        $leaveRequest = $this->scopedQuery($request)
            ->with(['student', 'applicant', 'verifier'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Data pengajuan izin telah berhasil diambil.',
            'data' => $leaveRequest,
        ], 200);
    }


    // GET detail pengajuan izin
    public function show(Request $request, $id)
    {
        $leaveRequest = $this->scopedQuery($request)
        ->with(['student', 'applicant', 'verifier',])
        ->find($id);

        if (!$leaveRequest) {
            return response()->json([
                'message' => 'Pengajuan izin tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail pengajuan izin berhasil diambil.',
            'data' => $leaveRequest,
        ], 200);
    }


    // PUT approve pengajuan izin
    public function approve(Request $request, $id)
    {
        $leaveRequest = $this->scopedQuery($request)
        ->with(['student', 'applicant', 'verifier',])
        ->find($id);

        if (!$leaveRequest) {
            return response()->json([
                'message' => 'Pengajuan izin tidak ditemukan.'
            ], 404);
        }

        // Hanya pengajuan Pending yang bisa diverifikasi
        if ($leaveRequest->status_approval !== 'Pending') {
            return response()->json([
                'message' => 'Pengajuan izin sudah diverifikasi.',
                'errors' => [
                    'status_approval' => [
                        'Hanya pengajuan berstatus Pending yang dapat diverifikasi.',
                    ]
                ]
            ], 409);
        }

        $leaveRequest->update([
            'status_approval' => 'Approved',
            'verified_by' => $request->user()->id,
        ]);

        $leaveRequest->load([
            'student',
            'applicant',
            'verifier',
        ]);

        return response()->json([
            'message' => 'Pengajuan izin berhasil disetujui.',
            'data' => $leaveRequest,
        ], 200);
    }


    // PUT reject pengajuan izin
    public function reject(Request $request, $id)
    {
        $leaveRequest = $this->scopedQuery($request)
        ->with(['student', 'applicant', 'verifier',])
        ->find($id);

        if (!$leaveRequest) {
            return response()->json([
                'message' => 'Pengajuan izin tidak ditemukan.'
            ], 404);
        }

        // Hanya pengajuan Pending yang bisa diverifikasi
        if ($leaveRequest->status_approval !== 'Pending') {
            return response()->json([
                'message' => 'Pengajuan izin sudah diverifikasi.',
                'errors' => [
                    'status_approval' => [
                        'Hanya pengajuan berstatus Pending yang dapat diverifikasi.',
                    ],
                ],
            ], 409);
        }

        $leaveRequest->update([
            'status_approval' => 'Rejected',
            'verified_by' => $request->user()->id,
        ]);

        $leaveRequest->load([
            'student',
            'applicant',
            'verifier',
        ]);

        return response()->json([
            'message' => 'Pengajuan izin berhasil ditolak.',
            'data' => $leaveRequest,
        ], 200);
    }
}