<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;


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
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'leave_type' => [
                'required',
                Rule::in(['Izin', 'Sakit']),
            ],
            'reason' => 'required|string',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

           $isOverlap = LeaveRequest::where('student_id', $student->id)
            ->whereIn('status_approval', ['Pending', 'Approved'])
            ->whereDate('start_date', '<=', $request->end_date)
            ->whereDate('end_date', '>=', $request->start_date)
            ->exists();
        if ($isOverlap) {
            return response()->json([
                'message' => 'Siswa sudah memiliki permohonan izin aktif atau pending pada rentang tanggal tersebut.',
            ], 409);
        }
        
        $documentPath = $request->file('document')
            ->store('leave-documents', 'public');

        try {
            $leaveRequest = DB::transaction(function () use (
                $student,
                $request,
                $documentPath
            ){
                return LeaveRequest::create([
            'student_id' => $student->id,
            'applicant_id' => $student->id,
            'verified_by' => null,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'leave_type' => $request->leave_type,
            'reason' => $request->reason,
            'document_path' => $documentPath,
            'status_approval' => 'Pending',
        ]);
    });
}   catch (Throwable $exception){
    Storage::disk('public')->delete($documentPath);
    throw $exception;
}

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
