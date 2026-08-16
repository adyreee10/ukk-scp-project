<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;

class StudentLeaveRequestController extends Controller
{
    //get api student leave-request
    public function index(Request $request)
    {
        $student = $request->user();
        $leaveRequest = LeaveRequest::with([
            'student',
            'applicant',
            'verifier',
        ])
        ->where('student_id', $student->id)
        ->orderByDesc('created_at')
        ->get();

        return response()->json([
            'message' => 'Data pengajuan izin berhasil diambil.',
            'data' => $leaveRequest,
        ], 200);
    }

    //post api student leave-request
    public function store(Request $request)
    {
        $student = $request->user();
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'document_path' => 'required|string',
        ]);

        $leaveRequest = LeaveRequest::create([
            'student_id' => $student->id,
            'applicant_id' => $student->id,
            'verified_by' => null,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'document_path' => $request->document_path,
            'status_approval' => 'Pending',
        ]);

        $leaveRequest->load([
            'student',
            'applicant',
            'verifier',
        ]);

        return response()->json([
            'message' => 'Pengajuan izin berhasil dibuat.',
            'data' => $leaveRequest,
        ], 201);
    }

    //get api student leave-request/{id}
    public function show(Request $request, $id)
    {
        $student = $request->user();
        $leaveRequest = LeaveRequest::with([
            'student',
            'applicant',
            'verifier',
        ])
        ->where('student_id', $student->id)
        ->find($id);

        if (!$leaveRequest){
            return response()->json([
                'message' => 'Pengajuan izin tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail pengajuan izin berhasil diambil.',
            'data' => $leaveRequest,
        ], 200);
    }
}
