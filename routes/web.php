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

// Endpoint Satu-Klik Sinkronisasi Akun Orang Tua di Web Hosting (Dilindungi Secret Key)
Route::get('/sync-parents-direct', [WakaController::class, 'studentsSyncParentsDirect']);

// Diagnostic & Development Tools (Hanya Aktif di Mode Debug)
if (config('app.debug')) {
    Route::get('/dev-reset-rezky', function (Request $request) {
        if ($request->query('key') !== 'ukk2026') {
            abort(403, 'Unauthorized');
        }
        $user = \App\Models\User::where('nis', '17821')->orWhere('id', 16)->first();
        if (!$user) {
            return response('❌ User NIS 17821 tidak ditemukan di database.', 404);
        }
        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make('password123')
        ]);
        return response("<h2 style='font-family:sans-serif;color:#10b981;'>✔ Berhasil! Password Rezky Aditya (NIS: 17821) sudah direset menjadi 'password123'.</h2><p><a href='/login'>Klik di sini untuk ke halaman Login</a></p>");
    });

    Route::get('/dev-db-check', function (Request $request) {
        if ($request->query('key') !== 'ukk2026') {
            abort(403, 'Unauthorized');
        }

        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            @unlink(base_path('bootstrap/cache/config.php'));
        }
        if (file_exists(base_path('bootstrap/cache/routes-v7.php'))) {
            @unlink(base_path('bootstrap/cache/routes-v7.php'));
        }

        try {
            $defaultConn = config('database.default');
            $connConfig = config("database.connections.{$defaultConn}");
            if (isset($connConfig['password'])) {
                $connConfig['password'] = '******';
            }

            $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
            $dbName = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
            $roomCount = \App\Models\Room::count();
            $scheduleCount = \App\Models\Schedule::count();
            $userCount = \App\Models\User::count();

            return response()->json([
                'status' => 'connected',
                'driver' => $driver,
                'database' => $dbName,
                'default_connection' => $defaultConn,
                'connection_details' => $connConfig,
                'room_count' => $roomCount,
                'schedule_count' => $scheduleCount,
                'user_count' => $userCount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'default_connection' => config('database.default'),
                'env_db_connection' => env('DB_CONNECTION'),
                'env_db_host' => env('DB_HOST'),
                'env_db_database' => env('DB_DATABASE'),
            ], 500);
        }
    });
}



Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Pencarian Global Navbar (Guru, Siswa, Jadwal, Jurnal, Kelas)
    Route::get('/search', function (Request $request) {
        $q = trim($request->get('q', ''));
        
        $teachers = collect();
        $students = collect();
        $schedules = collect();
        $journals = collect();
        $classes = collect();

        if ($q !== '') {
            $teachers = \App\Models\User::whereHas('role', fn($r) => $r->whereIn('role_name', ['Guru', 'Wali Kelas']))
                ->where(function($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%")
                          ->orWhere('username', 'like', "%{$q}%");
                })
                ->with(['role', 'homeroomClass'])
                ->take(10)
                ->get();

            $students = \App\Models\User::whereHas('role', fn($r) => $r->where('role_name', 'Siswa'))
                ->where(function($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                          ->orWhere('username', 'like', "%{$q}%");
                })
                ->with('schoolClass')
                ->take(10)
                ->get();

            $schedules = \App\Models\Schedule::where('subject', 'like', "%{$q}%")
                ->orWhere('day_of_week', 'like', "%{$q}%")
                ->with(['teacher', 'schoolClass', 'room'])
                ->take(10)
                ->get();

            $journals = \App\Models\Journal::where('topic_description', 'like', "%{$q}%")
                ->with(['schedule.teacher', 'schedule.schoolClass'])
                ->latest('date')
                ->take(10)
                ->get();

            $classes = \App\Models\SchoolClass::where('class_name', 'like', "%{$q}%")
                ->take(5)
                ->get();
        }

        $totalResults = $teachers->count() + $students->count() + $schedules->count() + $journals->count() + $classes->count();

        return view('search', compact('q', 'teachers', 'students', 'schedules', 'journals', 'classes', 'totalResults'));
    })->name('search');

});

// Route untuk menyajikan berkas dokumen dan foto secara andal (Bypass 403 Forbidden di cPanel)
Route::get('/media/{path}', function (string $path) {
    $cleanPath = str_replace(['../', '..\\'], '', $path);
    $fullPath = storage_path('app/public/' . $cleanPath);

    if (!file_exists($fullPath)) {
        $altPath = storage_path('app/' . $cleanPath);
        if (file_exists($altPath)) {
            $fullPath = $altPath;
        } else {
            abort(404, 'Berkas tidak ditemukan.');
        }
    }

    $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
    return response()->file($fullPath, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*')->name('media.serve');

Route::get('/storage/{path}', function (string $path) {
    $cleanPath = str_replace(['../', '..\\'], '', $path);
    $fullPath = storage_path('app/public/' . $cleanPath);

    if (!file_exists($fullPath)) {
        $altPath = storage_path('app/' . $cleanPath);
        if (file_exists($altPath)) {
            $fullPath = $altPath;
        } else {
            abort(404, 'Berkas tidak ditemukan.');
        }
    }

    $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
    return response()->file($fullPath, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');


// Rute Waka Kurikulum
Route::middleware(['auth', 'role:Waka Kurikulum'])->group(function () {
    Route::get('/waka/dashboard', [WakaController::class, 'dashboard'])->name('waka.dashboard');
    Route::get('/waka/cycle', [WakaController::class, 'cycleIndex'])->name('waka.cycle.index');
    Route::post('/waka/toggle-lock', [WakaController::class, 'toggleLock'])->name('waka.toggle-lock');
    Route::post('/waka/toggle-cycle', [WakaController::class, 'toggleCycle'])->name('waka.toggle-cycle');

    // Master Data Tahun Ajaran & Semester
    Route::get('/waka/academic-year', [WakaController::class, 'academicYearIndex'])->name('waka.academic-year.index');
    Route::post('/waka/academic-year', [WakaController::class, 'academicYearStore'])->name('waka.academic-year.store');
    Route::post('/waka/academic-period', [WakaController::class, 'updateAcademicPeriod'])->name('waka.academic-period.update');

    // Master Data Jam Pembelajaran
    Route::get('/waka/lesson-hours', [WakaController::class, 'lessonHoursIndex'])->name('waka.lesson-hours.index');
    Route::put('/waka/lesson-hours/{lessonHour}', [WakaController::class, 'lessonHoursUpdate'])->name('waka.lesson-hours.update');
    Route::post('/waka/lesson-hours/preset', [WakaController::class, 'lessonHoursApplyPreset'])->name('waka.lesson-hours.preset');

    // Master Data Jurusan
    Route::get('/waka/majors', [WakaController::class, 'majorsIndex'])->name('waka.majors.index');
    Route::post('/waka/majors', [WakaController::class, 'majorsStore'])->name('waka.majors.store');
    Route::put('/waka/majors/{major}', [WakaController::class, 'majorsUpdate'])->name('waka.majors.update');
    Route::delete('/waka/majors/{major}', [WakaController::class, 'majorsDestroy'])->name('waka.majors.destroy');

    // Master Data Kelas (Update & Wali Kelas)
    Route::put('/waka/classes/{schoolClass}', [WakaController::class, 'classesUpdate'])->name('waka.classes.update');

    // Kenaikan Kelas & Kelulusan Siswa (Per Siswa & Massal)
    Route::post('/waka/grades/{student}/promote', [WakaController::class, 'gradesPromote'])->name('waka.grades.promote');
    Route::post('/waka/grades/promote-mass', [WakaController::class, 'gradesPromoteMass'])->name('waka.grades.promote-mass');
    Route::post('/waka/grades/graduate-mass', [WakaController::class, 'gradesGraduateMass'])->name('waka.grades.graduate-mass');
    Route::post('/waka/grades/promote-selected', [WakaController::class, 'gradesPromoteSelected'])->name('waka.grades.promote-selected');


    // Master Data Tingkat Kelas & Kelulusan (Siswa Lulus)
    Route::get('/waka/grades', [WakaController::class, 'gradesIndex'])->name('waka.grades.index');
    Route::post('/waka/grades/{student}/toggle-status', [WakaController::class, 'gradesToggleStatus'])->name('waka.grades.toggle-status');
    Route::put('/waka/grades/{student}', [WakaController::class, 'gradesUpdate'])->name('waka.grades.update');

    // Jadwal Pelajaran (Input Jadwal Mandiri & Import)
    Route::get('/waka/schedules', [WakaController::class, 'schedulesIndex'])->name('waka.schedules.index');
    Route::get('/waka/schedules/template', [WakaController::class, 'schedulesTemplate'])->name('waka.schedules.template');
    Route::post('/waka/schedules/import', [WakaController::class, 'schedulesImport'])->name('waka.schedules.import');
    Route::post('/waka/schedules', [WakaController::class, 'schedulesStore'])->name('waka.schedules.store');
    Route::delete('/waka/schedules/{schedule}', [WakaController::class, 'schedulesDestroy'])->name('waka.schedules.destroy');
    Route::put('/waka/schedules/{schedule}', [WakaController::class, 'schedulesUpdate'])->name('waka.schedules.update');
    Route::post('/waka/schedules/delete-selected', [WakaController::class, 'schedulesDeleteSelected'])->name('waka.schedules.delete-selected');
    Route::post('/waka/schedules/delete-all', [WakaController::class, 'schedulesDeleteAll'])->name('waka.schedules.delete-all');

    // Master Data Guru & Import
    Route::get('/waka/teachers', [WakaController::class, 'teachersIndex'])->name('waka.teachers.index');
    Route::get('/waka/teachers/template', [WakaController::class, 'teachersTemplate'])->name('waka.teachers.template');
    Route::post('/waka/teachers/import', [WakaController::class, 'teachersImport'])->name('waka.teachers.import');
    Route::post('/waka/teachers', [WakaController::class, 'teachersStore'])->name('waka.teachers.store');
    Route::put('/waka/teachers/{teacher}', [WakaController::class, 'teachersUpdate'])->name('waka.teachers.update');
    Route::delete('/waka/teachers/{teacher}', [WakaController::class, 'teachersDestroy'])->name('waka.teachers.destroy');
    Route::post('/waka/teachers/delete-selected', [WakaController::class, 'teachersDeleteSelected'])->name('waka.teachers.delete-selected');
    Route::post('/waka/teachers/delete-all', [WakaController::class, 'teachersDeleteAll'])->name('waka.teachers.delete-all');

    // Master Data Siswa & Import
    Route::get('/waka/students', [WakaController::class, 'studentsIndex'])->name('waka.students.index');
    Route::get('/waka/students/template', [WakaController::class, 'studentsTemplate'])->name('waka.students.template');
    Route::post('/waka/students/import', [WakaController::class, 'studentsImport'])->name('waka.students.import');
    Route::post('/waka/students', [WakaController::class, 'studentsStore'])->name('waka.students.store');
    Route::put('/waka/students/{student}', [WakaController::class, 'studentsUpdate'])->name('waka.students.update');
    Route::delete('/waka/students/{student}', [WakaController::class, 'studentsDestroy'])->name('waka.students.destroy');
    Route::post('/waka/students/delete-selected', [WakaController::class, 'studentsDeleteSelected'])->name('waka.students.delete-selected');
    Route::post('/waka/students/delete-all', [WakaController::class, 'studentsDeleteAll'])->name('waka.students.delete-all');
    Route::post('/waka/students/sync-parents', [WakaController::class, 'studentsSyncParents'])->name('waka.students.sync-parents');

    // Master Data Kelas
    Route::get('/waka/classes', [WakaController::class, 'classesIndex'])->name('waka.classes.index');
    Route::post('/waka/classes', [WakaController::class, 'classesStore'])->name('waka.classes.store');
    Route::delete('/waka/classes/{schoolClass}', [WakaController::class, 'classesDestroy'])->name('waka.classes.destroy');

    // Master Data Ruang & Import
    Route::get('/waka/rooms', [WakaController::class, 'roomsIndex'])->name('waka.rooms.index');
    Route::get('/waka/rooms/template', [WakaController::class, 'roomsTemplate'])->name('waka.rooms.template');
    Route::post('/waka/rooms/import', [WakaController::class, 'roomsImport'])->name('waka.rooms.import');
    Route::post('/waka/rooms', [WakaController::class, 'roomsStore'])->name('waka.rooms.store');
    Route::delete('/waka/rooms/{room}', [WakaController::class, 'roomsDestroy'])->name('waka.rooms.destroy');

    // Monitoring & Riwayat Jurnal Guru
    Route::get('/waka/journals', [WakaController::class, 'journalsIndex'])->name('waka.journals.index');

});

// ========================================================
// 1. RUTE GURU, WALI KELAS, & GURU BK (AKTIVITAS MENGAJAR)
// ========================================================
Route::middleware(['auth', 'role:Guru,Wali Kelas,Guru BK'])->group(function () {
    Route::get('/teacher/dashboard', [TeacherController::class, 'dashboard'])->name('teacher.dashboard');
    Route::get('/teacher/schedules', [TeacherController::class, 'schedulesIndex'])->name('teacher.schedules.index');
    Route::get('/teacher/journals/create', [TeacherController::class, 'createJournal'])->name('teacher.journals.create');
    Route::post('/teacher/journals', [TeacherController::class, 'storeJournal'])->name('teacher.journals.store');
    Route::put('/teacher/journals/{journal}/attendance', [TeacherController::class, 'updateJournalAttendance'])->name('teacher.journals.update-attendance');
    Route::get('/teacher/journals/history', [TeacherController::class, 'journalsHistory'])->name('teacher.journals.history');
});

// ========================================================
// 2. RUTE VERIFIKASI IZIN PENDING SISWA (WALI KELAS & GURU BK)
// Wali Kelas & Guru BK sama-sama dapat melakukan approve atau reject izin siswa
// ========================================================
Route::middleware(['auth', 'role:Wali Kelas,Guru BK'])->group(function () {
    
    // Laman Verifikasi Izin Siswa (Pending)
    Route::get('/student-affairs/leaves', function (Request $request) {
        $user = $request->user();
        $handledClassIds = $user->getHandledClassIds();

        $query = LeaveRequest::with(['student.schoolClass', 'applicant'])
            ->where('status_approval', 'Pending');

        // Jika Wali Kelas atau Guru BK, batasi secara ketat ke kelas perwaliannya
        if (in_array($user->role?->role_name, ['Wali Kelas', 'Guru BK'])) {
            $query->whereHas('student', function ($q) use ($handledClassIds) {
                $q->whereIn('class_id', $handledClassIds);
            });

            // Filter opsional jika Guru BK memilih salah satu kelas dari daftar yang ia ampu
            if ($request->filled('class_id') && $request->class_id !== 'all') {
                $targetClassId = (int) $request->class_id;
                if (in_array($targetClassId, $handledClassIds)) {
                    $query->whereHas('student', function ($q) use ($targetClassId) {
                        $q->where('class_id', $targetClassId);
                    });
                }
            }
        } elseif ($request->filled('class_id') && $request->class_id !== 'all') {
            // Waka Kurikulum dapat memfilter ke kelas mana pun
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        $pendingLeaves = $query->latest()
            ->get()
            ->map(function ($leave) {
                return (object)[
                    'id'            => $leave->id,
                    'student_name'  => $leave->student?->name ?? 'Siswa',
                    'class_name'    => $leave->student?->schoolClass?->class_name ?? '-',
                    'leave_type'    => $leave->leave_type,
                    'start_date'    => $leave->start_date?->toDateString() ?? (string)$leave->start_date,
                    'end_date'      => $leave->end_date?->toDateString() ?? (string)$leave->end_date,
                    'period_label'  => $leave->period_label, 
                    'reason'        => $leave->reason,
                    'document_path' => $leave->document_path,
                ];
            });

        return view('dashboards.student-affairs', compact('pendingLeaves'));
    })->name('student-affairs.leaves.pending');

    // Aksi: Approve Izin Siswa
    Route::post('/student-affairs/leaves/{id}/approve', function ($id) {
        $leave = LeaveRequest::with('student')->findOrFail($id);
        $user = auth()->user();

        // Validasi hak akses perwalian untuk Wali Kelas dan Guru BK
        if (in_array($user->role?->role_name, ['Wali Kelas', 'Guru BK'])) {
            $handledClassIds = $user->getHandledClassIds();
            if (!in_array($leave->student?->class_id, $handledClassIds)) {
                return redirect()->back()->with('error', 'Akses ditolak. Anda hanya dapat menyetujui izin siswa di kelas perwalian Anda.');
            }
        }

        if ($leave->status_approval !== 'Pending') {
            return redirect()->back()->with('error', 'Permohonan izin ini sudah pernah diverifikasi sebelumnya.');
        }
        $leave->update([
            'status_approval' => 'Approved',
            'verified_by' => $user->id,
        ]);
        return redirect()->back()->with('success', 'Permohonan izin berhasil disetujui!');
    })->name('student-affairs.leaves.approve');

    // Aksi: Reject Izin Siswa
    Route::post('/student-affairs/leaves/{id}/reject', function (Request $request, $id) {
        $validated = $request->validate([
            'rejection_note' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'rejection_note.required' => 'Alasan penolakan izin wajib diisi.',
            'rejection_note.min' => 'Alasan penolakan minimal 3 karakter.',
            'rejection_note.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $leave = LeaveRequest::with('student')->findOrFail($id);
        $user = auth()->user();

        // Validasi hak akses perwalian untuk Wali Kelas dan Guru BK
        if (in_array($user->role?->role_name, ['Wali Kelas', 'Guru BK'])) {
            $handledClassIds = $user->getHandledClassIds();
            if (!in_array($leave->student?->class_id, $handledClassIds)) {
                return redirect()->back()->with('error', 'Akses ditolak. Anda hanya dapat menolak izin siswa di kelas perwalian Anda.');
            }
        }

        if ($leave->status_approval !== 'Pending') {
            return redirect()->back()->with('error', 'Permohonan izin ini sudah pernah diverifikasi sebelumnya.');
        }
        $leave->update([
            'status_approval' => 'Rejected',
            'rejection_note' => $validated['rejection_note'],
            'verified_by' => $user->id,
        ]);
        return redirect()->back()->with('success', 'Permohonan izin berhasil ditolak dengan alasan yang tercatat.');
    })->name('student-affairs.leaves.reject');
});

// ========================================================
// 3. RUTE REKAPAN KESISWAAN (WALI KELAS & GURU BK)
// Guru BK & Wali Kelas bisa melihat rekapan izin & rekap presensi
// ========================================================
Route::middleware(['auth', 'role:Wali Kelas,Guru BK,Waka Kurikulum'])->group(function () {

    // Alias redirect URL lama
    Route::get('/student-affairs/dashboard', function (Request $request) {
        if ($request->user()->role?->role_name === 'Waka Kurikulum') {
            return redirect()->route('student-affairs.leaves.history');
        }
        return redirect()->route('student-affairs.leaves.pending');
    })->name('student-affairs.dashboard');

        // Laman Riwayat & Rekapan Izin Siswa
    Route::get('/student-affairs/leaves/history', function (Request $request) {
        $user = $request->user();
        $isWaka = ($user->role?->role_name === 'Waka Kurikulum');
        $handledClassIds = $user->getHandledClassIds();

        $selectedGrade  = $request->query('grade');
        $selectedMajor  = $request->query('major');
        $selectedRombel = $request->query('rombel');

        $activeClass = null;
        $majors = \App\Models\Major::orderBy('name')->get();
        $selectedClassId = $request->query('class_id');

        $processedQuery = LeaveRequest::with(['student.schoolClass', 'applicant', 'verifier'])
            ->whereIn('status_approval', ['Approved', 'Rejected'])
            ->latest();

        if (!$isWaka) {
            // Wali Kelas & Guru BK dibatasi HANYA ke kelas perwaliannya
            $classes = \App\Models\SchoolClass::whereIn('id', $handledClassIds)->orderBy('class_name')->get();
            $processedQuery->whereHas('student', function ($q) use ($handledClassIds) {
                $q->whereIn('class_id', $handledClassIds);
            });

            if ($user->role?->role_name === 'Wali Kelas') {
                $selectedClassId = $user->homeroom_class_id;
            } elseif ($selectedClassId && in_array((int)$selectedClassId, $handledClassIds)) {
                $processedQuery->whereHas('student', function ($q) use ($selectedClassId) {
                    $q->where('class_id', $selectedClassId);
                });
            }
        } else {
            // Waka Kurikulum memiliki akses menyeluruh ke seluruh kelas
            $classes = \App\Models\SchoolClass::orderBy('class_name')->get();

            if ($selectedGrade || $selectedMajor || $selectedRombel) {
                $processedQuery->whereHas('student.schoolClass', function ($q) use ($selectedGrade, $selectedMajor, $selectedRombel) {
                    if ($selectedGrade) {
                        $q->where('class_name', 'like', "{$selectedGrade} %");
                    }
                    if ($selectedMajor) {
                        $q->where(function ($sub) use ($selectedMajor) {
                            $sub->where('class_name', 'like', "% {$selectedMajor} %")
                                ->orWhere('class_name', 'like', "% {$selectedMajor}");
                        });
                    }
                    if ($selectedRombel) {
                        $q->where('class_name', 'like', "% {$selectedRombel}");
                    }
                });
            } elseif ($selectedClassId && $selectedClassId !== 'all') {
                $processedQuery->whereHas('student', function ($q) use ($selectedClassId) {
                    $q->where('class_id', $selectedClassId);
                });
            }
        }

        $search = trim($request->query('search', ''));
        if ($search !== '') {
            $searchLower = strtolower($search);
            $processedQuery->where(function ($sq) use ($searchLower) {
                $sq->whereHas('student', function ($stQ) use ($searchLower) {
                    $stQ->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw("LOWER(COALESCE(nis, '')) LIKE ?", ["%{$searchLower}%"])
                        ->orWhereHas('schoolClass', function ($scQ) use ($searchLower) {
                            $scQ->whereRaw('LOWER(class_name) LIKE ?', ["%{$searchLower}%"]);
                        });
                })->orWhereRaw('LOWER(reason) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(leave_type) LIKE ?', ["%{$searchLower}%"]);
            });
        }

        $processedLeaves = $processedQuery->take(100)->get()->map(function ($leave) {
            return (object)[
                'id'            => $leave->id,
                'student_name'  => $leave->student?->name ?? 'Siswa',
                'student_nis'   => $leave->student?->nis ?? '',
                'class_name'    => $leave->student?->schoolClass?->class_name ?? '-',
                'leave_type'    => $leave->leave_type,
                'start_date'    => $leave->start_date?->toDateString() ?? (string)$leave->start_date,
                'end_date'      => $leave->end_date?->toDateString() ?? (string)$leave->end_date,
                'period_label'  => $leave->period_label,
                'reason'        => $leave->reason,
                'document_path' => $leave->document_path,
                'status'        => $leave->status_approval,
                'verifier_name' => $leave->verifier?->name ?? 'Wali Kelas / Guru BK',
                'rejection_note'=> $leave->rejection_note,
                'processed_date'=> $leave->updated_at ? \Carbon\Carbon::parse($leave->updated_at)->translatedFormat('d M Y, H:i') : '-',
            ];
        });

        return view('student-affairs.history', compact('processedLeaves', 'classes', 'majors', 'selectedGrade', 'selectedMajor', 'selectedRombel', 'selectedClassId'));
    })->name('student-affairs.leaves.history');

    // Laman Rekapitulasi Presensi Siswa (Beserta status disiplin & filter 3 kriteria)
    Route::get('/student-affairs/attendance', function (Request $request) {
        $user = $request->user();
        $isWaka = ($user->role?->role_name === 'Waka Kurikulum');
        $handledClassIds = $user->getHandledClassIds();

        $selectedGrade  = $request->query('grade');
        $selectedMajor  = $request->query('major');
        $selectedRombel = $request->query('rombel');

        $activeClass = null;
        $majors = \App\Models\Major::orderBy('name')->get();

        if (!$isWaka) {
            // Wali Kelas & Guru BK dibatasi HANYA ke kelas perwaliannya
            $classes = \App\Models\SchoolClass::whereIn('id', $handledClassIds)->orderBy('class_name')->get();

            if ($user->role?->role_name === 'Wali Kelas') {
                $activeClassId = $user->homeroom_class_id;
                $activeClass = $classes->firstWhere('id', $activeClassId);
                $targetClassIds = $activeClassId ? [$activeClassId] : [];
            } else {
                // Guru BK memfilter dalam cakupan kelas yang diampunya
                $classQuery = \App\Models\SchoolClass::whereIn('id', $handledClassIds);
                if ($selectedGrade) {
                    $classQuery->where('class_name', 'like', "{$selectedGrade} %");
                }
                if ($selectedMajor) {
                    $classQuery->where(function ($q) use ($selectedMajor) {
                        $q->where('class_name', 'like', "% {$selectedMajor} %")
                          ->orWhere('class_name', 'like', "% {$selectedMajor}");
                    });
                }
                if ($selectedRombel) {
                    $classQuery->where('class_name', 'like', "% {$selectedRombel}");
                }

                $matchedClasses = $classQuery->get();
                if ($matchedClasses->isNotEmpty()) {
                    $targetClassIds = $matchedClasses->pluck('id')->toArray();
                    $activeClass = ($matchedClasses->count() === 1) ? $matchedClasses->first() : null;
                } else {
                    $firstClass = $classes->first();
                    $targetClassIds = $firstClass ? [$firstClass->id] : [];
                    $activeClass = $firstClass;
                }
            }
        } else {
            // Waka Kurikulum memiliki akses menyeluruh ke seluruh kelas
            $classes = \App\Models\SchoolClass::orderBy('class_name')->get();

            $classQuery = \App\Models\SchoolClass::query();
            if ($selectedGrade) {
                $classQuery->where('class_name', 'like', "{$selectedGrade} %");
            }
            if ($selectedMajor) {
                $classQuery->where(function ($q) use ($selectedMajor) {
                    $q->where('class_name', 'like', "% {$selectedMajor} %")
                      ->orWhere('class_name', 'like', "% {$selectedMajor}");
                });
            }
            if ($selectedRombel) {
                $classQuery->where('class_name', 'like', "% {$selectedRombel}");
            }

            // Jika belum ada filter yang dipilih, otomatis arahkan ke kelas pertama
            if (!$selectedGrade && !$selectedMajor && !$selectedRombel && $classes->isNotEmpty()) {
                $firstClass = $classes->first();
                $targetClassIds = [$firstClass->id];
                $activeClass = $firstClass;
                $selectedGrade = $firstClass->grade;
                $selectedMajor = $firstClass->major_code;
                $selectedRombel = $firstClass->rombel;
            } else {
                $matchedClasses = $classQuery->get();
                $targetClassIds = $matchedClasses->pluck('id')->toArray();
                $activeClass = ($matchedClasses->count() === 1) ? $matchedClasses->first() : null;
            }
        }

        $studentsAttendance = collect();
        if (!empty($targetClassIds)) {
            $students = \App\Models\User::whereIn('class_id', $targetClassIds)
                ->whereHas('role', fn ($q) => $q->where('role_name', 'Siswa'))
                ->with('schoolClass')
                ->orderBy('name')
                ->get();

            $studentsAttendance = $students->map(function ($student) {
                $attendances = Attendance::where('student_id', $student->id)->get();
                $totalSessions = $attendances->count();
                $hadir = $attendances->where('status', 'Hadir')->count();
                $sakit = $attendances->where('status', 'Sakit')->count();
                $izin = $attendances->where('status', 'Izin')->count();
                $alpa = $attendances->where('status', 'Alpa')->count();
                $rate = $totalSessions > 0 ? round(($hadir / $totalSessions) * 100) : 100;

                // Status Disiplin Siswa
                if ($alpa >= 3 || ($totalSessions >= 5 && $rate < 75)) {
                    $disciplineStatus = 'Kritis'; // Perlu Panggilan BK
                } elseif ($alpa >= 1 || ($totalSessions >= 5 && $rate < 85)) {
                    $disciplineStatus = 'Perhatian';
                } else {
                    $disciplineStatus = 'Tertib';
                }

                return (object)[
                    'id'            => $student->id,
                    'name'          => $student->name,
                    'nis'           => $student->nis ?? '-',
                    'class_name'    => $student->schoolClass?->class_name ?? '-',
                    'total_sessions'=> $totalSessions,
                    'hadir'         => $hadir,
                    'sakit'         => $sakit,
                    'izin'          => $izin,
                    'alpa'          => $alpa,
                    'rate'          => $rate,
                    'status'        => $disciplineStatus,
                ];
            });
        }

        return view('student-affairs.attendance', compact('classes', 'majors', 'activeClass', 'studentsAttendance', 'selectedGrade', 'selectedMajor', 'selectedRombel'));
    })->name('student-affairs.attendance'); 
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

        $rules = [
            'leave_type'        => ['required', 'in:Sakit,Izin,Potong Jam Pelajaran,Dispensasi'],
            'reason'            => ['required', 'string', 'max:255'],
            'document'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'document_base64'   => ['nullable', 'string'],
        ];

        if ($request->leave_type === 'Potong Jam Pelajaran') {
            $rules['single_date'] = ['required', 'date'];
            $rules['start_time']  = ['required', 'string'];
            $rules['end_time']    = ['required', 'string', 'gte:start_time'];
            $validated = $request->validate($rules, [
                'end_time.gte' => 'Sampai jam pelajaran harus sama atau setelah jam mulai.',
            ]);

            $startDate = $validated['single_date'];
            $endDate   = $validated['single_date'];
            $startTime = $validated['start_time'];
            $endTime   = $validated['end_time'];
        }
        else {
            $rules['start_date'] = ['required', 'date'];
            $rules['end_date']   = ['required', 'date', 'after_or_equal:start_date'];
            $validated = $request->validate($rules);

            $startDate = $validated['start_date'];
            $endDate   = $validated['end_date'];
            $startTime = null;
            $endTime   = null;
        }

        // Simpan foto bukti izin dari kamera real-time
        $docFile = $request->file('document');
        $docBase64 = $request->input('document_base64');

        if (!$docFile && !$docBase64) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['document' => 'Wajib mengambil foto surat bukti izin secara real-time dari kamera.']);
        }

        if ($docFile) {
            $docPath = $docFile->store('leave_documents', 'public');
        } else {
            $imgData = preg_replace('/^data:image\/\w+;base64,/', '', $docBase64);
            $imgDecoded = base64_decode($imgData);
            $filename = 'leave_documents/' . \Illuminate\Support\Str::random(40) . '.jpg';
            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $imgDecoded);
            $docPath = $filename;
        }

        LeaveRequest::create([
            'student_id'      => $studentId,
            'applicant_id'    => $user->id,
            'leave_type'      => $validated['leave_type'],
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'start_time'      => $startTime,
            'end_time'        => $endTime,
            'reason'          => $validated['reason'],
            'document_path'   => $docPath,
            'status_approval' => 'Pending',
        ]);

        return redirect()->route('family.leaves.history')
            ->with('success', 'Permohonan izin berhasil diajukan dan sedang menunggu verifikasi.');
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

        // Halaman Jadwal Pembelajaran Kelas Siswa
    Route::get('/family/schedules', function (Request $request) {
        $user = $request->user();
        $studentId = $user->role?->role_name === 'Siswa' ? $user->id : ($user->children()->first()?->id ?? $user->id);
        $student = \App\Models\User::with('schoolClass')->find($studentId) ?? $user;

        $configuration = \App\Models\SystemConfiguration::current();
        $todayName = \App\Support\IndonesianDay::from(now());

        $query = \App\Models\Schedule::with(['teacher', 'room', 'schoolClass'])
            ->where('class_id', $student->class_id)
            ->where('cycle_type', $configuration->active_cycle)
            ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 END")
            ->orderBy('start_time');

        // Filter Hari jika dipilih
        $selectedDay = $request->query('day', 'Semua');
        if ($selectedDay !== 'Semua') {
            $query->where('day_of_week', $selectedDay);
        }

        $schedules = $query->get();

        return view('family.schedules.index', compact('schedules', 'student', 'configuration', 'todayName', 'selectedDay'));
    })->name('family.schedules.index');

});


require __DIR__.'/auth.php';
