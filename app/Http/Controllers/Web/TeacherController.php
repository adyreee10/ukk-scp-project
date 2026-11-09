<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\JournalReminderService;
use App\Support\IndonesianDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    /**
     * Dashboard Utama Guru (Fokus pada Aktivitas Hari Ini & Riwayat Jurnal)
     */
    public function dashboard(JournalReminderService $service)
    {
        $configuration = SystemConfiguration::current();
        $user = auth()->user();
        $todayName = IndonesianDay::from(now());

        // Ambil semua jadwal guru di putaran aktif
        $allSchedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->where('cycle_type', $configuration->active_cycle)
            ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 END")
            ->orderBy('start_time')
            ->get();

        // Filter jadwal HANYA untuk hari ini
        $todaySchedules = $allSchedules->filter(fn($s) => $s->day_of_week === $todayName);

        // Cek ID jadwal yang sudah diisi jurnalnya pada hari ini
        $filledScheduleIdsToday = Journal::whereDate('date', now()->toDateString())
            ->whereIn('schedule_id', $todaySchedules->pluck('id'))
            ->pluck('schedule_id')
            ->toArray();

        // Deteksi keterlambatan jurnal
        $overdueSchedules = $service->overdueFor($user);

        // Riwayat 5 jurnal KBM terakhir yang telah diisi oleh guru ini
        $recentJournals = Journal::with(['schedule.schoolClass', 'schedule.room', 'attendances'])
            ->whereHas('schedule', fn($q) => $q->where('teacher_id', $user->id))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $schedules = $todaySchedules;

        return view('dashboards.teacher', compact(
            'schedules',
            'allSchedules', 
            'todaySchedules', 
            'todayName', 
            'configuration', 
            'overdueSchedules', 
            'recentJournals',
            'filledScheduleIdsToday'
        ));
    }

    /**
     * Halaman Khusus: Jadwal Mengajar Mingguan Lengkap
     */
    public function schedulesIndex(Request $request)
    {
        $configuration = SystemConfiguration::current();
        $user = auth()->user();

        $query = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->where('cycle_type', $configuration->active_cycle)
            ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 END")
            ->orderBy('start_time');

        // Filter hari jika dipilih oleh guru
        if ($request->filled('day') && $request->day !== 'Semua') {
            $query->where('day_of_week', $request->day);
        }

        $schedules = $query->get();

        return view('teacher.schedules.index', compact('schedules', 'configuration'));
    }

    /**
     * Halaman Formulir Isi Jurnal & Absensi Siswa
     */
    public function createJournal(Request $request)
    {
        $configuration = SystemConfiguration::current();
        $user = auth()->user();
        $today = now()->toDateString();
        
        // Deteksi hari saat ini (dapat disimulasikan via ?day= jika dibutuhkan untuk pengetesan)
        $todayName = $request->query('day', IndonesianDay::from(now()));

        // Ambil semua jadwal guru di putaran aktif
        $allSchedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->where('cycle_type', $configuration->active_cycle)
            ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 END")
            ->orderBy('start_time')
            ->get();

        // 1. Sistem otomatis menyesuaikan hari: ambil jadwal yang HANYA cocok dengan hari hari ini ($todayName)
        $todaySchedules = $allSchedules->filter(fn($s) => $s->day_of_week === $todayName)->values();
        $isEffectiveDayToday = $todaySchedules->isNotEmpty();

        // Jadwal yang diproses: jika hari ini ada jadwal mengajar, sistem gunakan jadwal hari ini.
        // Jika hari ini libur KBM (misal Minggu saat pengetesan), sistem otomatis mengambil jadwal mengajar pertama guru tersebut.
        $schedules = $isEffectiveDayToday 
            ? $todaySchedules 
            : ($allSchedules->isNotEmpty() ? collect([$allSchedules->first()]) : collect());

        // Cek ID jadwal yang sudah diisi jurnalnya pada hari ini
        $filledScheduleIdsToday = Journal::whereDate('date', $today)
            ->whereIn('schedule_id', $schedules->pluck('id'))
            ->pluck('schedule_id')
            ->toArray();

        // 2. Pemilihan jadwal dilakukan 100% otomatis oleh sistem (atau berdasarkan request schedule_id jika valid):
        if ($request->filled('schedule_id')) {
            $selectedSchedule = $schedules->firstWhere('id', (int) $request->schedule_id)
                ?? $allSchedules->firstWhere('id', (int) $request->schedule_id);
        } else {
            // Cek apakah ada sesi KBM yang sedang berlangsung saat ini (sesuai jam pelajaran real-time)
            $currentTime = now()->format('H:i:s');
            $ongoingSchedule = $schedules->first(function ($s) use ($currentTime) {
                return $currentTime >= $s->real_start_time && $currentTime <= $s->real_end_time;
            });

            if ($ongoingSchedule && !in_array($ongoingSchedule->id, $filledScheduleIdsToday)) {
                $selectedSchedule = $ongoingSchedule;
            } else {
                $selectedSchedule = $schedules->first(fn($s) => !in_array($s->id, $filledScheduleIdsToday)) 
                    ?? $schedules->last();
            }
        }

        // Cek apakah jadwal ini sudah memiliki rekaman jurnal pada hari ini
        $existingJournalToday = null;
        $isEditableWithin24Hours = false;
        if ($selectedSchedule) {
            $existingJournalToday = Journal::with(['attendances.student'])
                ->where('schedule_id', $selectedSchedule->id)
                ->whereDate('date', $today)
                ->latest('id')
                ->first();

            if ($existingJournalToday) {
                // Periksa apakah masih dalam toleransi hari KBM (bisa edit presensi hingga pukul 23:59 malam ini)
                $isEditableWithin24Hours = Carbon::parse($existingJournalToday->date)->isToday();
            } else {
                // VALIDASI WAKTU MENGAJAR: Guru hanya bisa isi jurnal jika sudah memasuki jam mengajar
                // Jika waktu saat ini belum mencapai jam mulai mengajar, sistem langsung menolak!
                if ($selectedSchedule->day_of_week !== $todayName) {
                    return redirect()->route('teacher.dashboard')
                        ->with('error', "Belum jadwal mengajar! Sesi pembelajaran {$selectedSchedule->subject} berada di hari {$selectedSchedule->day_of_week}.");
                }

                if (!$selectedSchedule->isTeachingTimeStarted()) {
                    $startTimeFormatted = substr($selectedSchedule->real_start_time, 0, 5);
                    return redirect()->route('teacher.dashboard')
                        ->with('error', "Belum waktunya mengisi jurnal! Sesi pembelajaran {$selectedSchedule->subject} baru dimulai pada pukul {$startTimeFormatted} WIB. Pengisian jurnal dan presensi hanya dapat dilakukan saat jam mengajar Anda telah dimulai.");
                }
            }
        }

        $students = collect();
        $approvedLeavesToday = collect();

        if ($selectedSchedule && $selectedSchedule->class_id) {
            $students = User::where('class_id', $selectedSchedule->class_id)
                ->whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
                ->where('student_status', 'Aktif')
                ->orderBy('name')
                ->get();

            // Ambil data izin siswa yang telah disetujui (Approved) untuk kelas dan hari ini
            $approvedLeavesToday = LeaveRequest::approvedOn($today)
                ->whereIn('student_id', $students->pluck('id'))
                ->latest('updated_at')
                ->get()
                ->unique('student_id')
                ->keyBy('student_id');
        }

        return view('teacher.journals.create', compact(
            'schedules', 
            'selectedSchedule', 
            'students', 
            'configuration',
            'filledScheduleIdsToday',
            'existingJournalToday',
            'isEditableWithin24Hours',
            'approvedLeavesToday',
            'isEffectiveDayToday',
            'todayName'
        ));
    }

    /**
     * Simpan Jurnal & Presensi Siswa (Hanya Berlaku di Hari KBM / Tidak Boleh Isi Hari yang Lalu)
     */
    public function storeJournal(Request $request, AttendanceService $attendanceService)
    {
        $validated = $request->validate([
            'schedule_id'       => ['required', 'exists:schedules,id'],
            'date'              => ['required', 'date'],
            'topic_description' => ['required', 'string', 'max:5000'],
            'photo'             => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photo_base64'      => ['nullable', 'string'],
            'attendances'       => ['nullable', 'array'],
            'attendance_notes'  => ['nullable', 'array'],
        ]);

        // Guru tidak bisa mengisi jurnal yang sudah berlalu (waktu pengisian hanya 24 jam pada hari KBM)
        $todayDate = now()->toDateString();
        $date = Carbon::parse($validated['date'])->startOfDay();
        if ($date->toDateString() !== $todayDate) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal: Guru tidak dapat mengisi jurnal untuk tanggal yang sudah berlalu. Pengisian jurnal hanya dapat dilakukan pada hari KBM berlangsung.');
        }

        $photoFile = $request->file('photo');
        $photoBase64 = $request->input('photo_base64');

        if (!$photoFile && !$photoBase64) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['photo' => 'Wajib mengambil foto bukti KBM secara real-time dari kamera.']);
        }

        $schedule = Schedule::where('teacher_id', auth()->id())->findOrFail($validated['schedule_id']);
        $todayName = IndonesianDay::from(now());

        // Validasi 1: Hari mengajar harus sesuai hari ini
        if ($schedule->day_of_week !== $todayName) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Gagal: Jadwal mengajar untuk mata pelajaran ini adalah hari {$schedule->day_of_week}. Anda hanya dapat mengisi jurnal pada hari jadwal berlangsung.");
        }

        // Validasi 2: Guru hanya bisa isi jurnal dan absensi jika sudah waktu ia mengajar
        // Jika belum jam mengajar, sistem langsung menolak!
        if (!$schedule->isTeachingTimeStarted()) {
            $startTimeFormatted = substr($schedule->real_start_time, 0, 5);
            return redirect()->back()
                ->withInput()
                ->with('error', "Gagal: Belum waktunya mengisi jurnal! Sesi pembelajaran baru dimulai pada pukul {$startTimeFormatted} WIB. Pengisian jurnal hanya dapat dilakukan saat jam mengajar telah dimulai.");
        }

        $date = Carbon::parse($validated['date'])->startOfDay();

        if (Journal::where('schedule_id', $schedule->id)->whereDate('date', $date)->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Jurnal pembelajaran untuk kelas dan tanggal ini sudah pernah diisi sebelumnya.');
        }

        if ($photoFile) {
            $photoPath = $photoFile->store('journals', 'public');
        } else {
            $imgData = preg_replace('/^data:image\/\w+;base64,/', '', $photoBase64);
            $imgDecoded = base64_decode($imgData);
            $filename = 'journals/' . Str::random(40) . '.jpg';
            Storage::disk('public')->put($filename, $imgDecoded);
            $photoPath = $filename;
        }

        $notes = $validated['attendance_notes'] ?? $request->input('attendance_notes', []);

        try {
            DB::transaction(function () use ($schedule, $date, $validated, $photoPath, $attendanceService, $notes, $request) {
                $journal = Journal::create([
                    'schedule_id'       => $schedule->id,
                    'date'              => $date->toDateString(),
                    'topic_description' => $validated['topic_description'],
                    'photo_path'        => $photoPath,
                ]);

                $attendanceService->syncApprovedLeaves($journal);

                if (!empty($validated['attendances'])) {
                    $rows = [];
                    foreach ($validated['attendances'] as $studentId => $status) {
                        $rows[] = [
                            'student_id' => (int) $studentId,
                            'status'     => $status,
                            'note'       => !empty($notes[$studentId]) ? trim($notes[$studentId]) : null,
                        ];
                    }
                    $attendanceService->saveBatch($journal, $rows);
                }

            });
        } catch (\Throwable $e) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            throw $e;
        }

        return redirect()->route('teacher.dashboard')
            ->with('success', 'Jurnal pembelajaran dan presensi siswa berhasil disimpan ke sistem!');
    }

    /**
     * Perbarui Presensi Siswa pada Jurnal KBM (Hanya Bisa dalam Batas Waktu 24 Jam)
     */
    public function updateJournalAttendance(Request $request, Journal $journal, AttendanceService $attendanceService)
    {
        // Pastikan hanya guru pemilik jadwal yang dapat mengedit
        if ($journal->schedule->teacher_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengedit jurnal ini.');
        }

        // Cek batasan waktu: hanya bisa edit di hari KBM hingga pukul 23:59
        if (!Carbon::parse($journal->date)->isToday()) {
            return redirect()->back()
                ->with('error', 'Batas waktu pengeditan presensi siswa pada jurnal ini telah berakhir pada pukul 23:59 di hari KBM. Data presensi telah terkunci permanen.');
        }

        $validated = $request->validate([
            'attendances'      => ['required', 'array'],
            'attendance_notes' => ['nullable', 'array'],
        ]);

        $rows = [];
        $notes = $request->input('attendance_notes', []);
        foreach ($validated['attendances'] as $studentId => $status) {
            $rows[] = [
                'student_id' => (int) $studentId,
                'status'     => $status,
                'note'       => !empty($notes[$studentId]) ? trim($notes[$studentId]) : null,
            ];
        }

        $attendanceService->saveBatch($journal, $rows);

        return redirect()->back()->with('success', 'Presensi siswa berhasil diperbarui! (Masih dalam batas waktu 24 jam)');
    }
        /**
     * Halaman Riwayat Lengkap Jurnal KBM Guru & Deteksi Jurnal yang Belum Diisi
     * (Menampilkan Indikator Hijau "Mengisi Jurnal" & Merah "Tidak Mengisi Jurnal")
     */
    public function journalsHistory(Request $request, JournalReminderService $reminderService)
    {
        $user = auth()->user();
        $configuration = SystemConfiguration::current();

        // Filter bulan (default: bulan saat ini YYYY-MM)
        $monthInput = $request->get('month', now()->format('Y-m'));
        $classId = $request->get('class_id');
        $statusFilter = $request->get('status', 'all'); // 'all', 'filled', 'unfilled'

        $historyList = collect();

        // Tentukan batas tanggal pencarian
        if ($monthInput && $monthInput !== 'all') {
            $parsedMonth = Carbon::parse($monthInput);
            $startDate = $parsedMonth->copy()->startOfMonth();
            $endDate = $parsedMonth->copy()->endOfMonth();
        } else {
            $startDate = now()->subDays(60)->startOfDay();
            $endDate = now()->endOfDay();
        }

        $today = now()->startOfDay();
        $scanEndDate = $endDate->greaterThan($today) ? $today : $endDate;

        // 1. Ambil jurnal yang sudah diisi guru pada rentang ini (Indikator Hijau: Mengisi Jurnal)
        if ($statusFilter !== 'unfilled') {
            $journalsQuery = Journal::with(['schedule.schoolClass', 'schedule.room', 'attendances'])
                ->whereHas('schedule', fn($q) => $q->where('teacher_id', $user->id))
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);

            if ($classId) {
                $journalsQuery->whereHas('schedule', fn($q) => $q->where('class_id', $classId));
            }

            $filledJournals = $journalsQuery->orderByDesc('date')->get();

            foreach ($filledJournals as $j) {
                $historyList->push((object)[
                    'id'                => $j->id,
                    'date'              => $j->date->toDateString(),
                    'schedule'          => $j->schedule,
                    'is_filled'         => true,
                    'status_label'      => 'Mengisi Jurnal',
                    'status_color'      => 'emerald',
                    'topic_description' => $j->topic_description,
                    'photo_path'        => $j->photo_path,
                    'attendances'       => $j->attendances,
                    'created_at'        => $j->created_at,
                    'start_time'        => $j->schedule?->start_time ?? '00:00:00',
                ]);
            }
        } else {
            $filledJournals = collect();
        }

        // Ambil semua kunci jurnal yang sudah diisi untuk perbandingan cepat
        $allFilledKeys = Journal::whereHas('schedule', fn($q) => $q->where('teacher_id', $user->id))
            ->whereBetween('date', [$startDate->toDateString(), $scanEndDate->toDateString()])
            ->get()
            ->map(fn($j) => $j->schedule_id . '_' . $j->date->toDateString())
            ->flip();

        // 2. Deteksi jadwal KBM yang telah lewat batas 24 jam dan TIDAK diisi (Indikator Merah: Tidak Mengisi Jurnal)
        if ($statusFilter !== 'filled') {
            $schedulesQuery = Schedule::with(['schoolClass', 'room'])
                ->where('teacher_id', $user->id)
                ->where('cycle_type', $configuration->active_cycle);

            if ($classId) {
                $schedulesQuery->where('class_id', $classId);
            }

            $teacherSchedules = $schedulesQuery->get();

            $daysMap = [
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                7 => 'Minggu',
            ];

            // Pengoperasian riwayat dimulai efektif per hari ini (2026-11-09)
            // Seluruh tanggal sebelum hari ini tidak dicatat sebagai "Tidak Mengisi Jurnal" (Riwayat bersih)
            $systemOperationStartDate = Carbon::parse('2026-11-09')->startOfDay();
            $effectiveScanStart = $startDate->greaterThanOrEqualTo($systemOperationStartDate) ? $startDate : $systemOperationStartDate;
            
            // Hari ini masih berjalan aktif dan memiliki masa pengisian hingga pukul 23:59 malam ini,
            // sehingga status "Tidak Mengisi Jurnal" hanya berlaku bagi hari KBM yang telah berakhir (kemarin ke belakang)
            $yesterday = now()->subDay()->endOfDay();
            $effectiveScanEnd = $scanEndDate->lessThan($yesterday) ? $scanEndDate : $yesterday;

            if ($effectiveScanStart->lessThanOrEqualTo($effectiveScanEnd)) {
                $period = \Carbon\CarbonPeriod::create($effectiveScanStart, $effectiveScanEnd);
                foreach ($period as $date) {
                    $dayName = $daysMap[$date->dayOfWeekIso] ?? '';
                    if (in_array($dayName, ['Sabtu', 'Minggu'])) {
                        continue; // Akhir pekan libur KBM
                    }

                    $schedulesForDay = $teacherSchedules->where('day_of_week', $dayName);
                    foreach ($schedulesForDay as $schedule) {
                        $key = $schedule->id . '_' . $date->toDateString();
                        if ($allFilledKeys->has($key)) {
                            continue; // Sudah diisi
                        }

                        $sessionEnd = Carbon::parse($date->toDateString() . ' ' . $schedule->real_end_time);

                        $historyList->push((object)[
                            'id'                => null,
                            'date'              => $date->toDateString(),
                            'schedule'          => $schedule,
                            'is_filled'         => false,
                            'status_label'      => 'Tidak Mengisi Jurnal',
                            'status_color'      => 'rose',
                            'topic_description' => 'Tidak mengisi jurnal pembelajaran (Melewati batas toleransi 23:59)',
                            'photo_path'        => null,
                            'attendances'       => collect(),
                            'created_at'        => $sessionEnd,
                            'start_time'        => $schedule->real_start_time ?? '00:00:00',
                        ]);
                    }
                }
            }
        }

        // Urutkan riwayat secara kronologis mundur (terbaru di atas)
        $sortedHistory = $historyList->sort(function ($a, $b) {
            if ($a->date === $b->date) {
                return strcmp($b->start_time, $a->start_time);
            }
            return strcmp($b->date, $a->date);
        })->values();

        // Paginasi 15 item per halaman
        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pagedData = $sortedHistory->slice(($page - 1) * $perPage, $perPage)->values();
        $journals = new LengthAwarePaginator($pagedData, $sortedHistory->count(), $perPage, $page, [
            'path'  => LengthAwarePaginator::resolveCurrentPath(),
            'query' => $request->query(),
        ]);

        // Ambil daftar kelas yang diajar guru ini untuk opsi dropdown filter
        $myClasses = \App\Models\SchoolClass::whereHas('schedules', fn($q) => $q->where('teacher_id', $user->id))
            ->orderBy('class_name')
            ->get();

        // Deteksi jadwal yang hari ini terlambat / belum diisi
        $overdueSchedules = $reminderService->overdueFor($user);

        return view('teacher.journals.history', compact('journals', 'myClasses', 'overdueSchedules', 'configuration', 'monthInput', 'statusFilter'));
    }

}
