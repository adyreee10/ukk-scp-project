<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\SystemConfiguration;
use App\Models\Schedule;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Services\JournalReminderService;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TeacherController;
use App\Http\Controllers\Web\WakaController;


// SESUDAH:
Route::get('/', function () {
    return redirect()->route('login');
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
    Route::get('/waka/dashboard', [WakaController::class, 'dashboard'])->name('waka.dashboard');
    Route::get('/waka/cycle', [WakaController::class, 'cycleIndex'])->name('waka.cycle.index');
    Route::post('/waka/toggle-lock', [WakaController::class, 'toggleLock'])->name('waka.toggle-lock');
    Route::post('/waka/toggle-cycle', [WakaController::class, 'toggleCycle'])->name('waka.toggle-cycle');

    // Jadwal Pelajaran (Input Jadwal Mandiri)
    Route::get('/waka/schedules', [WakaController::class, 'schedulesIndex'])->name('waka.schedules.index');
    Route::post('/waka/schedules', [WakaController::class, 'schedulesStore'])->name('waka.schedules.store');
    Route::delete('/waka/schedules/{schedule}', [WakaController::class, 'schedulesDestroy'])->name('waka.schedules.destroy');

    // Master Data Guru
    Route::get('/waka/teachers', [WakaController::class, 'teachersIndex'])->name('waka.teachers.index');
    Route::post('/waka/teachers', [WakaController::class, 'teachersStore'])->name('waka.teachers.store');
    Route::delete('/waka/teachers/{teacher}', [WakaController::class, 'teachersDestroy'])->name('waka.teachers.destroy');

    // Master Data Siswa
    Route::get('/waka/students', [WakaController::class, 'studentsIndex'])->name('waka.students.index');
    Route::post('/waka/students', [WakaController::class, 'studentsStore'])->name('waka.students.store');
    Route::delete('/waka/students/{student}', [WakaController::class, 'studentsDestroy'])->name('waka.students.destroy');

    // Master Data Kelas
    Route::get('/waka/classes', [WakaController::class, 'classesIndex'])->name('waka.classes.index');
    Route::post('/waka/classes', [WakaController::class, 'classesStore'])->name('waka.classes.store');
    Route::delete('/waka/classes/{schoolClass}', [WakaController::class, 'classesDestroy'])->name('waka.classes.destroy');

    // Master Data Ruang
    Route::get('/waka/rooms', [WakaController::class, 'roomsIndex'])->name('waka.rooms.index');
    Route::post('/waka/rooms', [WakaController::class, 'roomsStore'])->name('waka.rooms.store');
    Route::delete('/waka/rooms/{room}', [WakaController::class, 'roomsDestroy'])->name('waka.rooms.destroy');
});

// Rute Guru
Route::middleware(['auth', 'role:Guru'])->group(function () {
    Route::get('/teacher/dashboard', [TeacherController::class, 'dashboard'])->name('teacher.dashboard');
    Route::get('/teacher/schedules', [TeacherController::class, 'schedulesIndex'])->name('teacher.schedules.index');
    Route::get('/teacher/journals/create', [TeacherController::class, 'createJournal'])->name('teacher.journals.create');
    Route::post('/teacher/journals', [TeacherController::class, 'storeJournal'])->name('teacher.journals.store');
});




// Rute Wali Kelas / Guru BK
Route::middleware(['auth', 'role:Wali Kelas,Guru BK'])->group(function () {
     Route::get('/student-affairs/dashboard', function (Request $request) {
        $user = $request->user();

        // 1. Ambil permohonan izin yang Pending sesuai hak akses (Wali Kelas: kelas binaannya, BK: seluruh sekolah)
        $pendingQuery = LeaveRequest::with(['student.schoolClass', 'applicant'])
            ->where('status_approval', 'Pending')
            ->latest();

        // 2. Ambil riwayat izin yang sudah diproses (Approved / Rejected)
        $processedQuery = LeaveRequest::with(['student.schoolClass', 'applicant', 'verifier'])
            ->whereIn('status_approval', ['Approved', 'Rejected'])
            ->latest();

        if ($user->role?->role_name === 'Wali Kelas') {
            $pendingQuery->whereHas('student', function ($q) use ($user) {
                $q->where('class_id', $user->homeroom_class_id);
            });
            $processedQuery->whereHas('student', function ($q) use ($user) {
                $q->where('class_id', $user->homeroom_class_id);
            });
        }

        $pendingLeaves = $pendingQuery->get()->map(function ($leave) {
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

        $processedLeaves = $processedQuery->take(25)->get()->map(function ($leave) {
            return (object)[
                'id' => $leave->id,
                'student_name' => $leave->student?->name ?? 'Siswa',
                'class_name' => $leave->student?->schoolClass?->class_name ?? '-',
                'leave_type' => $leave->leave_type,
                'start_date' => $leave->start_date?->toDateString() ?? (string)$leave->start_date,
                'end_date' => $leave->end_date?->toDateString() ?? (string)$leave->end_date,
                'reason' => $leave->reason,
                'document_path' => $leave->document_path,
                'status' => $leave->status_approval,
                'verifier_name' => $leave->verifier?->name ?? 'Petugas',
                'processed_date' => $leave->updated_at ? \Carbon\Carbon::parse($leave->updated_at)->translatedFormat('d M Y, H:i') : '-',
            ];
        });

        return view('dashboards.student-affairs', compact('pendingLeaves', 'processedLeaves'));
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


// Rute Siswa / Orang Tua (Portal Keluarga)
Route::middleware(['auth', 'role:Siswa,Orang Tua'])->group(function () {
    
    // 1. Dashboard Utama & Statistik Kehadiran
    Route::get('/family/dashboard', function (Request $request) {
        $user = $request->user();
        $studentId = $user->role?->role_name === 'Siswa' ? $user->id : ($user->children()->first()?->id ?? $user->id);
        $student = \App\Models\User::with('schoolClass')->find($studentId) ?? $user;

        $attendances = Attendance::where('student_id', $studentId)->get();
        $totalSessions = $attendances->count();
        $hadirCount = $attendances->where('status', 'Hadir')->count();
        $attendanceRate = $totalSessions > 0 ? round(($hadirCount / $totalSessions) * 100) : 100;

        $attendanceSummary = [
            'Hadir' => $hadirCount,
            'Izin'  => $attendances->where('status', 'Izin')->count(),
            'Sakit' => $attendances->where('status', 'Sakit')->count(),
            'Alpa'  => $attendances->where('status', 'Alpa')->count(),
            'Total' => $totalSessions,
            'Rate'  => $attendanceRate,
        ];

        // 3 Pengajuan izin terbaru untuk ringkasan di dashboard
        $recentLeaves = LeaveRequest::where('student_id', $studentId)
            ->latest()
            ->take(3)
            ->get();

        return view('dashboards.family', compact('attendanceSummary', 'recentLeaves', 'student'));
    })->name('family.dashboard');

    // 2. Halaman Formulir Pengajuan Izin Baru
    Route::get('/family/leaves/create', function (Request $request) {
        $user = $request->user();
        $studentId = $user->role?->role_name === 'Siswa' ? $user->id : ($user->children()->first()?->id ?? $user->id);
        $student = \App\Models\User::with('schoolClass')->find($studentId) ?? $user;

        return view('family.leaves.create', compact('student'));
    })->name('family.leaves.create');

    // 3. Aksi Simpan Pengajuan Izin Baru
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

        // Pencegahan pengajuan izin ganda di rentang tanggal yang sama
        $isOverlap = LeaveRequest::where('student_id', $studentId)
            ->whereIn('status_approval', ['Pending', 'Approved'])
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($isOverlap) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal mengajukan izin: Anda sudah memiliki permohonan izin aktif pada rentang tanggal tersebut.');
        }

        // Upload dokumen bukti izin
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

        // Dialihkan langsung ke halaman Riwayat & Rekapitulasi Izin
        return redirect()->route('family.leaves.history')
            ->with('success', 'Permohonan izin berhasil diajukan dan sedang menunggu verifikasi Wali Kelas / Guru BK.');
    })->name('family.leaves.store');

    // 4. Halaman Riwayat Pengajuan Izin & Rekapitulasi Presensi
    Route::get('/family/leaves/history', function (Request $request) {
        $user = $request->user();
        $studentId = $user->role?->role_name === 'Siswa' ? $user->id : ($user->children()->first()?->id ?? $user->id);
        $student = \App\Models\User::with('schoolClass')->find($studentId) ?? $user;

        // Riwayat semua pengajuan surat izin
        $leaveRequests = LeaveRequest::where('student_id', $studentId)
            ->latest()
            ->get();

        // Rekapitulasi sesi presensi KBM siswa
        $attendances = Attendance::with(['journal.schedule.teacher', 'journal.schedule.room'])
            ->where('student_id', $studentId)
            ->latest('date')
            ->get();

        $attendanceSummary = [
            'Hadir' => $attendances->where('status', 'Hadir')->count(),
            'Izin'  => $attendances->where('status', 'Izin')->count(),
            'Sakit' => $attendances->where('status', 'Sakit')->count(),
            'Alpa'  => $attendances->where('status', 'Alpa')->count(),
            'Total' => $attendances->count(),
        ];

        return view('family.leaves.history', compact('leaveRequests', 'attendances', 'attendanceSummary', 'student'));
    })->name('family.leaves.history');
});

require __DIR__.'/auth.php';
