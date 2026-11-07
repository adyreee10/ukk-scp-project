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

        $schedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->where('cycle_type', $configuration->active_cycle)
            ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 END")
            ->orderBy('start_time')
            ->get();

        $filledScheduleIdsToday = Journal::whereDate('date', $today)
            ->whereIn('schedule_id', $schedules->pluck('id'))
            ->pluck('schedule_id')
            ->toArray();

        $selectedScheduleId = $request->query('schedule_id', $schedules->first()?->id);
        $selectedSchedule = $schedules->firstWhere('id', $selectedScheduleId) ?? $schedules->first();

        // Cek apakah jadwal ini sudah memiliki rekaman jurnal pada hari ini
        $existingJournalToday = null;
        if ($selectedSchedule) {
            $existingJournalToday = Journal::with(['attendances.student'])
                ->where('schedule_id', $selectedSchedule->id)
                ->whereDate('date', $today)
                ->first();
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
            'approvedLeavesToday'
        ));
    }

    /**
     * Simpan Jurnal & Presensi Siswa
     */
    public function storeJournal(Request $request, AttendanceService $attendanceService)
    {
        $validated = $request->validate([
            'schedule_id'       => ['required', 'exists:schedules,id'],
            'date'              => ['required', 'date', 'before_or_equal:today'],
            'topic_description' => ['required', 'string', 'max:5000'],
            'photo'             => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photo_base64'      => ['nullable', 'string'],
            'attendances'       => ['nullable', 'array'],
            'attendance_notes'  => ['nullable', 'array'],
        ]);

        $photoFile = $request->file('photo');
        $photoBase64 = $request->input('photo_base64');

        if (!$photoFile && !$photoBase64) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['photo' => 'Wajib mengambil foto bukti KBM secara real-time dari kamera.']);
        }

        $schedule = Schedule::where('teacher_id', auth()->id())->findOrFail($validated['schedule_id']);
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

        DB::transaction(function () use ($schedule, $date, $validated, $photoPath, $attendanceService) {
            $journal = Journal::create([
                'schedule_id'       => $schedule->id,
                'date'              => $date->toDateString(),
                'topic_description' => $validated['topic_description'],
                'photo_path'        => $photoPath,
            ]);

            $attendanceService->syncApprovedLeaves($journal);

            if (!empty($validated['attendances'])) {
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
            }

        });

        return redirect()->route('teacher.dashboard')
            ->with('success', 'Jurnal pembelajaran dan presensi siswa berhasil disimpan ke sistem!');
    }
        /**
     * Halaman Riwayat Lengkap Jurnal KBM Guru & Deteksi Jurnal yang Belum Diisi
     */
    public function journalsHistory(Request $request, JournalReminderService $reminderService)
    {
        $user = auth()->user();
        $configuration = SystemConfiguration::current();

        $query = Journal::with(['schedule.schoolClass', 'schedule.room', 'attendances'])
            ->whereHas('schedule', fn($q) => $q->where('teacher_id', $user->id));

        // Filter berdasarkan Bulan (jika dipilih)
        if ($request->filled('month')) {
            $month = Carbon::parse($request->month);
            $query->whereYear('date', $month->year)->whereMonth('date', $month->month);
        }

        // Filter berdasarkan Kelas yang diampu
        if ($request->filled('class_id')) {
            $classId = $request->class_id;
            $query->whereHas('schedule', fn($q) => $q->where('class_id', $classId));
        }

        $journals = $query->orderByDesc('date')->orderByDesc('id')->paginate(15)->withQueryString();

        // Ambil daftar kelas yang diajar guru ini untuk opsi dropdown filter
        $myClasses = \App\Models\SchoolClass::whereHas('schedules', fn($q) => $q->where('teacher_id', $user->id))
            ->orderBy('class_name')
            ->get();

        // Deteksi jadwal yang hari ini terlambat / belum diisi
        $overdueSchedules = $reminderService->overdueFor($user);

        return view('teacher.journals.history', compact('journals', 'myClasses', 'overdueSchedules', 'configuration'));
    }

}
