<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\JournalReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Major;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Schema;
use App\Models\LessonHour;
use App\Services\SpreadsheetImportService;
use App\Services\FetTimetableParserService;
use App\Services\SchoolDocumentParserService;
use Illuminate\Support\Str;



class WakaController extends Controller
{
    /**
     * Dashboard Waka Kurikulum
     */
    public function dashboard(JournalReminderService $service)
    {
        $configuration = SystemConfiguration::first() ?? SystemConfiguration::create([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        $lateTeachers = $service->overdueFor();

        // 1. Total Seluruh Guru (Guru Pengajar, Wali Kelas, Guru BK)
        $teacherCount = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))->count();

        // 2. Total Siswa Aktif
        $studentCount = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))->count();

        // 3. Total Ruangan Sekolah
        $roomCount = Room::count();

        // 4. Total Rombel / Kelas
        $classCount = SchoolClass::count();

        // 5. Total Mata Pelajaran Terdaftar
        $subjectCount = Schedule::whereNotNull('subject')->where('subject', '!=', '')->distinct()->count('subject');

        // 6. Jurnal KBM yang Harus Disubmit Hari Ini & Realisasi
        $activeCycle = $configuration->active_cycle ?? 'Putaran 1';
        $todayDate   = now()->toDateString();
        $dayName     = \App\Support\IndonesianDay::from(now());

        $todaySchedulesQuery = Schedule::where('cycle_type', $activeCycle)->where('day_of_week', $dayName);
        $targetJournalsToday = (clone $todaySchedulesQuery)->count();
        $filledJournalsToday = (clone $todaySchedulesQuery)->whereHas('journals', fn($q) => $q->whereDate('date', $todayDate))->count();

        $isWeekend = in_array($dayName, ['Sabtu', 'Minggu']);
        $mondayTarget = $isWeekend ? Schedule::where('cycle_type', $activeCycle)->where('day_of_week', 'Senin')->count() : $targetJournalsToday;

        return view('dashboards.waka', compact(
            'configuration',
            'lateTeachers',
            'teacherCount',
            'studentCount',
            'roomCount',
            'classCount',
            'subjectCount',
            'targetJournalsToday',
            'filledJournalsToday',
            'dayName',
            'isWeekend',
            'mondayTarget'
        ));
    }

    /**
     * Halaman Khusus Pengelolaan Siklus Blok & Kuncian Jadwal
     */
    public function cycleIndex()
    {
        $configuration = SystemConfiguration::first() ?? SystemConfiguration::create([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        return view('waka.cycle.index', compact('configuration'));
    }

    /**
     * Toggle lock jadwal
     */
    public function toggleLock()
    {
        $config = SystemConfiguration::first();
        if ($config) {
            $config->update([
                'is_schedule_locked' => !$config->is_schedule_locked,
                'updated_by' => auth()->id(),
            ]);
            $status = $config->is_schedule_locked ? 'dikunci' : 'dibuka';
            return redirect()->back()->with('success', "Status jadwal berhasil $status!");
        }

        return redirect()->back()->with('error', 'Konfigurasi sistem tidak ditemukan.');
    }

    /**
     * Toggle putaran aktif
     */
    public function toggleCycle()
    {
        $config = SystemConfiguration::first();
        if ($config) {
            // Validasi: Jika jadwal terkunci, tidak boleh mengubah putaran
            if ($config->is_schedule_locked) {
                return redirect()->back()->with('error', 'Gagal mengubah putaran: Siklus dan jadwal saat ini sedang TERKUNCI. Silakan buka kunci jadwal terlebih dahulu.');
            }

            $newCycle = $config->active_cycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1';
            $config->update([
                'active_cycle' => $newCycle,
                'updated_by' => auth()->id(),
            ]);
            return redirect()->back()->with('success', "Putaran aktif berhasil diubah menjadi $newCycle!");
        }

        return redirect()->back()->with('error', 'Konfigurasi sistem tidak ditemukan.');
    }


    /**
     * Jadwal Pelajaran (Input Jadwal Mandiri)
     */
    public function schedulesIndex(Request $request)
    {
        $config = SystemConfiguration::current();
        $query = Schedule::with(['teacher', 'schoolClass', 'room']);

        if ($request->filled('cycle_type')) {
            $query->where('cycle_type', $request->cycle_type);
        } else {
            $query->where('cycle_type', $config->active_cycle);
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        // Filter 1: Tingkat Kelas (X, XI, XII)
        if ($request->filled('grade') && $request->grade !== 'all') {
            $gradeMap = ['10' => 'X', '11' => 'XI', '12' => 'XII', 'X' => 'X', 'XI' => 'XI', 'XII' => 'XII'];
            $gradeVal = $gradeMap[$request->grade] ?? $request->grade;
            $query->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', "{$gradeVal} %"));
        }

        // Filter 2: Jurusan
        if ($request->filled('major') && $request->major !== 'all') {
            $major = trim($request->major);
            $query->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', "% {$major} %")->orWhere('class_name', 'like', "% {$major}"));
        }

        // Filter 3: Rombel
        if ($request->filled('rombel') && $request->rombel !== 'all') {
            $rombel = trim($request->rombel);
            $query->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', "% {$rombel}"));
        }

        // Kompatibilitas filter class_id langsung
        if ($request->filled('class_id') && $request->class_id !== 'all') {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $perPage = in_array((int)$request->input('per_page'), [50, 100, 500, 1000]) ? (int)$request->input('per_page') : 50;

        $schedules = $query->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 END")
            ->orderBy('start_time')
            ->paginate($perPage)
            ->withQueryString();

        $teachers = User::with('role')->whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))->orderBy('name')->get();
        $classes = SchoolClass::orderBy('class_name')->get();
        $rooms = Room::orderBy('room_name')->get();

        // Opsi Dropdown Filter 3 Kolom
        $availableGrades = ['X', 'XI', 'XII'];
        $availableMajors = Major::orderBy('code')->pluck('code')->toArray();
        if (empty($availableMajors)) {
            $availableMajors = ['RPL', 'TKJ', 'AKL', 'BD', 'MP'];
        }
        $availableRombels = ['1', '2', '3', '4'];

        return view('waka.schedules.index', compact(
            'schedules', 
            'teachers', 
            'classes', 
            'rooms', 
            'config',
            'availableGrades',
            'availableMajors',
            'availableRombels'
        ));
    }

    public function schedulesStore(Request $request)
    {
        $config = SystemConfiguration::current();
        if ($config->is_schedule_locked) {
            return redirect()->back()->with('error', 'Gagal menambahkan jadwal: Siklus blok saat ini sedang TERKUNCI. Buka kunci jadwal terlebih dahulu di menu Siklus Blok.');
        }

        $validated = $request->validate([
            'teacher_id'  => ['required', 'exists:users,id'],
            'class_id'    => ['required', 'exists:classes,id'],
            'room_id'     => ['required', 'exists:rooms,id'],
            'subject'     => ['required', 'string', 'max:100'],
            'day_of_week' => ['required', 'in:Senin,Selasa,Rabu,Kamis,Jumat'],
            'start_time'  => ['required', 'in:01:00,02:00,03:00,04:00,05:00,06:00,07:00,08:00,09:00,10:00'],
            'end_time'    => ['required', 'in:01:00,02:00,03:00,04:00,05:00,06:00,07:00,08:00,09:00,10:00', 'gte:start_time'],
            'cycle_type'  => ['required', 'in:Putaran 1,Putaran 2'],
        ]);

        // Pembatasan hari Jumat: Maksimal hanya sampai Jam ke - 8
        if ($validated['day_of_week'] === 'Jumat') {
            if ($validated['start_time'] > '08:00' || $validated['end_time'] > '08:00') {
                return redirect()->back()->withInput()->with('error', 'Khusus hari Jumat, jam pelajaran hanya sampai Jam ke - 8.');
            }
        }

        Schedule::create($validated);

        return redirect()->route('waka.schedules.index')->with('success', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    public function schedulesUpdate(Request $request, Schedule $schedule)
    {
        $config = SystemConfiguration::current();
        if ($config->is_schedule_locked) {
            return redirect()->back()->with('error', 'Gagal memperbarui jadwal: Siklus blok saat ini sedang TERKUNCI. Buka kunci jadwal terlebih dahulu untuk mengedit.');
        }

        $validated = $request->validate([
            'teacher_id'  => ['required', 'exists:users,id'],
            'class_id'    => ['required', 'exists:classes,id'],
            'room_id'     => ['required', 'exists:rooms,id'],
            'subject'     => ['required', 'string', 'max:100'],
            'day_of_week' => ['required', 'in:Senin,Selasa,Rabu,Kamis,Jumat'],
            'start_time'  => ['required', 'in:01:00,02:00,03:00,04:00,05:00,06:00,07:00,08:00,09:00,10:00'],
            'end_time'    => ['required', 'in:01:00,02:00,03:00,04:00,05:00,06:00,07:00,08:00,09:00,10:00', 'gte:start_time'],
            'cycle_type'  => ['required', 'in:Putaran 1,Putaran 2'],
        ]);

        // Pembatasan hari Jumat: Maksimal hanya sampai Jam ke - 8
        if ($validated['day_of_week'] === 'Jumat') {
            if ($validated['start_time'] > '08:00' || $validated['end_time'] > '08:00') {
                return redirect()->back()->withInput()->with('error', 'Khusus hari Jumat, jam pelajaran hanya sampai Jam ke - 8.');
            }
        }

        $schedule->update($validated);

        return redirect()->route('waka.schedules.index')->with('success', 'Jadwal pelajaran berhasil diperbarui.');
    }

    public function schedulesDestroy(Schedule $schedule)
    {
        $config = SystemConfiguration::current();
        if ($config->is_schedule_locked) {
            return redirect()->back()->with('error', 'Gagal menghapus jadwal: Siklus blok saat ini sedang TERKUNCI.');
        }

        $schedule->delete();
        return redirect()->route('waka.schedules.index')->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }

    /**
     * Hapus Jadwal Mengajar Terpilih (Bulk Delete)
     */
    public function schedulesDeleteSelected(Request $request)
    {
        $config = SystemConfiguration::current();
        if ($config->is_schedule_locked) {
            return redirect()->back()->with('error', 'Gagal menghapus jadwal: Siklus blok saat ini sedang TERKUNCI.');
        }

        $request->validate([
            'schedule_ids'   => ['required', 'array', 'min:1'],
            'schedule_ids.*' => ['exists:schedules,id'],
        ]);

        $count = Schedule::whereIn('id', $request->schedule_ids)->delete();

        return redirect()->route('waka.schedules.index')
            ->with('success', "Sukses! Berhasil menghapus {$count} jadwal pelajaran yang dipilih.");
    }

    /**
     * Hapus Semua Jadwal Mengajar (Opsional per Putaran / Semua Putaran / Kelas)
     */
    public function schedulesDeleteAll(Request $request)
    {
        $config = SystemConfiguration::current();
        if ($config->is_schedule_locked) {
            return redirect()->back()->with('error', 'Gagal menghapus jadwal: Siklus blok saat ini sedang TERKUNCI.');
        }

        $query = Schedule::query();

        $scope = $request->input('scope', 'cycle_1');
        if ($scope === 'cycle_1' || $scope === 'Putaran 1') {
            $query->where('cycle_type', 'Putaran 1');
            $scopeDesc = "di Putaran 1";
        } elseif ($scope === 'cycle_2' || $scope === 'Putaran 2') {
            $query->where('cycle_type', 'Putaran 2');
            $scopeDesc = "di Putaran 2";
        } elseif ($scope === 'active_cycle') {
            $cycle = $request->input('cycle_type', $config->active_cycle);
            $query->where('cycle_type', $cycle);
            $scopeDesc = "di {$cycle}";
        } else {
            $scopeDesc = "di SELURUH Putaran";
        }

        if ($request->filled('class_id') && $request->class_id !== 'all') {
            $class = SchoolClass::find($request->class_id);
            $query->where('class_id', $request->class_id);
            $scopeDesc .= " untuk Kelas " . ($class ? $class->class_name : $request->class_id);
        }

        $count = $query->delete();

        return redirect()->route('waka.schedules.index')
            ->with('success', "Sukses! Berhasil menghapus {$count} jadwal pelajaran {$scopeDesc}.");
    }

    /**
     * Download Template Excel / CSV Jadwal Mengajar
     */
    public function schedulesTemplate(SpreadsheetImportService $spreadsheetService)
    {
        $headers = ['nama_guru_atau_nip', 'kelas', 'ruang', 'mapel', 'hari', 'jam_mulai', 'jam_selesai', 'putaran'];
        $samples = [
            ['Drs. Budi Santoso, M.Pd', 'X PPLG 1', 'Lab Komputer 1', 'Pemrograman Dasar', 'Senin', '1', '4', 'Putaran 1'],
            ['Siti Rahmawati, S.Kom', 'XI TJKT 2', 'Ruang Teori 101', 'Administrasi Server', 'Selasa', '01:00', '03:00', 'Putaran 1'],
            ['Ahmad Hidayat, S.Pd', 'XII DKV 1', 'Lab Desain 2', 'Desain Grafis Percetakan', 'Jumat', '1', '5', 'Putaran 2'],
        ];

        return $spreadsheetService->downloadTemplate('template_jadwal_mengajar.csv', $headers, $samples);
    }

    /**
     * Import Jadwal Mengajar dari Berkas (PDF FET, HTML FET, Excel, atau CSV)
     * Dilengkapi fitur Auto-Provisioning: otomatis mendaftarkan Guru, Kelas, Jurusan, dan Ruang jika belum ada.
     * Mendukung pengunggahan beberapa berkas sekaligus (Multiple Files).
     */
    public function schedulesImport(Request $request, FetTimetableParserService $parserService)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $config = SystemConfiguration::current();
        if ($config->is_schedule_locked) {
            return redirect()->back()->with('error', 'Gagal mengimpor jadwal: Siklus blok saat ini sedang TERKUNCI. Buka kunci terlebih dahulu di menu Siklus Blok.');
        }

        $request->validate([
            'files'            => ['nullable', 'array'],
            'files.*'          => ['file', 'mimes:pdf,html,htm,csv,txt,xlsx', 'max:25600'],
            'file'             => ['nullable', 'file', 'mimes:pdf,html,htm,csv,txt,xlsx', 'max:25600'],
            'cycle_type'       => ['nullable', 'in:Putaran 1,Putaran 2'],
            'duplicate_action' => ['nullable', 'in:replace,keep'],
        ]);

        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            $uploadedFiles = (array) $request->file('files');
        } elseif ($request->hasFile('file')) {
            $uploadedFiles = [$request->file('file')];
        }

        if (empty($uploadedFiles)) {
            return redirect()->back()->with('error', 'Silakan pilih setidaknya satu file dokumen jadwal.');
        }

        // Urutkan berkas: pastikan berkas Jadwal Kelas diproses sebelum Jadwal Ruang (Room Assignments)
        usort($uploadedFiles, function ($a, $b) {
            $aName = method_exists($a, 'getClientOriginalName') ? $a->getClientOriginalName() : (string) $a;
            $bName = method_exists($b, 'getClientOriginalName') ? $b->getClientOriginalName() : (string) $b;
            $aIsRoom = str_contains(strtoupper($aName), 'RUANG');
            $bIsRoom = str_contains(strtoupper($bName), 'RUANG');
            return $aIsRoom <=> $bIsRoom;
        });

        $cycleType = $request->input('cycle_type', $config->active_cycle ?? 'Putaran 1');
        $duplicateAction = $request->input('duplicate_action', 'replace');
        if (!in_array($duplicateAction, ['replace', 'keep'])) {
            $duplicateAction = 'replace';
        }

        $totalImported  = 0;
        $totalSkipped   = 0;
        $totalTeachers  = 0;
        $totalClasses   = 0;
        $totalRooms     = 0;
        $processedFiles = 0;
        $errors = [];

        foreach ($uploadedFiles as $file) {
            try {
                $result = $parserService->import($file, $cycleType, $duplicateAction);
                $totalImported += $result['imported'] ?? 0;
                $totalSkipped  += $result['skipped'] ?? 0;
                $totalTeachers += $result['created_teachers'] ?? 0;
                $totalClasses  += $result['created_classes'] ?? 0;
                $totalRooms    += $result['created_rooms'] ?? 0;
                $processedFiles++;
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        if ($processedFiles === 0 && !empty($errors)) {
            return redirect()->back()->with('error', 'Gagal memproses seluruh berkas jadwal: ' . implode('; ', $errors));
        }

        $msg = "Sukses! Berhasil memproses {$processedFiles} berkas dokumen jadwal: {$totalImported} jadwal pelajaran berhasil diimpor ke {$cycleType}";
        if ($totalSkipped > 0) {
            $msg .= " ({$totalSkipped} jadwal lama dipertahankan)";
        }
        $msg .= ".";

        $details = [];
        if ($totalTeachers > 0) {
            $details[] = "{$totalTeachers} akun guru baru";
        }
        if ($totalClasses > 0) {
            $details[] = "{$totalClasses} kelas & jurusan baru";
        }
        if ($totalRooms > 0) {
            $details[] = "{$totalRooms} ruangan/lab baru";
        }

        if (!empty($details)) {
            $msg .= " Sistem otomatis melakukan auto-provisioning menambahkan: " . implode(', ', $details) . ".";
        }

        if (!empty($errors)) {
            $msg .= " Catatan peringatan (" . count($errors) . " file dilewati): " . implode('; ', $errors);
        }

        return redirect()->route('waka.schedules.index')->with('success', $msg);
    }


    /**
     * Master Data: Guru
     */
        public function teachersIndex(Request $request)
    {
        $baseQuery = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']));

        // Hitung jumlah guru untuk setiap kategori peran
        $countAll  = (clone $baseQuery)->count();
        $countGuru = (clone $baseQuery)->whereHas('role', fn($q) => $q->where('role_name', 'Guru'))->count();
        $countWali = (clone $baseQuery)->whereHas('role', fn($q) => $q->where('role_name', 'Wali Kelas'))->count();
        $countBK   = (clone $baseQuery)->whereHas('role', fn($q) => $q->where('role_name', 'Guru BK'))->count();

        $query = (clone $baseQuery)
            ->with(['role', 'homeroomClass', 'counselorClasses'])
            ->withCount('schedules');

        // Filter berdasarkan peran (Guru, Wali Kelas, atau Guru BK)
        if ($request->filled('role') && $request->role !== 'all') {
            $roleFilter = $request->role;
            $query->whereHas('role', fn($q) => $q->where('role_name', $roleFilter));
        }

        // Pencarian teks menyeluruh & Case-Insensitive (tidak sensitif huruf kapital)
        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(username) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(COALESCE(nip, \'\')) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(COALESCE(phone, \'\')) LIKE ?', ["%{$search}%"]);
            });
        }

        $perPage = in_array((int)$request->input('per_page'), [50, 100, 500, 1000]) ? (int)$request->input('per_page') : 50;

        $teachers = $query->orderBy('name')->paginate($perPage)->withQueryString();
        $classes = SchoolClass::orderBy('class_name')->get();
        $roles = Role::whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK'])->get();
        $structuredClasses = $this->getStructuredClasses();

        return view('waka.teachers.index', compact(
            'teachers', 
            'classes', 
            'structuredClasses',
            'roles', 
            'countAll', 
            'countGuru', 
            'countWali', 
            'countBK'
        ));
    }

    /**
     * Helper Menyusun Data Kelas Terstruktur (Tingkat, Jurusan, Rombel)
     */
    protected function getStructuredClasses()
    {
        return SchoolClass::orderBy('class_name')->get()->map(function ($c) {
            $parts = explode(' ', trim($c->class_name));
            $grade = $parts[0] ?? '';
            if (count($parts) >= 3) {
                $rombel = end($parts);
                array_pop($parts);
                array_shift($parts);
                $major = implode(' ', $parts);
            } elseif (count($parts) === 2) {
                $major = $parts[1];
                $rombel = '-';
            } else {
                $major = $c->class_name;
                $rombel = '-';
            }
            return [
                'id'         => $c->id,
                'class_name' => $c->class_name,
                'grade'      => $grade,
                'major'      => $major,
                'rombel'     => $rombel,
            ];
        });
    }

    public function teachersStore(Request $request)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'username'              => ['nullable', 'string', 'max:50', 'unique:users,username'],
            'nip'                   => ['nullable', 'string', 'max:30'],
            'email'                 => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'                 => ['nullable', 'string', 'max:20'],
            'address'               => ['nullable', 'string', 'max:500'],
            'role_id'               => ['required', 'exists:roles,id'],
            'homeroom_class_id'     => ['nullable', 'exists:classes,id'],
            'counselor_class_ids'   => ['nullable', 'array'],
            'counselor_class_ids.*' => ['exists:classes,id'],
        ]);

        if (empty($validated['username'])) {
            $baseName = !empty($validated['nip']) ? 'guru_' . $validated['nip'] : 'guru_' . Str::slug($validated['name'], '_');
            $validated['username'] = substr($baseName . '_' . rand(10, 99), 0, 35);
        }

        if (empty($validated['email'])) {
            $validated['email'] = null;
        }

        $role = Role::find($validated['role_id']);
        if ($role?->role_name === 'Guru BK' || $role?->role_name === 'Guru') {
            $validated['homeroom_class_id'] = null;
        }

        $validated['password'] = Hash::make('password123');

        $teacher = User::create($validated);

        if ($role?->role_name === 'Guru BK' && !empty($request->counselor_class_ids)) {
            $teacher->counselorClasses()->sync($request->counselor_class_ids);
        }

        return redirect()->route('waka.teachers.index')->with('success', 'Data guru berhasil ditambahkan.');
    }


    public function teachersUpdate(Request $request, User $teacher)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'username'              => ['required', 'string', 'max:50', 'unique:users,username,' . $teacher->id],
            'nip'                   => ['nullable', 'string', 'max:30'],
            'email'                 => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,' . $teacher->id],
            'phone'                 => ['nullable', 'string', 'max:20'],
            'address'               => ['nullable', 'string', 'max:500'],
            'role_id'               => ['required', 'exists:roles,id'],
            'homeroom_class_id'     => ['nullable', 'exists:classes,id'],
            'counselor_class_ids'   => ['nullable', 'array'],
            'counselor_class_ids.*' => ['exists:classes,id'],
            'password'              => ['nullable', 'string', 'min:6'],
        ]);

        if (empty($validated['email'])) {
            $validated['email'] = null;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $role = Role::find($validated['role_id']);
        if ($role?->role_name === 'Guru BK') {
            $validated['homeroom_class_id'] = null;
            $teacher->counselorClasses()->sync($request->counselor_class_ids ?? []);
        } elseif ($role?->role_name === 'Wali Kelas') {
            $teacher->counselorClasses()->detach();
        } else {
            $validated['homeroom_class_id'] = null;
            $teacher->counselorClasses()->detach();
        }

        $teacher->update($validated);

        return redirect()->route('waka.teachers.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function teachersDestroy(User $teacher)
    {
        if ($teacher->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // 1. Lepaskan relasi perwalian BK
        $teacher->counselorClasses()->detach();

        // 2. Hapus jadwal mengajar yang terikat pada guru ini terlebih dahulu agar tidak memicu foreign key violation
        \App\Models\Schedule::where('teacher_id', $teacher->id)->delete();

        // 3. Hapus data guru
        $teacher->delete();

        return redirect()->route('waka.teachers.index')->with('success', 'Data guru dan seluruh penugasan/jadwal mengajarnya berhasil dihapus.');
    }

    /**
     * Hapus Data Guru Terpilih (Bulk Delete)
     */
    public function teachersDeleteSelected(Request $request)
    {
        $request->validate([
            'teacher_ids'   => ['required', 'array', 'min:1'],
            'teacher_ids.*' => ['exists:users,id'],
        ]);

        $currentUserId = auth()->id();
        $targetIds = array_diff($request->teacher_ids, [$currentUserId]);

        if (empty($targetIds)) {
            return redirect()->back()->with('error', 'Tidak ada data guru yang dapat dihapus (akun Anda sendiri tidak dapat dihapus).');
        }

        $teachers = User::whereIn('id', $targetIds)
            ->whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))
            ->get();

        $validIds = $teachers->pluck('id')->toArray();
        if (empty($validIds)) {
            return redirect()->back()->with('error', 'Tidak ada data guru valid yang ditemukan.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($validIds) {
            // 1. Lepaskan relasi perwalian Guru BK
            \Illuminate\Support\Facades\DB::table('counselor_classes')->whereIn('user_id', $validIds)->delete();

            // 2. Hapus jadwal mengajar yang terikat pada guru-guru ini
            \App\Models\Schedule::whereIn('teacher_id', $validIds)->delete();

            // 3. Hapus data akun guru
            User::whereIn('id', $validIds)->delete();
        });

        $count = count($validIds);
        return redirect()->route('waka.teachers.index')
            ->with('success', "Sukses! Berhasil menghapus {$count} data guru dan seluruh penugasan/jadwal mengajarnya.");
    }

    /**
     * Hapus Semua Data Guru (Opsional per Peran: Semua, Guru Pengajar, Wali Kelas, Guru BK)
     */
    public function teachersDeleteAll(Request $request)
    {
        $currentUserId = auth()->id();
        $query = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))
            ->where('id', '!=', $currentUserId);

        $roleFilter = $request->input('role', 'all');
        if ($roleFilter && $roleFilter !== 'all') {
            $query->whereHas('role', fn($q) => $q->where('role_name', $roleFilter));
            $roleLabel = "peran {$roleFilter}";
        } else {
            $roleLabel = "seluruh peran (Guru, Wali Kelas, Guru BK)";
        }

        $validIds = $query->pluck('id')->toArray();
        if (empty($validIds)) {
            return redirect()->back()->with('error', 'Tidak ada data guru yang dapat dihapus.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($validIds) {
            // 1. Lepaskan relasi perwalian Guru BK
            \Illuminate\Support\Facades\DB::table('counselor_classes')->whereIn('user_id', $validIds)->delete();

            // 2. Hapus jadwal mengajar yang terikat pada guru-guru ini
            \App\Models\Schedule::whereIn('teacher_id', $validIds)->delete();

            // 3. Hapus data akun guru
            User::whereIn('id', $validIds)->delete();
        });

        $count = count($validIds);
        return redirect()->route('waka.teachers.index')
            ->with('success', "Sukses! Berhasil menghapus {$count} data guru dengan {$roleLabel} dari sistem.");
    }


    /**
     * Download Template Excel / CSV Data Guru
     */
    public function teachersTemplate(SpreadsheetImportService $spreadsheetService)
    {
        $headers = ['nama', 'nip', 'username', 'email', 'phone', 'role', 'wali_kelas', 'address'];
        $samples = [
            ['Drs. Budi Santoso, M.Pd', '197508122000031001', 'budi.santoso', 'budi@smk.sch.id', '081234567890', 'Guru', '', 'Jl. Merdeka No. 10'],
            ['Siti Rahmawati, S.Kom', '198804152012022001', 'siti.rahma', 'siti@smk.sch.id', '081298765432', 'Wali Kelas', 'X PPLG 1', 'Jl. Sudirman No. 5'],
        ];

        return $spreadsheetService->downloadTemplate('template_data_guru.csv', $headers, $samples);
    }

    /**
     * Import Data Guru dari File Dokumen (PDF, Excel, atau CSV)
     * Mendukung pembacaan berkas PDF daftar guru/SK/FET, auto-provisioning kelas & jurusan untuk Wali Kelas, serta multiple files.
     */
    public function teachersImport(Request $request, SchoolDocumentParserService $documentService)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $request->validate([
            'files'            => ['nullable', 'array'],
            'files.*'          => ['file', 'mimes:pdf,csv,txt,xlsx', 'max:25600'],
            'file'             => ['nullable', 'file', 'mimes:pdf,csv,txt,xlsx', 'max:25600'],
            'duplicate_action' => ['nullable', 'in:replace,keep'],
        ]);

        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            $uploadedFiles = (array) $request->file('files');
        } elseif ($request->hasFile('file')) {
            $uploadedFiles = [$request->file('file')];
        }

        if (empty($uploadedFiles)) {
            return redirect()->back()->with('error', 'Silakan pilih setidaknya satu file dokumen data guru (PDF / Excel / CSV).');
        }

        $duplicateAction = $request->input('duplicate_action', 'replace');
        if (!in_array($duplicateAction, ['replace', 'keep'])) {
            $duplicateAction = 'replace';
        }

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalSkippedStaff = 0;
        $totalClasses = 0;
        $totalMajors  = 0;
        $processedFiles = 0;
        $errors = [];

        foreach ($uploadedFiles as $file) {
            try {
                $res = $documentService->importTeachers($file, $duplicateAction);
                $totalCreated += $res['created'] ?? 0;
                $totalUpdated += $res['updated'] ?? 0;
                $totalSkipped += $res['skipped'] ?? 0;
                $totalSkippedStaff += $res['skipped_staff'] ?? 0;
                $totalClasses += $res['created_classes'] ?? 0;
                $totalMajors  += $res['created_majors'] ?? 0;
                $processedFiles++;
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        if ($processedFiles === 0 && !empty($errors)) {
            return redirect()->back()->with('error', 'Gagal memproses berkas data guru: ' . implode('; ', $errors));
        }

        $msg = "Sukses! Berhasil memproses {$processedFiles} berkas guru: {$totalCreated} guru baru ditambahkan, {$totalUpdated} data guru diperbarui";
        if ($totalSkipped > 0) {
            $msg .= ", {$totalSkipped} data guru sudah ada (dipertahankan)";
        }
        $msg .= ".";
        $details = [];
        if ($totalClasses > 0) $details[] = "{$totalClasses} rombel kelas baru";
        if ($totalMajors > 0)  $details[] = "{$totalMajors} jurusan baru";
        if ($totalSkippedStaff > 0) $details[] = "{$totalSkippedStaff} pegawai non-guru dilewati otomatis";

        if (!empty($details)) {
            $msg .= " Catatan sistem: " . implode(', ', $details) . ".";
        }
        if (!empty($errors)) {
            $msg .= " Catatan peringatan: " . implode('; ', $errors);
        }

        return redirect()->route('waka.teachers.index')->with('success', $msg);
    }

    /**
     * Master Data: Siswa
     */
    public function studentsIndex(Request $request)
    {
        $query = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->with(['schoolClass', 'parent']);

        // Filter 1: Tingkat (X, XI, XII atau 10, 11, 12)
        if ($request->filled('grade') && $request->grade !== 'all') {
            $gradeMap = ['10' => 'X', '11' => 'XI', '12' => 'XII', 'X' => 'X', 'XI' => 'XI', 'XII' => 'XII'];
            $gradeVal = $gradeMap[$request->grade] ?? $request->grade;
            $query->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', "{$gradeVal} %"));
        }

        // Filter 2: Jurusan (RPL, TKJ, AKL, BD, MP, dll)
        if ($request->filled('major') && $request->major !== 'all') {
            $major = trim($request->major);
            $query->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', "% {$major} %")->orWhere('class_name', 'like', "% {$major}"));
        }

        // Filter 3: Rombel (1, 2, 3, 4)
        if ($request->filled('rombel') && $request->rombel !== 'all') {
            $rombel = trim($request->rombel);
            $query->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', "% {$rombel}"));
        }

        // Kompatibilitas class_id langsung
        if ($request->filled('class_id') && $request->class_id !== 'all') {
            $query->where('class_id', $request->class_id);
        }

        // Pencarian Teks Menyeluruh & Case-Insensitive (tidak sensitif huruf kapital)
        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(username) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(COALESCE(nis, \'\')) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(COALESCE(phone, \'\')) LIKE ?', ["%{$search}%"]);
            });
        }

        $perPage = in_array((int)$request->input('per_page'), [50, 100, 500, 1000]) ? (int)$request->input('per_page') : 50;

        $students = $query->orderBy('name')->paginate($perPage)->withQueryString();
        $classes = SchoolClass::orderBy('class_name')->get();
        $studentRole = Role::where('role_name', 'Siswa')->first();
        $structuredClasses = $this->getStructuredClasses();

        // Opsi Dropdown Filter 3 Kolom
        $availableGrades = ['X', 'XI', 'XII'];
        $availableMajors = Major::orderBy('code')->pluck('code')->toArray();
        if (empty($availableMajors)) {
            $availableMajors = $structuredClasses->pluck('major')->unique()->filter()->values()->toArray();
        }
        $availableRombels = ['1', '2', '3', '4'];

        return view('waka.students.index', compact(
            'students', 
            'classes', 
            'structuredClasses', 
            'studentRole',
            'availableGrades',
            'availableMajors',
            'availableRombels'
        ));
    }

        public function studentsStore(Request $request)
    {
        $studentRole = Role::where('role_name', 'Siswa')->firstOrFail();

        // Otomatis gabungkan Tingkat, Jurusan, dan Rombel jika dikirim dari 3 kolom pemilih
        if ($request->filled(['grade', 'major', 'rombel'])) {
            $gradeMap = ['10' => 'X', '11' => 'XI', '12' => 'XII', 'X' => 'X', 'XI' => 'XI', 'XII' => 'XII'];
            $grade = $gradeMap[$request->grade] ?? $request->grade;
            $rombelStr = $request->rombel === '-' ? '' : $request->rombel;
            $className = trim("{$grade} {$request->major} {$rombelStr}");

            // Cari atau buat otomatis kelas jika belum terdaftar di tabel classes
            $schoolClass = SchoolClass::firstOrCreate(['class_name' => $className]);
            $request->merge(['class_id' => $schoolClass->id]);
        }

        // Auto-generate username jika dikosongkan oleh Waka
        if (!$request->filled('username')) {
            $baseUser = $request->filled('nis') ? "siswa_{$request->nis}" : 'siswa_' . Str::slug($request->name, '_');
            $uniqueUser = substr($baseUser, 0, 30);
            $counter = 1;
            while (User::where('username', $uniqueUser)->exists()) {
                $uniqueUser = substr($baseUser, 0, 24) . '_' . rand(10, 9999);
            }
            $request->merge(['username' => $uniqueUser]);
        }

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'username'   => ['required', 'string', 'max:50', 'unique:users,username'],
            'nis'        => ['nullable', 'string', 'max:30'],
            'email'      => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'address'    => ['nullable', 'string', 'max:500'],
            'birth_date' => ['nullable', 'date'],
            'class_id'   => ['required', 'exists:classes,id'],
        ]);

        if (empty($validated['email'])) {
            $validated['email'] = null;
        }

        $validated['password'] = Hash::make('password123');
        $validated['role_id']  = $studentRole->id;

        // Auto-create akun Orang Tua untuk siswa ini
        $parentRole = Role::where('role_name', 'Orang Tua')->first();
        if ($parentRole) {
            $parentName = 'Orang tua ' . trim($validated['name']);
            $nis = trim((string)($validated['nis'] ?? ''));
            $baseParentUser = $nis ? "ortu_{$nis}" : "ortu_" . $validated['username'];
            $uniqueParentUser = substr($baseParentUser, 0, 45);
            $pCounter = 1;
            while (User::where('username', $uniqueParentUser)->exists()) {
                $uniqueParentUser = substr($baseParentUser, 0, 38) . "_{$pCounter}";
                $pCounter++;
            }
            $parentPasswordPlain = $nis ? "p{$nis}" : "portu123";

            $parent = User::create([
                'name'           => $parentName,
                'username'       => $uniqueParentUser,
                'password'       => Hash::make($parentPasswordPlain),
                'role_id'        => $parentRole->id,
                'phone'          => $validated['phone'] ?? null,
                'address'        => $validated['address'] ?? null,
                'student_status' => 'Aktif',
            ]);
            $validated['parent_id'] = $parent->id;
        }

        User::create($validated);

        return redirect()->route('waka.students.index')->with('success', 'Data siswa berhasil ditambahkan dan otomatis dibuatkan akun Orang Tua (Password siswa: password123, Password orang tua: p[NIS]).');
    }

    public function studentsUpdate(Request $request, User $student)
    {
        // Otomatis gabungkan Tingkat, Jurusan, dan Rombel jika dikirim dari 3 kolom pemilih
        if ($request->filled(['grade', 'major', 'rombel'])) {
            $gradeMap = ['10' => 'X', '11' => 'XI', '12' => 'XII', 'X' => 'X', 'XI' => 'XI', 'XII' => 'XII'];
            $grade = $gradeMap[$request->grade] ?? $request->grade;
            $rombelStr = $request->rombel === '-' ? '' : $request->rombel;
            $className = trim("{$grade} {$request->major} {$rombelStr}");

            $schoolClass = SchoolClass::firstOrCreate(['class_name' => $className]);
            $request->merge(['class_id' => $schoolClass->id]);
        }

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'username'   => ['required', 'string', 'max:50', 'unique:users,username,' . $student->id],
            'nis'        => ['nullable', 'string', 'max:30'],
            'email'      => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,' . $student->id],
            'phone'      => ['nullable', 'string', 'max:20'],
            'address'    => ['nullable', 'string', 'max:500'],
            'birth_date' => ['nullable', 'date'],
            'class_id'   => ['required', 'exists:classes,id'],
            'password'   => ['nullable', 'string', 'min:6'],
        ]);

        if (empty($validated['email'])) {
            $validated['email'] = null;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $student->update($validated);

        // Pastikan nama akun orang tua sinkron atau buatkan jika belum ada
        if ($student->parent) {
            $student->parent->update([
                'name' => 'Orang tua ' . trim($student->name),
            ]);
        } elseif (!$student->parent_id) {
            $parentRole = Role::where('role_name', 'Orang Tua')->first();
            if ($parentRole) {
                $parentName = 'Orang tua ' . trim($student->name);
                $nis = trim((string)($student->nis ?? ''));
                $baseParentUser = $nis ? "ortu_{$nis}" : "ortu_" . $student->username;
                $uniqueParentUser = substr($baseParentUser, 0, 45);
                $pCounter = 1;
                while (User::where('username', $uniqueParentUser)->exists()) {
                    $uniqueParentUser = substr($baseParentUser, 0, 38) . "_{$pCounter}";
                    $pCounter++;
                }
                $parentPasswordPlain = $nis ? "p{$nis}" : "portu123";

                $parent = User::create([
                    'name'           => $parentName,
                    'username'       => $uniqueParentUser,
                    'password'       => Hash::make($parentPasswordPlain),
                    'role_id'        => $parentRole->id,
                    'phone'          => $student->phone,
                    'address'        => $student->address,
                    'student_status' => 'Aktif',
                ]);
                $student->update(['parent_id' => $parent->id]);
            }
        }

        return redirect()->route('waka.students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Sinkronisasi massal seluruh akun Orang Tua untuk siswa yang belum memiliki akun orang tua
     */
    public function studentsSyncParents(Request $request)
    {
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $parentRole = Role::where('role_name', 'Orang Tua')->firstOrFail();
        $unlinkedStudents = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->whereNull('parent_id')
            ->select('id', 'name', 'username', 'nis', 'phone', 'address')
            ->get();

        if ($unlinkedStudents->isEmpty()) {
            return redirect()->route('waka.students.index')->with('success', 'Seluruh data siswa sudah memiliki akun orang tua yang terhubung.');
        }

        // Cache username yang sudah ada ke array memori agar pencarian username sangat cepat tanpa query berulang
        $existingUsernames = [];
        foreach (User::pluck('username') as $u) {
            $existingUsernames[strtolower($u)] = true;
        }

        $createdCount = 0;
        $nisHashes = [];

        // Proses per chunk 100 siswa dalam transaksi database untuk performa maksimal
        foreach ($unlinkedStudents->chunk(100) as $chunk) {
            DB::transaction(function () use ($chunk, $parentRole, &$existingUsernames, &$nisHashes, &$createdCount) {
                $now = now();
                foreach ($chunk as $student) {
                    $nis = preg_replace('/[^0-9A-Za-z]/', '', (string)($student->nis ?? ''));
                    $baseParentUser = $nis ? "ortu_{$nis}" : "ortu_" . preg_replace('/[^0-9A-Za-z_]/', '', (string)$student->username);
                    $parentPasswordPlain = $nis ? "p{$nis}" : "portu123";

                    // 1. Cek jika akun orang tua dengan username ini sudah pernah dibuat sebelumnya (misal diinput manual)
                    $existingParent = DB::table('users')->where('username', $baseParentUser)->first();
                    if ($existingParent) {
                        if (!isset($nisHashes[$parentPasswordPlain])) {
                            $nisHashes[$parentPasswordPlain] = password_hash($parentPasswordPlain, PASSWORD_BCRYPT, ['cost' => 10]);
                        }
                        DB::table('users')->where('id', $existingParent->id)->update([
                            'password'   => $nisHashes[$parentPasswordPlain],
                            'role_id'    => $parentRole->id,
                            'name'       => 'Orang tua ' . trim($student->name),
                            'updated_at' => $now,
                        ]);
                        DB::table('users')->where('id', $student->id)->update(['parent_id' => $existingParent->id]);
                        $existingUsernames[strtolower($baseParentUser)] = true;
                        $createdCount++;
                        continue;
                    }

                    // 2. Jika belum ada, pastikan username unik sebelum membuat akun baru
                    $uniqueParentUser = substr($baseParentUser, 0, 45);
                    $pCounter = 1;
                    while (isset($existingUsernames[strtolower($uniqueParentUser)])) {
                        $uniqueParentUser = substr($baseParentUser, 0, 38) . "_{$pCounter}";
                        $pCounter++;
                    }
                    $existingUsernames[strtolower($uniqueParentUser)] = true;

                    if (!isset($nisHashes[$parentPasswordPlain])) {
                        $nisHashes[$parentPasswordPlain] = password_hash($parentPasswordPlain, PASSWORD_BCRYPT, ['cost' => 10]);
                    }
                    $hash = $nisHashes[$parentPasswordPlain];

                    try {
                        $parentId = DB::table('users')->insertGetId([
                            'name'           => 'Orang tua ' . trim($student->name),
                            'username'       => $uniqueParentUser,
                            'password'       => $hash,
                            'role_id'        => $parentRole->id,
                            'phone'          => $student->phone,
                            'address'        => $student->address,
                            'student_status' => 'Aktif',
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ]);
                    } catch (\Throwable $ex) {
                        $fallback = DB::table('users')->where('username', $uniqueParentUser)->first();
                        if ($fallback) {
                            $parentId = $fallback->id;
                        } else {
                            $uniqueParentUser = substr($baseParentUser, 0, 35) . '_' . rand(1000, 9999);
                            $parentId = DB::table('users')->insertGetId([
                                'name'           => 'Orang tua ' . trim($student->name),
                                'username'       => $uniqueParentUser,
                                'password'       => $hash,
                                'role_id'        => $parentRole->id,
                                'phone'          => $student->phone,
                                'address'        => $student->address,
                                'student_status' => 'Aktif',
                                'created_at'     => $now,
                                'updated_at'     => $now,
                            ]);
                        }
                    }

                    DB::table('users')->where('id', $student->id)->update(['parent_id' => $parentId]);
                    $createdCount++;
                }
            });
        }

        return redirect()->route('waka.students.index')->with('success', "Sukses! Berhasil membuat {$createdCount} akun orang tua baru dan menautkannya ke masing-masing siswa.");
    }

    /**
     * Endpoint langsung (GET) untuk sinkronisasi akun orang tua via browser
     */
    public function studentsSyncParentsDirect(Request $request)
    {
        if ($request->get('key') !== 'ukk2026') {
            abort(403, 'Akses ditolak. Gunakan key yang valid.');
        }

        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $parentRole = Role::where('role_name', 'Orang Tua')->firstOrFail();
        $unlinkedStudents = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->whereNull('parent_id')
            ->select('id', 'name', 'username', 'nis', 'phone', 'address')
            ->get();

        $existingUsernames = [];
        foreach (User::pluck('username') as $u) {
            $existingUsernames[strtolower($u)] = true;
        }

        $createdCount = 0;
        $nisHashes = [];

        foreach ($unlinkedStudents->chunk(100) as $chunk) {
            DB::transaction(function () use ($chunk, $parentRole, &$existingUsernames, &$nisHashes, &$createdCount) {
                $now = now();
                foreach ($chunk as $student) {
                    $nis = preg_replace('/[^0-9A-Za-z]/', '', (string)($student->nis ?? ''));
                    $baseParentUser = $nis ? "ortu_{$nis}" : "ortu_" . preg_replace('/[^0-9A-Za-z_]/', '', (string)$student->username);
                    $parentPasswordPlain = $nis ? "p{$nis}" : "portu123";

                    // 1. Cek jika akun orang tua sudah ada
                    $existingParent = DB::table('users')->where('username', $baseParentUser)->first();
                    if ($existingParent) {
                        if (!isset($nisHashes[$parentPasswordPlain])) {
                            $nisHashes[$parentPasswordPlain] = password_hash($parentPasswordPlain, PASSWORD_BCRYPT, ['cost' => 10]);
                        }
                        DB::table('users')->where('id', $existingParent->id)->update([
                            'password'   => $nisHashes[$parentPasswordPlain],
                            'role_id'    => $parentRole->id,
                            'name'       => 'Orang tua ' . trim($student->name),
                            'updated_at' => $now,
                        ]);
                        DB::table('users')->where('id', $student->id)->update(['parent_id' => $existingParent->id]);
                        $existingUsernames[strtolower($baseParentUser)] = true;
                        $createdCount++;
                        continue;
                    }

                    // 2. Jika belum ada, buat baru
                    $uniqueParentUser = substr($baseParentUser, 0, 45);
                    $pCounter = 1;
                    while (isset($existingUsernames[strtolower($uniqueParentUser)])) {
                        $uniqueParentUser = substr($baseParentUser, 0, 38) . "_{$pCounter}";
                        $pCounter++;
                    }
                    $existingUsernames[strtolower($uniqueParentUser)] = true;

                    if (!isset($nisHashes[$parentPasswordPlain])) {
                        $nisHashes[$parentPasswordPlain] = password_hash($parentPasswordPlain, PASSWORD_BCRYPT, ['cost' => 10]);
                    }
                    $hash = $nisHashes[$parentPasswordPlain];

                    try {
                        $parentId = DB::table('users')->insertGetId([
                            'name'           => 'Orang tua ' . trim($student->name),
                            'username'       => $uniqueParentUser,
                            'password'       => $hash,
                            'role_id'        => $parentRole->id,
                            'phone'          => $student->phone,
                            'address'        => $student->address,
                            'student_status' => 'Aktif',
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ]);
                    } catch (\Throwable $ex) {
                        $fallback = DB::table('users')->where('username', $uniqueParentUser)->first();
                        if ($fallback) {
                            $parentId = $fallback->id;
                        } else {
                            $uniqueParentUser = substr($baseParentUser, 0, 35) . '_' . rand(1000, 9999);
                            $parentId = DB::table('users')->insertGetId([
                                'name'           => 'Orang tua ' . trim($student->name),
                                'username'       => $uniqueParentUser,
                                'password'       => $hash,
                                'role_id'        => $parentRole->id,
                                'phone'          => $student->phone,
                                'address'        => $student->address,
                                'student_status' => 'Aktif',
                                'created_at'     => $now,
                                'updated_at'     => $now,
                            ]);
                        }
                    }

                    DB::table('users')->where('id', $student->id)->update(['parent_id' => $parentId]);
                    $createdCount++;
                }
            });
        }

        return response("<div style='font-family:system-ui,-apple-system,sans-serif;padding:30px;background:#0f172a;color:#38bdf8;border-radius:15px;max-width:550px;margin:50px auto;border:1px solid #1e293b;box-shadow:0 20px 25px -5px rgba(0,0,0,0.5);line-height:1.6;'>
            <h2 style='color:#4ade80;margin-top:0;'>✔ Sinkronisasi Akun Orang Tua Berhasil!</h2>
            <p style='color:#f8fafc;'>Berhasil memproses dan membuatkan <strong style='color:#38bdf8;'>{$createdCount}</strong> akun Orang Tua baru untuk semua siswa yang belum terhubung.</p>
            <div style='background:#1e293b;padding:15px;border-radius:10px;margin:20px 0;font-size:13px;color:#cbd5e1;'>
                <p style='margin:0 0 8px 0;font-weight:bold;color:#fff;'>Format Akun Orang Tua:</p>
                <p style='margin:3px 0;'>• <strong>Username:</strong> <code style='color:#a5b4fc;'>ortu_[NIS]</code> (contoh: <code>ortu_17821</code>)</p>
                <p style='margin:3px 0;'>• <strong>Password:</strong> <code style='color:#4ade80;'>p[NIS]</code> (contoh: <code>p17821</code>)</p>
            </div>
            <a href='/login' style='display:inline-block;padding:12px 24px;background:#6366f1;color:#fff;text-decoration:none;border-radius:10px;font-weight:bold;box-shadow:0 4px 6px -1px rgba(99,102,241,0.4);'>Masuk ke Halaman Login &rarr;</a>
        </div>");
    }

    public function studentsDestroy(User $student)
    {
        $student->delete();
        return redirect()->route('waka.students.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * Hapus Data Siswa yang Dipilih (Bulk Delete)
     */
    public function studentsDeleteSelected(Request $request)
    {
        $request->validate([
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['exists:users,id'],
        ]);

        $count = User::whereIn('id', $request->student_ids)
            ->whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->delete();

        return redirect()->route('waka.students.index')
            ->with('success', "Sukses! Berhasil menghapus {$count} data siswa yang dipilih.");
    }

    /**
     * Hapus Semua Data Siswa (Opsional per Kelas atau Seluruh Siswa)
     */
    public function studentsDeleteAll(Request $request)
    {
        $query = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'));

        if ($request->filled('class_id') && $request->class_id !== 'all') {
            $class = SchoolClass::find($request->class_id);
            $count = (clone $query)->where('class_id', $request->class_id)->delete();
            $className = $class ? $class->class_name : 'terkait';
            $msg = "Sukses! Berhasil menghapus seluruh data siswa di kelas {$className} ({$count} siswa).";
        } else {
            $count = $query->delete();
            $msg = "Sukses! Berhasil menghapus seluruh data siswa dari sistem ({$count} siswa).";
        }

        return redirect()->route('waka.students.index')->with('success', $msg);
    }

    /**
     * Download Template Excel / CSV Data Siswa
     */
    public function studentsTemplate(SpreadsheetImportService $spreadsheetService)
    {
        $headers = ['nama', 'nis', 'username', 'email', 'kelas', 'phone', 'birth_date', 'address'];
        $samples = [
            ['Ahmad Rizki Pratama', '24251001', 'ahmad.rizki', 'ahmad.rizki@siswa.sch.id', 'X PPLG 1', '081311223344', '2008-05-14', 'Jl. Kenanga No. 12'],
            ['Dewi Anggraini', '24251002', 'dewi.anggra', 'dewi@siswa.sch.id', 'X PPLG 1', '081355667788', '2008-08-20', 'Jl. Mawar No. 4'],
            ['Muhammad Farhan', '24251003', 'farhan.m', 'farhan@siswa.sch.id', 'XI TJKT 2', '081399887766', '2007-11-03', 'Jl. Melati No. 8'],
        ];

        return $spreadsheetService->downloadTemplate('template_data_siswa.csv', $headers, $samples);
    }

    /**
     * Import Data Siswa dari File Dokumen (PDF, Excel, atau CSV)
     * Dilengkapi auto-provisioning Jurusan, Kelas, dan Akun Orang Tua serta mendukung berkas PDF per rombel & multiple files.
     */
    public function studentsImport(Request $request, SchoolDocumentParserService $documentService)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $request->validate([
            'files'            => ['nullable', 'array'],
            'files.*'          => ['file', 'mimes:pdf,csv,txt,xlsx', 'max:25600'],
            'file'             => ['nullable', 'file', 'mimes:pdf,csv,txt,xlsx', 'max:25600'],
            'duplicate_action' => ['nullable', 'in:replace,keep'],
        ]);

        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            $uploadedFiles = (array) $request->file('files');
        } elseif ($request->hasFile('file')) {
            $uploadedFiles = [$request->file('file')];
        }

        if (empty($uploadedFiles)) {
            return redirect()->back()->with('error', 'Silakan pilih setidaknya satu file dokumen data siswa (PDF / Excel / CSV).');
        }

        $duplicateAction = $request->input('duplicate_action', 'replace');
        if (!in_array($duplicateAction, ['replace', 'keep'])) {
            $duplicateAction = 'replace';
        }

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalClasses = 0;
        $totalMajors  = 0;
        $totalParents = 0;
        $processedFiles = 0;
        $errors = [];

        foreach ($uploadedFiles as $file) {
            try {
                $res = $documentService->importStudents($file, $duplicateAction);
                $totalCreated += $res['created'] ?? 0;
                $totalUpdated += $res['updated'] ?? 0;
                $totalSkipped += $res['skipped'] ?? 0;
                $totalClasses += $res['created_classes'] ?? 0;
                $totalMajors  += $res['created_majors'] ?? 0;
                $totalParents += $res['created_parents'] ?? 0;
                $processedFiles++;
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        if ($processedFiles === 0 && !empty($errors)) {
            return redirect()->back()->with('error', 'Gagal memproses berkas data siswa: ' . implode('; ', $errors));
        }

        $msg = "Sukses! Berhasil memproses {$processedFiles} berkas data siswa: {$totalCreated} siswa baru ditambahkan";
        if ($totalUpdated > 0) {
            $msg .= ", {$totalUpdated} data siswa diperbarui";
        }
        if ($totalSkipped > 0) {
            $msg .= ", {$totalSkipped} data siswa sudah ada (dipertahankan)";
        }
        $msg .= ".";

        $details = [];
        if ($totalClasses > 0) $details[] = "{$totalClasses} rombel kelas baru";
        if ($totalMajors > 0)  $details[] = "{$totalMajors} jurusan baru";
        if ($totalParents > 0) $details[] = "{$totalParents} akun orang tua otomatis dibuatkan";

        if (!empty($details)) {
            $msg .= " Sistem otomatis melakukan auto-provisioning: " . implode(', ', $details) . ".";
        }
        if (!empty($errors)) {
            $msg .= " Catatan peringatan: " . implode('; ', $errors);
        }

        return redirect()->route('waka.students.index')->with('success', $msg);
    }

    public function classesStore(Request $request)
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:50', 'unique:classes,class_name'],
        ]);

        SchoolClass::create($validated);

        return redirect()->route('waka.classes.index')->with('success', 'Kelas baru berhasil ditambahkan.');
    }

public function classesDestroy(SchoolClass $schoolClass)
{
    if ($schoolClass->users()->exists()) {
        return redirect()->back()->with('error', 'Kelas tidak dapat dihapus karena masih memiliki data siswa terdaftar.');
    }

    // Hapus jadwal yang terikat terlebih dahulu agar tidak memicu error foreign key
    $schoolClass->schedules()->delete();

    $schoolClass->delete();

    return redirect()->route('waka.classes.index')->with('success', 'Kelas dan seluruh jadwal terkait berhasil dihapus.');
}


    /**
     * Master Data: Ruang
     */
    public function roomsIndex(Request $request)
    {
        $perPage = in_array((int)$request->input('per_page'), [50, 100, 500, 1000]) ? (int)$request->input('per_page') : 50;

        $rooms = Room::withCount('schedules')
            ->with(['schedules' => function ($q) {
                $q->with(['schoolClass', 'teacher'])
                  ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 END")
                  ->orderBy('start_time');
            }])
            ->orderBy('room_name')
            ->paginate($perPage)
            ->withQueryString();

        return view('waka.rooms.index', compact('rooms'));
    }

    public function roomsStore(Request $request)
    {
        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:50', 'unique:rooms,room_name'],
        ]);

        Room::create($validated);

        return redirect()->route('waka.rooms.index')->with('success', 'Ruangan baru berhasil ditambahkan.');
    }

    public function roomsDestroy(Room $room)
    {
        // Bersihkan jadwal yang menggunakan ruangan ini terlebih dahulu agar aman dari foreign key violation
        $room->schedules()->delete();

        $room->delete();

        return redirect()->route('waka.rooms.index')->with('success', 'Ruangan dan seluruh penugasan jadwal terkait berhasil dihapus.');
    }

    /**
     * Download Template Excel / CSV Data Ruangan
     */
    public function roomsTemplate(SpreadsheetImportService $spreadsheetService)
    {
        $headers = ['nama_ruang'];
        $samples = [
            ['Lab Komputer 1'],
            ['Lab Komputer 2'],
            ['Ruang Teori 101'],
            ['Ruang Teori 102'],
            ['Bengkel Praktik Otomotif'],
            ['Bengkel Desain Komunikasi Visual'],
        ];

        return $spreadsheetService->downloadTemplate('template_data_ruang.csv', $headers, $samples);
    }

    /**
     * Import Data Ruangan dari File Dokumen (PDF, Excel, atau CSV)
     * Mendukung pembacaan matriks/legenda ruangan dari PDF FET dan input banyak berkas (Multiple Files).
     */
    public function roomsImport(Request $request, SchoolDocumentParserService $documentService)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $request->validate([
            'files'            => ['nullable', 'array'],
            'files.*'          => ['file', 'mimes:csv,txt,xlsx,pdf', 'max:25600'],
            'file'             => ['nullable', 'file', 'mimes:csv,txt,xlsx,pdf', 'max:25600'],
            'duplicate_action' => ['nullable', 'in:replace,keep'],
        ]);

        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            $uploadedFiles = (array) $request->file('files');
        } elseif ($request->hasFile('file')) {
            $uploadedFiles = [$request->file('file')];
        }

        if (empty($uploadedFiles)) {
            return redirect()->back()->with('error', 'Silakan pilih setidaknya satu file data ruangan (PDF / Excel / CSV).');
        }

        $duplicateAction = $request->input('duplicate_action', 'replace');
        if (!in_array($duplicateAction, ['replace', 'keep'])) {
            $duplicateAction = 'replace';
        }

        $totalCreated = 0;
        $totalExisting = 0;
        $totalProcessed = 0;
        $processedFiles = 0;
        $errors = [];

        foreach ($uploadedFiles as $file) {
            try {
                $res = $documentService->importRooms($file, $duplicateAction);
                $totalCreated += $res['created'] ?? 0;
                $totalExisting += $res['existing'] ?? 0;
                $totalProcessed += $res['total'] ?? (($res['created'] ?? 0) + ($res['existing'] ?? 0));
                $processedFiles++;
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        if ($processedFiles === 0 && !empty($errors)) {
            return redirect()->back()->with('error', 'Gagal memproses berkas data ruangan: ' . implode('; ', $errors));
        }

        if ($totalCreated > 0) {
            $msg = "Sukses! Berhasil memproses {$processedFiles} berkas: {$totalProcessed} ruangan berhasil diinput/disinkronkan ({$totalCreated} ruangan baru ditambahkan).";
        } else {
            $msg = "Sukses! Berhasil memproses {$processedFiles} berkas: {$totalProcessed} data ruangan berhasil disinkronkan.";
        }
        if (!empty($errors)) {
            $msg .= " Catatan peringatan: " . implode('; ', $errors);
        }

        return redirect()->route('waka.rooms.index')->with('success', $msg);
    }
    
        /**
     * Halaman Monitoring Kepatuhan & Riwayat Jurnal KBM Guru
     */
    public function journalsIndex(Request $request, JournalReminderService $service)
    {
        // 1. Guru Terlambat / Belum Mengisi Jurnal Hari Ini
        $lateTeachers = $service->overdueFor();

                // 2. Filter Riwayat Jurnal KBM
        $range     = $request->get('range');
        $date      = $request->get('date');
        $month     = $request->get('month');
        $teacherId = $request->get('teacher_id');
        $classId   = $request->get('class_id');

        $query = \App\Models\Journal::with(['schedule.teacher', 'schedule.schoolClass', 'schedule.room', 'attendances']);

        // Filter Tanggal Spesifik (jika diisi)
        if ($request->filled('date')) {
            $query->whereDate('date', $date);
        }
        // Filter Rekapan 1 Bulan (jika diisi, format YYYY-MM)
        elseif ($request->filled('month')) {
            $parsedMonth = \Carbon\Carbon::parse($month);
            $query->whereYear('date', $parsedMonth->year)->whereMonth('date', $parsedMonth->month);
        }
        // Filter Preset Rentang (jika tanggal & bulan tidak dipilih)
        elseif ($range === 'hari_ini') {
            $query->whereDate('date', now()->toDateString());
        } elseif ($range === '1_minggu') {
            $query->whereDate('date', '>=', now()->subDays(7)->toDateString());
        } elseif ($range === '1_bulan') {
            $query->whereDate('date', '>=', now()->subDays(30)->toDateString());
        }

        if ($teacherId) {
            $query->whereHas('schedule', fn($q) => $q->where('teacher_id', $teacherId));
        }

        if ($classId) {
            $query->whereHas('schedule', fn($q) => $q->where('class_id', $classId));
        }

        $journals = $query->latest('date')->paginate(15)->withQueryString();
        $teachers = User::with('role')->whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))->orderBy('name')->get();
        $classes  = SchoolClass::orderBy('class_name')->get();

        return view('waka.journals.index', compact('lateTeachers', 'journals', 'teachers', 'classes', 'range', 'date', 'month'));
    }

    /**
     * Halaman Khusus Pengelolaan Master Data Tahun Ajaran & Semester
     */
    public function academicYearIndex(Request $request)
    {
        $config = SystemConfiguration::current();

        // 1. Ambil atau inisialisasi daftar tahun ajaran dari database
        if (Schema::hasTable('academic_years')) {
            if (AcademicYear::count() === 0) {
                AcademicYear::create(['year' => '2024/2025', 'status' => 'Tahun ajaran selesai']);
                AcademicYear::create(['year' => '2025/2026', 'status' => 'Tahun ajaran selesai']);
                AcademicYear::create(['year' => '2026/2027', 'status' => 'Tahun ajaran berjalan']);
                AcademicYear::create(['year' => '2027/2028', 'status' => 'Tahun ajaran mendatang']);
            }
            $academicYears = AcademicYear::orderBy('year')->get();
        } else {
            // Fallback aman sebelum migration dijalankan
            $academicYears = collect([
                (object) ['id' => 1, 'year' => '2024/2025', 'status' => 'Tahun ajaran selesai'],
                (object) ['id' => 2, 'year' => '2025/2026', 'status' => 'Tahun ajaran selesai'],
                (object) ['id' => 3, 'year' => '2026/2027', 'status' => 'Tahun ajaran berjalan'],
                (object) ['id' => 4, 'year' => '2027/2028', 'status' => 'Tahun ajaran mendatang'],
            ]);
        }

        // 2. Tahun ajaran yang boleh muncul di dropdown Ganti Periode Aktif
        // (Tahun ajaran yang sudah selesai OTOMATIS HILANG dari dropdown)
        $selectableYears = $academicYears->filter(fn($ay) => $ay->status !== 'Tahun ajaran selesai');

        // 3. Logika Filter Linimasa Angkatan Masuk Sekolah
        $selectedEntryYear = (int) $request->get('entry_year', 2024);
        $currentStartYear  = (int) substr($config->academic_year, 0, 4); // 2026

        // Susun linimasa 3 tahun SMK untuk angkatan yang dipilih
        $timeline = [
            [
                'academic_year' => $selectedEntryYear . '/' . ($selectedEntryYear + 1),
                'grade_level'   => 'Kelas 10 (Tingkat X)',
                'is_current'    => ($selectedEntryYear === $currentStartYear),
            ],
            [
                'academic_year' => ($selectedEntryYear + 1) . '/' . ($selectedEntryYear + 2),
                'grade_level'   => 'Kelas 11 (Tingkat XI)',
                'is_current'    => (($selectedEntryYear + 1) === $currentStartYear),
            ],
            [
                'academic_year' => ($selectedEntryYear + 2) . '/' . ($selectedEntryYear + 3),
                'grade_level'   => 'Kelas 12 (Tingkat XII)',
                'is_current'    => (($selectedEntryYear + 2) === $currentStartYear),
            ],
        ];

        // Tentukan kesimpulan status saat ini untuk angkatan tersebut
        $diffYears = $currentStartYear - $selectedEntryYear;
        if ($diffYears >= 3) {
            $entryStatusText = "Siswa Angkatan {$selectedEntryYear} saat ini sudah LULUS (Alumni Sekolah).";
            $entryStatusBadge = "Sudah Lulus / Alumni";
            $entryStatusClass = "bg-emerald-500/20 text-emerald-400 border-emerald-500/30";
        } elseif ($diffYears === 2) {
            $entryStatusText = "Siswa Angkatan {$selectedEntryYear} saat ini masih aktif belajar di Kelas 12 (Tingkat XII).";
            $entryStatusBadge = "Aktif di Kelas 12";
            $entryStatusClass = "bg-indigo-500/20 text-indigo-300 border-indigo-500/30";
        } elseif ($diffYears === 1) {
            $entryStatusText = "Siswa Angkatan {$selectedEntryYear} saat ini masih aktif belajar di Kelas 11 (Tingkat XI).";
            $entryStatusBadge = "Aktif di Kelas 11";
            $entryStatusClass = "bg-blue-500/20 text-blue-300 border-blue-500/30";
        } else {
            $entryStatusText = "Siswa Angkatan {$selectedEntryYear} saat ini adalah siswa baru di Kelas 10 (Tingkat X).";
            $entryStatusBadge = "Siswa Baru di Kelas 10";
            $entryStatusClass = "bg-amber-500/20 text-amber-300 border-amber-500/30";
        }

        // Statistik
        $totalStudents = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))->where('student_status', 'Aktif')->count();
        $totalAlumni   = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))->where('student_status', 'Lulus')->count();
        $totalClasses  = SchoolClass::count();

        return view('waka.academic-year.index', compact(
            'config', 'academicYears', 'selectableYears', 'selectedEntryYear',
            'timeline', 'entryStatusText', 'entryStatusBadge', 'entryStatusClass',
            'totalStudents', 'totalAlumni', 'totalClasses'
        ));
    }

    /**
     * Waka Menambah Tahun Ajaran Baru
     */
    public function academicYearStore(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/', 'unique:academic_years,year'],
        ], [
            'year.regex' => 'Format tahun ajaran harus berupa format tahun (contoh: 2028/2029).',
            'year.unique' => 'Tahun ajaran ini sudah terdaftar sebelumnya.',
        ]);

        if (Schema::hasTable('academic_years')) {
            AcademicYear::create([
                'year'   => trim($validated['year']),
                'status' => 'Tahun ajaran mendatang',
            ]);
        }

        return redirect()->back()->with('success', "Tahun ajaran {$validated['year']} berhasil ditambahkan sebagai Tahun Ajaran Mendatang.");
    }

    /**
     * Pembaruan Pengaturan Tahun Ajaran & Semester Aktif
     */
    public function updateAcademicPeriod(Request $request)
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'semester'      => ['required', 'string', 'max:30'],
        ]);

        $newYear     = $validated['academic_year'];
        $newSemester = $validated['semester'];
        $newStart    = (int) substr($newYear, 0, 4);

        $config = SystemConfiguration::current();
        $config->update([
            'academic_year' => $newYear,
            'semester'      => $newSemester,
            'updated_by'    => auth()->id(),
        ]);

        // Perbarui status seluruh tahun ajaran secara otomatis
        if (Schema::hasTable('academic_years')) {
            $allYears = AcademicYear::all();
            foreach ($allYears as $ay) {
                $ayStart = (int) substr($ay->year, 0, 4);
                if ($ay->year === $newYear) {
                    $ay->update(['status' => 'Tahun ajaran berjalan']);
                } elseif ($ayStart < $newStart) {
                    $ay->update(['status' => 'Tahun ajaran selesai']);
                } else {
                    $ay->update(['status' => 'Tahun ajaran mendatang']);
                }
            }
        }

        return redirect()->back()->with('success', "Periode Tahun Ajaran {$newYear} • {$newSemester} berhasil diterapkan ke seluruh sistem!");
    }

        /**
     * Halaman Master Jam Pembelajaran dengan Filter Tab Hari
     */
    public function lessonHoursIndex(Request $request)
    {
        $selectedDay = $request->get('day', 'Senin'); // Default hari aktif: Senin
        $validDays   = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        if (!in_array($selectedDay, $validDays)) {
            $selectedDay = 'Senin';
        }

        if (Schema::hasTable('lesson_hours')) {
            $lessonHours = LessonHour::where('day_of_week', $selectedDay)
                ->orderBy('session_number')
                ->get();
            $allHoursCount = LessonHour::count();
        } else {
            $lessonHours = collect();
            $allHoursCount = 0;
        }

        // Rata-rata durasi aktif pada hari ini
        $activeDuration = $lessonHours->isNotEmpty() ? $lessonHours->first()->duration_minutes : 40;

        return view('waka.lesson-hours.index', compact('lessonHours', 'selectedDay', 'activeDuration', 'allHoursCount'));
    }

        /**
     * Ubah Jam Mulai (Khusus Jam 1) atau Durasi Menit per Sesi (Kalkulasi Otomatis Berantai)
     */
    public function lessonHoursUpdate(Request $request, LessonHour $lessonHour)
    {
        $validated = $request->validate([
            'start_time'       => ['nullable', 'string', 'max:10'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:90'],
            'notes'            => ['nullable', 'string', 'max:100'],
        ]);

        $newDuration = (int) $validated['duration_minutes'];
        $day = $lessonHour->day_of_week;

        // Khusus Jam ke-1: Boleh ganti jam mulai
        if ($lessonHour->session_number === 1 && !empty($validated['start_time'])) {
            $startTime = trim($validated['start_time']);
        } else {
            $startTime = $lessonHour->start_time;
        }

        // Kalkulasi jam selesai untuk sesi ini
        $parts = explode(':', $startTime);
        $totalMinutes = ((int) $parts[0] * 60) + (int) ($parts[1] ?? 0) + $newDuration;
        $endHours = str_pad(floor($totalMinutes / 60), 2, '0', STR_PAD_LEFT);
        $endMins  = str_pad($totalMinutes % 60, 2, '0', STR_PAD_LEFT);
        $newEndTime = "{$endHours}:{$endMins}";

        $lessonHour->update([
            'start_time'       => $startTime,
            'duration_minutes' => $newDuration,
            'end_time'         => $newEndTime,
            'notes'            => $validated['notes'],
        ]);

        // Rekalkulasi berantai (Cascading) untuk seluruh sesi setelahnya pada hari yang sama
        $nextSessions = LessonHour::where('day_of_week', $day)
            ->where('session_number', '>', $lessonHour->session_number)
            ->orderBy('session_number')
            ->get();

        $runningMinutes = $totalMinutes;

        foreach ($nextSessions as $next) {
            $num = $next->session_number;

            // Sisipkan waktu istirahat resmi sekolah
            if ($num === 4) {
                // Istirahat 1: 15 menit
                $runningMinutes += 15;
            } elseif ($num === 7) {
                // Istirahat 2: Khusus Jumat dikunci mulai 12:40 (setelah Sholat Jumat), selain itu 60 menit Ishoma
                if ($day === 'Jumat') {
                    $runningMinutes = max($runningMinutes + 75, (12 * 60) + 40);
                } else {
                    $runningMinutes += 60;
                }
            }

            $sH = str_pad(floor($runningMinutes / 60), 2, '0', STR_PAD_LEFT);
            $sM = str_pad($runningMinutes % 60, 2, '0', STR_PAD_LEFT);
            $nextStart = "{$sH}:{$sM}";

            $runningMinutes += $next->duration_minutes;

            $eH = str_pad(floor($runningMinutes / 60), 2, '0', STR_PAD_LEFT);
            $eM = str_pad($runningMinutes % 60, 2, '0', STR_PAD_LEFT);
            $nextEnd = "{$eH}:{$eM}";

            $next->update([
                'start_time' => $nextStart,
                'end_time'   => $nextEnd,
            ]);
        }

        return redirect()->back()->with('success', "Perubahan pada {$lessonHour->name} ({$lessonHour->day_of_week}) berhasil disimpan. Seluruh sesi berikutnya otomatis menyesuaikan.");
    }

    /**
     * Terapkan Durasi Baru: Universal (Semua Hari) atau Per Hari Tertentu
     * Sistem otomatis mengkalkulasi jam mulai & selesai per sesi sesuai agenda
     */
    public function lessonHoursApplyPreset(Request $request)
    {
        $validated = $request->validate([
            'target_day'       => ['required', 'in:all,Senin,Selasa,Rabu,Kamis,Jumat'],
            'duration_minutes' => ['required', 'integer', 'min:20', 'max:60'],
        ]);

        $duration  = (int) $validated['duration_minutes'];
        $targetDay = $validated['target_day'];
        $daysToUpdate = ($targetDay === 'all') ? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] : [$targetDay];

        if (Schema::hasTable('lesson_hours')) {
            foreach ($daysToUpdate as $day) {
                // Seluruh hari mulai tepat pukul 07:15 sesuai standar jadwal SMKN 1 Bantul
                $currentTime = (7 * 60) + 15; // 07:15
                $maxSessions = ($day === 'Jumat') ? 8 : 10; // Jumat 8 jam pelajaran, Senin-Kamis 10 jam pelajaran

                for ($sessionNum = 1; $sessionNum <= $maxSessions; $sessionNum++) {
                    // Sisipkan waktu istirahat sesuai agenda SMKN 1 Bantul
                    if ($sessionNum === 4) {
                        // Istirahat 1: 15 menit (09:30 - 09:45)
                        $currentTime += 15;
                    } elseif ($sessionNum === 7) {
                        // Istirahat 2 (Ishoma / Sholat Jumat): 30 menit (12:00 - 12:30)
                        $currentTime += 30;
                    }

                    $startH = str_pad(floor($currentTime / 60), 2, '0', STR_PAD_LEFT);
                    $startM = str_pad($currentTime % 60, 2, '0', STR_PAD_LEFT);
                    $startStr = "{$startH}:{$startM}";

                    $currentTime += $duration;

                    $endH = str_pad(floor($currentTime / 60), 2, '0', STR_PAD_LEFT);
                    $endM = str_pad($currentTime % 60, 2, '0', STR_PAD_LEFT);
                    $endStr = "{$endH}:{$endM}";

                    LessonHour::updateOrCreate(
                        ['day_of_week' => $day, 'session_number' => $sessionNum],
                        [
                            'name'             => "Jam ke - {$sessionNum}",
                            'start_time'       => $startStr,
                            'end_time'         => $endStr,
                            'duration_minutes' => $duration,
                            'notes'            => ($sessionNum === $maxSessions) ? 'Sesi Terakhir' : 'Sesi Pembelajaran',
                        ]
                    );
                }
            }
        }

        $targetText = ($targetDay === 'all') ? "Seluruh Hari (Senin - Jumat)" : "Hari {$targetDay}";
        return redirect()->route('waka.lesson-hours.index', ['day' => ($targetDay === 'all' ? 'Senin' : $targetDay)])
            ->with('success', "Sukses! Durasi jam pembelajaran untuk {$targetText} berhasil disetel menjadi {$duration} Menit per sesi. Jam pembelajaran otomatis terkalkulasi ulang.");
    }

        /**
     * Master Data: Jurusan (Major) dengan Filter Dropdown Tingkat Kelas
     */
    public function majorsIndex(Request $request)
    {
        // Otomatis seed 8 Jurusan awal jika tabel masih kosong
        if (Major::count() === 0) {
            $initialMajors = [
                ['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak',     'rombel_x_xi' => 2, 'rombel_xii' => 2, 'description' => 'Pengembangan perangkat lunak, pemrograman web & aplikasi mobile.'],
                ['code' => 'TKJ', 'name' => 'Teknik Komputer Jaringan',     'rombel_x_xi' => 2, 'rombel_xii' => 2, 'description' => 'Infrastruktur jaringan, routing switching, dan administrasi server.'],
                ['code' => 'DKV', 'name' => 'Desain Komunikasi Visual',     'rombel_x_xi' => 2, 'rombel_xii' => 2, 'description' => 'Desain grafis, multimedia digital, videografi komersial & animasi.'],
                ['code' => 'MP',  'name' => 'Manajemen Perkantoran',        'rombel_x_xi' => 2, 'rombel_xii' => 2, 'description' => 'Tata kelola administrasi perkantoran modern dan kearsipan digital.'],
                ['code' => 'BR',  'name' => 'Bisnis Ritel',                 'rombel_x_xi' => 2, 'rombel_xii' => 2, 'description' => 'Manajemen toko modern, display produk, dan rantai pasok ritel.'],
                ['code' => 'LPS', 'name' => 'Layanan Perbankan Syariah',     'rombel_x_xi' => 2, 'rombel_xii' => 1, 'description' => 'Operasional perbankan syariah, akuntansi syariah, dan layanan teller.'],
                ['code' => 'AKL', 'name' => 'Akuntansi Keuangan Lembaga',   'rombel_x_xi' => 3, 'rombel_xii' => 4, 'description' => 'Siklus akuntansi, komputer akuntansi (MYOB/Accurate), dan perpajakan.'],
                ['code' => 'BD',  'name' => 'Bisnis Digital',                'rombel_x_xi' => 1, 'rombel_xii' => 1, 'description' => 'Pemasaran online, digital marketing, dan e-commerce.'],
            ];

            foreach ($initialMajors as $m) {
                Major::create($m);
            }
        }

        // Pilihan filter tingkat kelas (Default: Kelas 10)
        $selectedGrade = $request->get('grade', '10');
        $romanGrade = ($selectedGrade == '10') ? 'X' : (($selectedGrade == '11') ? 'XI' : 'XII');

        $majors = Major::orderBy('name')->get()->map(function ($major) use ($selectedGrade, $romanGrade) {
            // Ambil daftar kelas aktif khusus untuk tingkat yang dipilih
            $gradeClasses = SchoolClass::where(function ($q) use ($romanGrade, $major) {
                $q->where('class_name', 'like', "{$romanGrade} {$major->code} %")
                  ->orWhere('class_name', 'like', "{$romanGrade} {$major->code}");
            })->withCount('users')->get();

            $totalStudentsInGrade = $gradeClasses->sum('users_count');
            $currentRombel = ($selectedGrade == '12') 
                ? ($major->rombel_xii ?? 2) 
                : (($selectedGrade == '11') ? ($major->rombel_xi ?? 2) : ($major->rombel_x ?? 2));

            return (object) [
                'id'                 => $major->id,
                'code'               => $major->code,
                'name'               => $major->name,
                'rombel_x'           => $major->rombel_x ?? $major->rombel_x_xi ?? 2,
                'rombel_xi'          => $major->rombel_xi ?? $major->rombel_x_xi ?? 2,
                'rombel_xii'         => $major->rombel_xii ?? 2,
                'current_rombel'     => $currentRombel,
                'description'        => $major->description,
                'classes'            => $gradeClasses,
                'total_classes'      => $gradeClasses->count(),
                'total_students'     => $totalStudentsInGrade,
            ];
        });

        $config = SystemConfiguration::first() ?? SystemConfiguration::create([
            'active_cycle'       => 'Putaran 1',
            'academic_year'      => '2026/2027',
            'semester'           => 'Semester 1 (Ganjil)',
            'is_schedule_locked' => false,
        ]);

        return view('waka.majors.index', compact('majors', 'config', 'selectedGrade', 'romanGrade'));
    }

    public function majorsStore(Request $request)
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:20', 'unique:majors,code'],
            'name'        => ['required', 'string', 'max:100'],
            'rombel_x'    => ['required', 'integer', 'min:1', 'max:10'],
            'rombel_xi'   => ['required', 'integer', 'min:1', 'max:10'],
            'rombel_xii'  => ['required', 'integer', 'min:1', 'max:10'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        // Keep rombel_x_xi in sync for backward compatibility
        $validated['rombel_x_xi'] = $validated['rombel_x'];
        Major::create($validated);

        return redirect()->route('waka.majors.index')->with('success', 'Data Jurusan baru berhasil ditambahkan.');
    }

    public function majorsUpdate(Request $request, Major $major)
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:20', 'unique:majors,code,' . $major->id],
            'name'        => ['required', 'string', 'max:100'],
            'rombel_x'    => ['required', 'integer', 'min:1', 'max:10'],
            'rombel_xi'   => ['required', 'integer', 'min:1', 'max:10'],
            'rombel_xii'  => ['required', 'integer', 'min:1', 'max:10'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['rombel_x_xi'] = $validated['rombel_x'];
        $major->update($validated);

        return redirect()->route('waka.majors.index')->with('success', 'Data Jurusan berhasil diperbarui.');
    }

    public function majorsDestroy(Major $major)
    {
        // Cek apakah ada kelas yang terkait
        $hasClass = SchoolClass::where('class_name', 'like', "% {$major->code} %")
            ->orWhere('class_name', 'like', "% {$major->code}")
            ->exists();

        if ($hasClass) {
            return redirect()->back()->with('error', 'Jurusan tidak dapat dihapus karena masih memiliki rombel/kelas yang terdaftar.');
        }

        $major->delete();

        return redirect()->route('waka.majors.index')->with('success', 'Data Jurusan berhasil dihapus.');
    }

    /**
     * Perbarui method classesIndex untuk mendukung filter lengkap
     */
    public function classesIndex(Request $request)
    {
        $query = SchoolClass::withCount('users')
            ->with(['homeroomTeachers', 'users']);

        // Filter Tingkat Kelas (X, XI, XII)
        if ($request->filled('grade')) {
            $grade = $request->grade;
            $query->where('class_name', 'like', "{$grade} %");
        }

        // Filter Jurusan
        if ($request->filled('major')) {
            $majorCode = $request->major;
            $query->where(function ($q) use ($majorCode) {
                $q->where('class_name', 'like', "% {$majorCode} %")
                  ->orWhere('class_name', 'like', "% {$majorCode}");
            });
        }

        // Filter Status Wali Kelas
        if ($request->filled('homeroom_status')) {
            if ($request->homeroom_status === 'has') {
                $query->has('homeroomTeachers');
            } elseif ($request->homeroom_status === 'none') {
                $query->doesntHave('homeroomTeachers');
            }
        }

        // Filter Pencarian Teks Menyeluruh & Case-Insensitive (tidak sensitif huruf kapital)
        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(class_name) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('homeroomTeachers', function ($sq) use ($search) {
                      $sq->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(username) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(COALESCE(nip, \'\')) LIKE ?', ["%{$search}%"]);
                  });
            });
        }

        $perPage = in_array((int)$request->input('per_page'), [50, 100, 500, 1000]) ? (int)$request->input('per_page') : 50;

        $classes = $query->orderBy('class_name')->paginate($perPage)->withQueryString();

        // Daftar Guru untuk Dropdown Wali Kelas
        $teachers = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas']))
            ->orderBy('name')
            ->get();

        $majors = Major::orderBy('name')->get();

        // Hitung Statistik
        $totalClasses = SchoolClass::count();
        $totalStudents = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))->where('student_status', 'Aktif')->count();
        $assignedHomerooms = User::whereNotNull('homeroom_class_id')->count();

        return view('waka.classes.index', compact('classes', 'teachers', 'majors', 'totalClasses', 'totalStudents', 'assignedHomerooms'));
    }

    /**
     * Update Data Kelas & Penugasan Wali Kelas
     */
    public function classesUpdate(Request $request, SchoolClass $schoolClass)
    {
        $validated = $request->validate([
            'class_name'          => ['required', 'string', 'max:50', 'unique:classes,class_name,' . $schoolClass->id],
            'homeroom_teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        $schoolClass->update(['class_name' => $validated['class_name']]);

        // Atur penugasan Wali Kelas & Sinkronisasi Peran (Role) Otomatis
        if ($request->has('homeroom_teacher_id')) {
            $newTeacherId = $validated['homeroom_teacher_id'] ?? null;
            $oldTeachers  = User::where('homeroom_class_id', $schoolClass->id)->get();

            $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
            $waliRole = Role::firstOrCreate(['role_name' => 'Wali Kelas']);

            // 1. Lepas penugasan lama: ubah peran guru yang sebelumnya memegang kelas ini kembali menjadi 'Guru'
            foreach ($oldTeachers as $oldTeacher) {
                if (!$newTeacherId || $oldTeacher->id != $newTeacherId) {
                    $oldTeacher->update([
                        'homeroom_class_id' => null,
                        'role_id'           => $guruRole->id,
                    ]);
                }
            }

            // 2. Beri penugasan baru: ubah peran guru baru menjadi 'Wali Kelas'
            if (!empty($newTeacherId)) {
                $newTeacher = User::find($newTeacherId);
                if ($newTeacher) {
                    $newTeacher->update([
                        'homeroom_class_id' => $schoolClass->id,
                        'role_id'           => $waliRole->id,
                    ]);
                }
            }
        }

        return redirect()->route('waka.classes.index')->with('success', 'Data kelas dan penugasan wali kelas berhasil diperbarui.');
    }

        /**
     * Master Data: Tingkat Kelas & Siswa Lulus (Dengan Filter Terpisah: Tingkat, Jurusan, Rombel)
     */
    public function gradesIndex(Request $request)
    {
        $activeTab = $request->get('tab', 'X'); // 'X', 'XI', 'XII', atau 'lulus'

        // Hitung statistik ringkasan per tingkat
        $countX = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->where('student_status', 'Aktif')
            ->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', 'X %'))
            ->count();

        $countXI = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->where('student_status', 'Aktif')
            ->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', 'XI %'))
            ->count();

        $countXII = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->where('student_status', 'Aktif')
            ->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', 'XII %'))
            ->count();

        $countLulus = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->where('student_status', 'Lulus')
            ->count();

        // Kueri siswa berdasarkan tab aktif
        $query = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->with(['schoolClass', 'parent']);

        if ($activeTab === 'lulus') {
            $query->where('student_status', 'Lulus');
        } else {
            $query->where('student_status', 'Aktif')
                  ->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', "{$activeTab} %"));
        }

        // Filter Spesifik: Jurusan
        if ($request->filled('major')) {
            $major = $request->major;
            $query->whereHas('schoolClass', function ($q) use ($major) {
                $q->where('class_name', 'like', "% {$major} %")
                  ->orWhere('class_name', 'like', "% {$major}");
            });
        }

        // Filter Spesifik: Rombel
        if ($request->filled('rombel')) {
            $rombel = $request->rombel;
            $query->whereHas('schoolClass', function ($q) use ($rombel) {
                $q->where('class_name', 'like', "% {$rombel}");
            });
        }

        // Filter Pencarian Teks Menyeluruh & Case-Insensitive (tidak sensitif huruf kapital)
        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(COALESCE(nis, \'\')) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(username) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(COALESCE(phone, \'\')) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('schoolClass', fn($sq) => $sq->whereRaw('LOWER(class_name) LIKE ?', ["%{$search}%"]));
            });
        }

        $perPage = in_array((int)$request->input('per_page'), [50, 100, 500, 1000]) ? (int)$request->input('per_page') : 50;

        $students = $query->orderBy('name')->paginate($perPage)->withQueryString();
        $majors = Major::orderBy('name')->get();
        $allClasses = SchoolClass::orderBy('class_name')->get();

        return view('waka.grades.index', compact(
            'students', 'majors', 'allClasses', 'activeTab',
            'countX', 'countXI', 'countXII', 'countLulus'
        ));
    }


    /**
     * Edit Cepat Siswa dari Halaman Tingkat
     */
    public function gradesUpdate(Request $request, User $student)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'nis'            => ['nullable', 'string', 'max:30'],
            'class_id'       => ['nullable', 'exists:classes,id'],
            'student_status' => ['required', 'in:Aktif,Lulus'],
        ]);

        $student->update($validated);

        return redirect()->back()->with('success', "Data siswa {$student->name} berhasil diperbarui.");
    }

        /**
     * Mengubah status siswa antara Aktif dan Lulus (Alumni)
     */
    public function gradesToggleStatus(User $student)
    {
        $newStatus = ($student->student_status === 'Lulus') ? 'Aktif' : 'Lulus';
        $student->update(['student_status' => $newStatus]);

        $pesan = ($newStatus === 'Lulus')
            ? "Siswa {$student->name} berhasil ditandai sebagai LULUS."
            : "Siswa {$student->name} berhasil diaktifkan kembali ke data siswa aktif.";

        return redirect()->back()->with('success', $pesan);
    }

    /**
     * Kenaikan Kelas / Kelulusan untuk SATU SISWA
     */
    public function gradesPromote(User $student)
    {
        $currentClass = $student->schoolClass;
        if (!$currentClass) {
            return redirect()->back()->with('error', 'Siswa belum terdaftar pada kelas manapun.');
        }

        $className = trim($currentClass->class_name);

        // Jika Kelas 10 (X) -> Naik ke Kelas 11 (XI)
        if (str_starts_with($className, 'X ')) {
            $targetName = preg_replace('/^X\s+/', 'XI ', $className);
            $targetClass = SchoolClass::firstOrCreate(['class_name' => $targetName]);
            $student->update(['class_id' => $targetClass->id]);
            return redirect()->back()->with('success', "Siswa {$student->name} berhasil dinaikkan ke kelas {$targetClass->class_name}!");
        }

        // Jika Kelas 11 (XI) -> Naik ke Kelas 12 (XII)
        if (str_starts_with($className, 'XI ')) {
            $targetName = preg_replace('/^XI\s+/', 'XII ', $className);
            $targetClass = SchoolClass::firstOrCreate(['class_name' => $targetName]);
            $student->update(['class_id' => $targetClass->id]);
            return redirect()->back()->with('success', "Siswa {$student->name} berhasil dinaikkan ke kelas {$targetClass->class_name}!");
        }

        // Jika Kelas 12 (XII) -> Lulus menjadi Alumni
        if (str_starts_with($className, 'XII ')) {
            $student->update(['student_status' => 'Lulus']);
            return redirect()->back()->with('success', "Siswa {$student->name} berhasil diluluskan menjadi Alumni!");
        }

        return redirect()->back()->with('error', 'Format nama kelas siswa tidak dikenali.');
    }

    /**
     * Kenaikan Kelas MASSAL (Seluruh Siswa Sekaligus)
     */
    public function gradesPromoteMass(Request $request)
    {
        $fromGrade = $request->input('from_grade'); // 'X' atau 'XI'
        $toGrade   = ($fromGrade === 'X') ? 'XI' : 'XII';

        $query = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->where('student_status', 'Aktif')
            ->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', "{$fromGrade} %"));

        // Dukung kenaikan kelas sesuai filter jurusan / rombel yang sedang dibuka
        if ($request->filled('major')) {
            $major = $request->major;
            $query->whereHas('schoolClass', function ($q) use ($major) {
                $q->where('class_name', 'like', "% {$major} %")
                  ->orWhere('class_name', 'like', "% {$major}");
            });
        }
        if ($request->filled('rombel')) {
            $rombel = $request->rombel;
            $query->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', "% {$rombel}"));
        }

        $students = $query->with('schoolClass')->get();
        if ($students->isEmpty()) {
            return redirect()->back()->with('error', "Tidak ada siswa di tingkat {$fromGrade} untuk dinaikkan kelas.");
        }

        $count = 0;
        foreach ($students as $student) {
            if ($student->schoolClass) {
                $currentName = $student->schoolClass->class_name;
                $targetName = preg_replace('/^' . preg_quote($fromGrade, '/') . '\s+/', "{$toGrade} ", $currentName);
                $targetClass = SchoolClass::firstOrCreate(['class_name' => $targetName]);
                $student->update(['class_id' => $targetClass->id]);
                $count++;
            }
        }

        return redirect()->back()->with('success', "Sukses! Sebanyak {$count} siswa berhasil dinaikkan ke tingkat {$toGrade}.");
    }

    /**
     * Kelulusan MASSAL Siswa Kelas 12 Menjadi Alumni
     */
    public function gradesGraduateMass(Request $request)
    {
        $query = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->where('student_status', 'Aktif')
            ->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', 'XII %'));

        if ($request->filled('major')) {
            $major = $request->major;
            $query->whereHas('schoolClass', function ($q) use ($major) {
                $q->where('class_name', 'like', "% {$major} %")
                  ->orWhere('class_name', 'like', "% {$major}");
            });
        }
        if ($request->filled('rombel')) {
            $rombel = $request->rombel;
            $query->whereHas('schoolClass', fn($q) => $q->where('class_name', 'like', "% {$rombel}"));
        }

        $students = $query->get();
        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada siswa aktif kelas 12 untuk diluluskan.');
        }

        $count = $students->count();
        foreach ($students as $s) {
            $s->update(['student_status' => 'Lulus']);
        }

        return redirect()->back()->with('success', "Sukses! Sebanyak {$count} siswa kelas 12 berhasil diluluskan menjadi Alumni.");
    }

        /**
     * Kenaikan Kelas / Kelulusan Berdasarkan Sistem Checklist
     * Siswa yang dicentang naik kelas/lulus; siswa yang TIDAK dicentang otomatis tinggal kelas (tetap).
     */
    public function gradesPromoteSelected(Request $request)
    {
        $fromGrade    = $request->input('from_grade'); // 'X', 'XI', atau 'XII'
        $selectedIds  = $request->input('selected_students', []); // ID siswa yang DICENTANG
        $evaluatedIds = $request->input('evaluated_students', []); // ID seluruh siswa yang dievaluasi

        if (empty($evaluatedIds)) {
            return redirect()->back()->with('error', 'Tidak ada data siswa yang diproses.');
        }

        $toGrade = ($fromGrade === 'X') ? 'XI' : (($fromGrade === 'XI') ? 'XII' : 'Lulus');
        $promotedCount = 0;
        $retainedCount = 0;

        foreach ($evaluatedIds as $studentId) {
            $student = User::find($studentId);
            if (!$student) continue;

            // Jika siswa tercentang -> NAIK KELAS / LULUS
            if (in_array($studentId, $selectedIds)) {
                if ($fromGrade === 'XII') {
                    $student->update(['student_status' => 'Lulus']);
                    $promotedCount++;
                } else {
                    if ($student->schoolClass) {
                        $currentName = $student->schoolClass->class_name;
                        $targetName = preg_replace('/^' . preg_quote($fromGrade, '/') . '\s+/', "{$toGrade} ", $currentName);
                        $targetClass = SchoolClass::firstOrCreate(['class_name' => $targetName]);
                        $student->update(['class_id' => $targetClass->id]);
                        $promotedCount++;
                    }
                }
            } else {
                // Siswa TIDAK dicentang -> TINGGAL KELAS (status dan kelas tetap)
                $retainedCount++;
            }
        }

        $actionText   = ($fromGrade === 'XII') ? "diluluskan menjadi Alumni" : "dinaikkan ke tingkat {$toGrade}";
        $retainedText = ($fromGrade === 'XII') ? "tetap aktif di Kelas 12" : "tinggal di tingkat {$fromGrade}";

        $message = "Proses Berhasil! Sebanyak {$promotedCount} siswa {$actionText}, dan {$retainedCount} siswa {$retainedText}.";

        return redirect()->back()->with('success', $message);
    }
}
