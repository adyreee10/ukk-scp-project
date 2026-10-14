<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\SystemConfiguration;
use App\Models\Schedule;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Services\JournalReminderService;
use App\Http\Controllers\Web\DashboardController; // <--- Pastikan import ini ada di atas

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rute Waka Kurikulum
Route::middleware(['auth', 'role:Waka Kurikulum'])->group(function () {
    Route::get('/waka/dashboard', function (JournalReminderService $service) {
        $configuration = SystemConfiguration::first();
        
        // Memanggil service asli untuk mendeteksi guru terlambat hari ini
        $lateTeachers = $service->overdueFor();
        return view('dashboards.waka', compact('configuration', 'lateTeachers'));
    })->name('waka.dashboard');
    // Action: Kunci/Buka Jadwal
    Route::post('/waka/toggle-lock', function (Request $request) {
        $config = SystemConfiguration::first();
        $config->update([
            'is_schedule_locked' => !$config->is_schedule_locked,
            'updated_by' => auth()->id()
        ]);
        $status = $config->is_schedule_locked ? 'dikunci' : 'dibuka';
        return redirect()->back()->with('success', "Jadwal berhasil $status!");
    })->name('waka.toggle-lock');
    // Action: Ubah Putaran
    Route::post('/waka/toggle-cycle', function (Request $request) {
        $config = SystemConfiguration::first();
        $newCycle = $config->active_cycle == 'Putaran 1' ? 'Putaran 2' : 'Putaran 1';
        $config->update([
            'active_cycle' => $newCycle,
            'updated_by' => auth()->id()
        ]);
        return redirect()->back()->with('success', "Putaran aktif berhasil diubah menjadi $newCycle!");
    })->name('waka.toggle-cycle');
});

// Rute Guru
Route::middleware(['auth', 'role:Guru'])->group(function () {
    Route::get('/teacher/dashboard', function (JournalReminderService $service) {
        $configuration = SystemConfiguration::current();
        $user = auth()->user();

        // 1. Ambil jadwal riil guru pada putaran aktif
        $schedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->where('cycle_type', $configuration->active_cycle)
            ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 END")
            ->orderBy('start_time')
            ->get();

        // 2. Deteksi keterlambatan jurnal riil untuk guru yang login
        $overdueSchedules = $service->overdueFor($user);

        return view('dashboards.teacher', compact('schedules', 'configuration', 'overdueSchedules'));
    })->name('teacher.dashboard');
});


// Rute Wali Kelas / Guru BK
Route::middleware(['auth', 'role:Wali Kelas,Guru BK'])->group(function () {
    Route::get('/student-affairs/dashboard', function (Request $request) {
        $user = $request->user();

        // Ambil permohonan izin yang Pending sesuai hak akses (Wali Kelas hanya kelas binaannya, BK seluruh sekolah)
        $query = LeaveRequest::with(['student.schoolClass', 'applicant'])
            ->where('status_approval', 'Pending')
            ->latest();

        if ($user->role?->role_name === 'Wali Kelas') {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('class_id', $user->homeroom_class_id);
            });
        }

        $pendingLeaves = $query->get()->map(function ($leave) {
            return (object)[
                'id' => $leave->id,
                'student_name' => $leave->student?->name ?? 'Siswa',
                'class_name' => $leave->student?->schoolClass?->class_name ?? '-',
                'leave_type' => $leave->leave_type,
                'start_date' => $leave->start_date?->toDateString() ?? (string)$leave->start_date,
                'end_date' => $leave->end_date?->toDateString() ?? (string)$leave->end_date,
                'reason' => $leave->reason,
                'document_path' => $leave->document_path,
            ];
        });

        return view('dashboards.student-affairs', compact('pendingLeaves'));
    })->name('student-affairs.dashboard');

    // Aksi: Approve Izin Siswa
    Route::post('/student-affairs/leaves/{id}/approve', function ($id) {
        $leave = LeaveRequest::findOrFail($id);
        $leave->update([
            'status_approval' => 'Approved',
            'verified_by' => auth()->id(),
        ]);
        return redirect()->back()->with('success', 'Permohonan izin berhasil disetujui!');
    })->name('student-affairs.leaves.approve');

    // Aksi: Reject Izin Siswa
    Route::post('/student-affairs/leaves/{id}/reject', function ($id) {
        $leave = LeaveRequest::findOrFail($id);
        $leave->update([
            'status_approval' => 'Rejected',
            'verified_by' => auth()->id(),
        ]);
        return redirect()->back()->with('success', 'Permohonan izin berhasil ditolak.');
    })->name('student-affairs.leaves.reject');
});


// Rute Siswa / Orang Tua
Route::middleware(['auth', 'role:Siswa,Orang Tua'])->group(function () {
    Route::get('/family/dashboard', function (Request $request) {
        $user = $request->user();
        $studentId = $user->role?->role_name === 'Siswa' ? $user->id : ($user->children()->first()?->id ?? $user->id);

        // 1. Hitung ringkasan presensi riil siswa
        $attendances = Attendance::where('student_id', $studentId)->get();
        $attendanceSummary = [
            'Hadir' => $attendances->where('status', 'Hadir')->count(),
            'Izin'  => $attendances->where('status', 'Izin')->count(),
            'Sakit' => $attendances->where('status', 'Sakit')->count(),
            'Alpa'  => $attendances->where('status', 'Alpa')->count(),
        ];

        // 2. Ambil riwayat izin riil siswa
        $leaveRequests = LeaveRequest::where('student_id', $studentId)
            ->latest()
            ->get()
            ->map(function ($req) {
                return (object)[
                    'id' => $req->id,
                    'leave_type' => $req->leave_type,
                    'start_date' => $req->start_date,
                    'status' => $req->status_approval,
                ];
            });

        return view('dashboards.family', compact('attendanceSummary', 'leaveRequests'));
    })->name('family.dashboard');

        // Aksi: Kirim Formulir Pengajuan Izin Baru (Dengan Hardening Fase 15)
    Route::post('/family/leaves', function (Request $request) {
        $user = $request->user();
        $studentId = $user->role?->role_name === 'Siswa' ? $user->id : ($user->children()->first()?->id ?? $user->id);

        $validated = $request->validate([
            'leave_type' => ['required', 'in:Sakit,Izin'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['required', 'string', 'max:255'],
            'document'   => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        // --- HARDENING FASE 15: Cegah Overlap Izin (Pending / Approved) ---
        $isOverlap = LeaveRequest::where('student_id', $studentId)
            ->whereIn('status_approval', ['Pending', 'Approved'])
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($isOverlap) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal mengajukan izin: Anda sudah memiliki permohonan izin aktif atau sedang diproses pada rentang tanggal tersebut.');
        }

        // Upload dokumen wajib
        $docPath = $request->file('document')->store('leave_documents', 'public');

        LeaveRequest::create([
            'student_id'      => $studentId,
            'applicant_id'    => $user->id,
            'leave_type'      => $validated['leave_type'],
            'start_date'      => $validated['start_date'],
            'end_date'        => $validated['end_date'],
            'reason'          => $validated['reason'],
            'document_path'   => $docPath,
            'status_approval' => 'Pending',
        ]);

        return redirect()->back()->with('success', 'Permohonan izin berhasil diajukan dan sedang menunggu persetujuan.');
    })->name('family.leaves.store');

});


require __DIR__.'/auth.php';
