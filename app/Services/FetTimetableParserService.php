<?php

namespace App\Services;

use App\Models\Major;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;

class FetTimetableParserService
{
    public function __construct(
        protected SpreadsheetImportService $spreadsheetService,
        protected SchoolDocumentParserService $schoolDocService
    ) {}

    /**
     * Memproses berkas jadwal (PDF FET, HTML FET, atau Excel/CSV) dengan Auto-Provisioning.
     * Mendukung:
     * 1. Jadwal per Kelas (PDF / HTML / Excel)
     * 2. Jadwal per Guru (PDF / HTML / Excel)
     * 3. Jadwal Ruang (PDF Matriks Ruang FET)
     */
    public function import(UploadedFile $file, string $cycleType = 'Putaran 1', string $duplicateAction = 'replace'): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'pdf') {
            return $this->importFromPdf($file->getRealPath(), $cycleType, $duplicateAction);
        } elseif (in_array($extension, ['html', 'htm'])) {
            return $this->importFromHtml(file_get_contents($file->getRealPath()), $cycleType, $duplicateAction);
        } else {
            // Excel / CSV
            return $this->importFromSpreadsheet($file, $cycleType, $duplicateAction);
        }
    }

    /**
     * Import dari file PDF jadwal FET SMKN 1 Bantul (Mendukung Jadwal Kelas, Jadwal Guru, dan Jadwal Ruang)
     */
    protected function importFromPdf(string $path, string $cycleType, string $duplicateAction = 'replace'): array
    {
        // 1. Coba Parser Python (pdfplumber) untuk akurasi layout visual 2D dan tabel tertinggi
        $pyResult = $this->spreadsheetService->runPythonParser($path);
        if ($pyResult) {
            $imported = 0;
            $skipped = 0;
            $createdTeachers = 0;
            $createdClasses = 0;
            $createdRooms = 0;

            // A. Daftarkan seluruh legenda ruangan yang terdeteksi
            if (!empty($pyResult['rooms'])) {
                foreach ($pyResult['rooms'] as $rItem) {
                    $name = trim($rItem['name']);
                    $this->resolveRoom($name, $createdRooms);
                }
            }

            // B. (Dokumen jadwal hanya mencocokkan data guru yang ada, tidak membuat guru baru)

            // C. Jika Python berhasil mengekstrak jadwal langsung dari tabel FET (Jadwal Kelas / Jadwal Guru)
            if (!empty($pyResult['schedules'])) {
                \Illuminate\Support\Facades\DB::transaction(function () use (
                    $pyResult, $cycleType, $duplicateAction, &$imported, &$skipped, &$createdTeachers, &$createdClasses, &$createdRooms
                ) {
                    // IN-MEMORY CACHING UNTUK PENCOCOKAN GURU
                    $allTeachers = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Admin']))
                        ->with('role')
                        ->get();

                    $allClasses = SchoolClass::all()->keyBy(fn($c) => strtoupper(trim($c->class_name)));
                    $allRooms = Room::all()->keyBy(fn($r) => strtoupper(trim($r->room_name)));
                    $allMajors = Major::all()->keyBy(fn($m) => strtoupper(trim($m->code)));

                    // Pre-load data jadwal yang sudah ada pada siklus ini untuk pengecekan duplikasi
                    $existingSchedules = Schedule::where('cycle_type', $cycleType)
                        ->select('id', 'class_id', 'day_of_week', 'start_time')
                        ->get();

                    $existingScheduleMap = [];
                    foreach ($existingSchedules as $s) {
                        $key = "{$s->class_id}_{$s->day_of_week}_{$s->start_time}";
                        $existingScheduleMap[$key] = $s->id;
                    }

                    $schedulesToDelete = [];
                    $schedulesToInsert = [];
                    $now = now();

                    foreach ($pyResult['schedules'] as $item) {
                        // 1. Resolve Kelas & Jurusan
                        $normClass = $this->normalizeClassName($item['class']);
                        if (empty($normClass)) continue;
                        $normClassUpper = strtoupper($normClass);

                        if (isset($allClasses[$normClassUpper])) {
                            $classId = $allClasses[$normClassUpper]->id;
                        } else {
                            $this->schoolDocService->autoProvisionMajor($normClass, $createdClasses, $allMajors);
                            $sc = SchoolClass::firstOrCreate(['class_name' => $normClass]);
                            if ($sc->wasRecentlyCreated) {
                                $createdClasses++;
                            }
                            $allClasses[$normClassUpper] = $sc;
                            $classId = $sc->id;
                        }

                        // 2. Resolve Guru: HANYA MENCOCOKKAN DENGAN DATA GURU, TIDAK MEMBUAT AKUN BARU
                        $tCode = $item['teacher_code'] ?? null;
                        $tName = trim($item['teacher_name'] ?? '');
                        $teacher = $this->findTeacherStrict(
                            rawName: $tName,
                            code: $tCode,
                            allTeachers: $allTeachers
                        );

                        if (!$teacher) {
                            $skipped++;
                            continue; // Jika guru tidak ditemukan di Data Guru, lewati baris jadwal ini
                        }

                        // 3. Resolve Ruangan
                        $mappedRoom = $this->canonicalRoomName($item['room'] ?? 'Ruang Teori');
                        $mappedRoomUpper = strtoupper($mappedRoom);
                        if (isset($allRooms[$mappedRoomUpper])) {
                            $roomId = $allRooms[$mappedRoomUpper]->id;
                        } else {
                            $r = Room::firstOrCreate(['room_name' => $mappedRoom]);
                            if ($r->wasRecentlyCreated) {
                                $createdRooms++;
                            }
                            $allRooms[$mappedRoomUpper] = $r;
                            $roomId = $r->id;
                        }

                        $day = $item['day'];
                        if (isset($item['start_session']) && isset($item['end_session'])) {
                            $startSession = sprintf('%02d:00:00', (int)$item['start_session']);
                            $endSession   = sprintf('%02d:00:00', (int)$item['end_session']);
                        } else {
                            $rawStart = $item['start_time'];
                            $startSession = $this->mapTimeToSession($rawStart);
                            $endSession = !empty($item['end_time']) ? $this->mapTimeToSession($item['end_time']) : $this->calculateNextSession($startSession, $day);
                        }

                        $slotKey = "{$classId}_{$day}_{$startSession}";

                        // Cek apakah slot jadwal sudah ada di sistem
                        if (isset($existingScheduleMap[$slotKey])) {
                            if ($duplicateAction === 'keep') {
                                $skipped++;
                                continue; // Pertahankan data awal yang ada di sistem, lewati data baru
                            }
                        }

                        $schedulesToInsert[] = [
                            'teacher_id'  => $teacher->id,
                            'class_id'    => $classId,
                            'room_id'     => $roomId,
                            'subject'     => trim($item['subject']) ?: 'Mata Pelajaran Umum',
                            'day_of_week' => $day,
                            'start_time'  => $startSession,
                            'end_time'    => $endSession,
                            'cycle_type'  => $cycleType,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];
                        $existingScheduleMap[$slotKey] = true;
                    }

                    if ($duplicateAction === 'replace' && !empty($schedulesToInsert)) {
                        $targetClassIds = array_unique(array_column($schedulesToInsert, 'class_id'));
                        Schedule::where('cycle_type', $cycleType)
                            ->whereIn('class_id', $targetClassIds)
                            ->delete();
                    }

                    foreach (array_chunk($schedulesToInsert, 200) as $chunk) {
                        Schedule::insert($chunk);
                        $imported += count($chunk);
                    }
                });

                return [
                    'imported'         => $imported,
                    'skipped'          => $skipped,
                    'created_teachers' => $createdTeachers,
                    'created_classes'  => $createdClasses,
                    'created_rooms'    => $createdRooms,
                ];
            }

            // C2. Jika dokumen adalah Matriks Jadwal Ruang (2304 room assignments)
            if (!empty($pyResult['room_assignments'])) {
                $imported = $this->importRoomAssignments(
                    $pyResult,
                    $cycleType,
                    $createdRooms,
                    $createdClasses
                );

                return [
                    'imported'         => $imported,
                    'skipped'          => $skipped,
                    'created_teachers' => $createdTeachers,
                    'created_classes'  => $createdClasses,
                    'created_rooms'    => $createdRooms,
                ];
            }

            // D. Fallback ke pembacaan baris per baris per halaman hasil Python
            if (!empty($pyResult['pages'])) {
                foreach ($pyResult['pages'] as $pageData) {
                    $text = $pageData['text'] ?? '';
                    if (str_contains(strtolower($text), 'daftar isi') && !str_contains($text, 'Senin')) {
                        continue;
                    }

                    if (str_contains(strtoupper($text), 'JADWAL RUANG') || str_contains($text, 'Laboratorium Komputer')) {
                        $this->parseRoomLegend($text, $createdRooms);
                        $imported += $this->parseRoomMatrixPage($text, $cycleType, $createdTeachers, $createdClasses, $createdRooms);
                        continue;
                    }

                    $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));
                    if (empty($lines)) continue;

                    $topLines = array_slice($lines, 0, 5);
                    $topText = implode(' ', $topLines);

                    if (preg_match('/"(\d+)\.\s*([^"]+)"/', $topText, $teacherHeaderMatch)) {
                        $teacherCode = $teacherHeaderMatch[1];
                        $teacherName = trim($teacherHeaderMatch[2]);
                        $teacher = $this->findTeacherStrict($teacherName, $teacherCode);
                        if (!$teacher) {
                            $skipped++;
                            continue;
                        }

                        $imported += $this->parseTeacherTimetablePage(
                            $lines,
                            $teacher,
                            $cycleType,
                            $createdClasses,
                            $createdRooms,
                            $duplicateAction,
                            $skipped
                        );
                        continue;
                    }

                    if (preg_match('/(?:SMK NEGERI 1 BANTUL\s+)?([X|XI|XII]+\s+[A-Z0-9]+)/i', $topText, $classMatch)) {
                        $rawClassName = trim($classMatch[1]);
                        $className = $this->normalizeClassName($rawClassName);
                        $schoolClass = $this->resolveClass($className, $createdClasses);

                        $imported += $this->parseClassTimetablePage(
                            $lines,
                            $schoolClass,
                            $cycleType,
                            $createdTeachers,
                            $createdRooms,
                            $duplicateAction,
                            $skipped
                        );
                    }
                }

                if ($imported > 0 || $skipped > 0) {
                    return [
                        'imported'         => $imported,
                        'skipped'          => $skipped,
                        'created_teachers' => $createdTeachers,
                        'created_classes'  => $createdClasses,
                        'created_rooms'    => $createdRooms,
                    ];
                }
            }
        }

        // 2. Fallback native PDF parser
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);
        $pages = $pdf->getPages();

        $imported = 0;
        $skipped = 0;
        $createdTeachers = 0;
        $createdClasses = 0;
        $createdRooms = 0;

        foreach ($pages as $page) {
            $text = $page->getText();

            // Lewati halaman daftar isi
            if (str_contains(strtolower($text), 'daftar isi') && !str_contains($text, 'Senin')) {
                continue;
            }

            // =================================================================
            // DOKUMEN TYPE 3: JADWAL RUANG & LEGENDA LABORATORIUM
            // =================================================================
            if (str_contains(strtoupper($text), 'JADWAL RUANG') || str_contains($text, 'Laboratorium Komputer')) {
                $this->parseRoomLegend($text, $createdRooms);
                // Jika halaman matriks ruangan
                $imported += $this->parseRoomMatrixPage($text, $cycleType, $createdTeachers, $createdClasses, $createdRooms);
                continue;
            }

            // Ambil 5 baris pertama untuk mendeteksi tipe halaman
            $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));
            if (empty($lines)) continue;

            $topLines = array_slice($lines, 0, 5);
            $topText = implode(' ', $topLines);

            // =================================================================
            // DOKUMEN TYPE 2: JADWAL PER GURU (Header: "Kode.Nama Guru, Gelar")
            // Contoh: "82. RAHMAWATI YOGA PAMUNGKAS, M.Pd"
            // =================================================================
            if (preg_match('/"(\d+)\.\s*([^"]+)"/', $topText, $teacherHeaderMatch)) {
                $teacherCode = $teacherHeaderMatch[1];
                $teacherName = trim($teacherHeaderMatch[2]);
                $teacher = $this->findTeacherStrict($teacherName, $teacherCode);
                if (!$teacher) {
                    $skipped++;
                    continue;
                }

                $imported += $this->parseTeacherTimetablePage(
                    $lines,
                    $teacher,
                    $cycleType,
                    $createdClasses,
                    $createdRooms,
                    $duplicateAction,
                    $skipped
                );
                continue;
            }

            // =================================================================
            // DOKUMEN TYPE 1: JADWAL PER KELAS (Header: "X RPL1", "XI TKJ2", dst)
            // =================================================================
            if (preg_match('/(?:SMK NEGERI 1 BANTUL\s+)?([X|XI|XII]+\s+[A-Z0-9]+)/i', $topText, $classMatch)) {
                $rawClassName = trim($classMatch[1]);
                $className = $this->normalizeClassName($rawClassName);
                $schoolClass = $this->resolveClass($className, $createdClasses);

                $imported += $this->parseClassTimetablePage(
                    $lines,
                    $schoolClass,
                    $cycleType,
                    $createdTeachers,
                    $createdRooms,
                    $duplicateAction,
                    $skipped
                );
            }
        }

        return [
            'imported'         => $imported,
            'skipped'          => $skipped,
            'created_teachers' => $createdTeachers,
            'created_classes'  => $createdClasses,
            'created_rooms'    => $createdRooms,
        ];
    }

    /**
     * Parse Jadwal per Kelas (Document 1)
     */
    protected function parseClassTimetablePage(
        array $lines,
        SchoolClass $schoolClass,
        string $cycleType,
        int &$createdTeachers,
        int &$createdRooms,
        string $duplicateAction = 'replace',
        int &$skipped = 0
    ): int {
        $count = 0;
        $currentDay = 'Senin';
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $currentSession = '01:00';
        $totalLines = count($lines);

        for ($i = 0; $i < $totalLines; $i++) {
            $line = $lines[$i];

            // Deteksi jam waktu (misal: "07:15", "08:00", "08:45", dsb)
            if (preg_match('/^(\d{1,2})[:.](\d{2})$/', $line)) {
                $currentSession = $this->mapTimeToSession($line);
                continue;
            }

            // Deteksi nama hari
            foreach ($days as $d) {
                if (strcasecmp($line, $d) === 0) {
                    $currentDay = $d;
                    break;
                }
            }

            // Deteksi Guru FET: "Kode.Nama Guru"
            if (preg_match('/^"(\d+)\.\s*([^"]+)"$/', $line, $tm)) {
                $teacherCode = $tm[1];
                $teacherName = trim($tm[2]);

                // Mapel: ambil baris sebelum nama guru
                $subject = 'Mata Pelajaran Umum';
                if ($i > 0) {
                    $prev = $lines[$i - 1];
                    if (!in_array($prev, $days) && !preg_match('/^\d{1,2}[:.]\d{2}$/', $prev) && !str_starts_with($prev, '"') && $prev !== '-x-') {
                        $subject = $prev;
                    }
                }

                // Ruang: ambil baris sesudah nama guru (LAB 16 / OR1 / LAB IPA dsb)
                $roomName = 'Ruang Teori';
                if ($i + 1 < $totalLines) {
                    $nxt = $lines[$i + 1];
                    if ($this->isRoomName($nxt)) {
                        $roomName = $nxt;
                    }
                }

                $teacher = $this->findTeacherStrict($teacherName, $teacherCode);
                if (!$teacher) {
                    $skipped++;
                    continue;
                }
                $room = $this->resolveRoom($roomName, $createdRooms);

                $sessionEnd = $this->calculateNextSession($currentSession, $currentDay);

                $existing = Schedule::where('class_id', $schoolClass->id)
                    ->where('day_of_week', $currentDay)
                    ->where('start_time', $currentSession)
                    ->where('cycle_type', $cycleType)
                    ->first();

                if ($existing) {
                    if ($duplicateAction === 'keep') {
                        $skipped++;
                        continue;
                    }
                    $existing->update([
                        'teacher_id' => $teacher->id,
                        'room_id'    => $room->id,
                        'subject'    => $subject,
                        'end_time'   => $sessionEnd,
                    ]);
                } else {
                    Schedule::create([
                        'teacher_id'  => $teacher->id,
                        'class_id'    => $schoolClass->id,
                        'room_id'     => $room->id,
                        'subject'     => $subject,
                        'day_of_week' => $currentDay,
                        'start_time'  => $currentSession,
                        'end_time'    => $sessionEnd,
                        'cycle_type'  => $cycleType,
                    ]);
                }

                $count++;
            }
        }

        return $count;
    }

    /**
     * Parse Jadwal per Guru (Document 2)
     */
    protected function parseTeacherTimetablePage(
        array $lines,
        User $teacher,
        string $cycleType,
        int &$createdClasses,
        int &$createdRooms,
        string $duplicateAction = 'replace',
        int &$skipped = 0
    ): int {
        $count = 0;
        $currentDay = 'Senin';
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $currentSession = '01:00';
        $totalLines = count($lines);

        for ($i = 0; $i < $totalLines; $i++) {
            $line = $lines[$i];

            if (preg_match('/^(\d{1,2})[:.](\d{2})$/', $line)) {
                $currentSession = $this->mapTimeToSession($line);
                continue;
            }

            foreach ($days as $d) {
                if (strcasecmp($line, $d) === 0) {
                    $currentDay = $d;
                    break;
                }
            }

            // Cari kemunculan nama kelas di dalam sel (misal: "X RPL1", "XI TKJ2", "XII BR1")
            if (preg_match('/^([X|XI|XII]+)\s*([A-Za-z]+)(\d*)$/i', $line, $cm)) {
                $className = $this->normalizeClassName($line);
                $schoolClass = $this->resolveClass($className, $createdClasses);

                // Mapel sesudah kelas
                $subject = 'Mata Pelajaran Umum';
                if ($i + 1 < $totalLines) {
                    $subLine = $lines[$i + 1];
                    if (!in_array($subLine, $days) && !preg_match('/^\d{1,2}[:.]\d{2}$/', $subLine) && !str_starts_with($subLine, '"') && $subLine !== '-x-') {
                        $subject = $subLine;
                    }
                }

                // Ruang jika ada
                $roomName = 'Ruang Teori';
                if ($i + 2 < $totalLines && $this->isRoomName($lines[$i + 2])) {
                    $roomName = $lines[$i + 2];
                }
                $room = $this->resolveRoom($roomName, $createdRooms);

                $sessionEnd = $this->calculateNextSession($currentSession, $currentDay);

                $existing = Schedule::where('class_id', $schoolClass->id)
                    ->where('day_of_week', $currentDay)
                    ->where('start_time', $currentSession)
                    ->where('cycle_type', $cycleType)
                    ->first();

                if ($existing) {
                    if ($duplicateAction === 'keep') {
                        $skipped++;
                        continue;
                    }
                    $existing->update([
                        'teacher_id' => $teacher->id,
                        'room_id'    => $room->id,
                        'subject'    => $subject,
                        'end_time'   => $sessionEnd,
                    ]);
                } else {
                    Schedule::create([
                        'teacher_id'  => $teacher->id,
                        'class_id'    => $schoolClass->id,
                        'room_id'     => $room->id,
                        'subject'     => $subject,
                        'day_of_week' => $currentDay,
                        'start_time'  => $currentSession,
                        'end_time'    => $sessionEnd,
                        'cycle_type'  => $cycleType,
                    ]);
                }

                $count++;
            }
        }

        return $count;
    }

    /**
     * Parse Matriks Ruangan (Document 3)
     */
    protected function parseRoomMatrixPage(
        string $text,
        string $cycleType,
        int &$createdTeachers,
        int &$createdClasses,
        int &$createdRooms
    ): int {
        // Matriks ruangan digunakan untuk memastikan seluruh ruang lab dan kode ruangan terdaftar
        $this->parseRoomLegend($text, $createdRooms);
        return 0;
    }

    /**
     * Membaca keterangan legenda ruangan pada footer FET Matriks Ruangan
     * (Contoh: L.1 : Laboratorium Komputer 1, L.IPA : Laboratorium IPA, OR : Lapangan Olahraga, HALL : Ruang untuk menari)
     */
    protected function parseRoomLegend(string $text, int &$createdRooms): void
    {
        // Pola: "L.1 : Laboratorium Komputer 1" atau "OR : Lapangan Olahraga"
        // Menggunakan lookahead agar tidak serakah menelan definisi ruangan berikutnya
        if (preg_match_all('/(L\.[A-Za-z0-9\.]+|OR\d*|HALL|L\.IPA|L\.PB)\s*:\s*(.*?)(?=(?:L\.[A-Za-z0-9\.]+|OR\d*|HALL|L\.IPA|L\.PB)\s*:|$)/is', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $code = strtoupper(trim($m[1]));
                $name = trim(preg_replace('/\s+/', ' ', $m[2]));
                $name = trim($name, " \t\n\r\0\x0B,.;-");
                if (empty($name)) continue;

                $room = Room::where('room_name', $name)->orWhere('room_name', $code)->first();
                if (!$room) {
                    Room::create([
                        'room_name' => $name,
                    ]);
                    $createdRooms++;
                }

                // Daftarkan juga kode singkat jika belum ada (misal: "L.1") agar cocok saat jadwal menggunakan kode lab
                if ($code !== $name && !Room::where('room_name', $code)->exists()) {
                    Room::create([
                        'room_name' => $code,
                    ]);
                }
            }
        }
    }

    /**
     * Import dari HTML hasil ekspor software FET
     */
    protected function importFromHtml(string $htmlContent, string $cycleType, string $duplicateAction = 'replace'): array
    {
        $imported = 0;
        $skipped = 0;
        $createdTeachers = 0;
        $createdClasses = 0;
        $createdRooms = 0;

        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlContent);
        $tables = $dom->getElementsByTagName('table');

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        foreach ($tables as $table) {
            $className = 'X RPL 1';
            $schoolClass = $this->resolveClass($className, $createdClasses);

            $rows = $table->getElementsByTagName('tr');
            foreach ($rows as $rowIndex => $row) {
                $cells = $row->getElementsByTagName('td');
                foreach ($cells as $cellIndex => $cell) {
                    $cellText = trim($cell->textContent);
                    if (preg_match('/"(\d+)\.\s*([^"]+)"/', $cellText, $m)) {
                        $teacherCode = $m[1];
                        $teacherName = trim($m[2]);

                        $lines = array_filter(array_map('trim', explode("\n", $cellText)));
                        $subject = $lines[0] ?? 'Mata Pelajaran';
                        $roomName = end($lines);
                        if (!$this->isRoomName($roomName)) {
                            $roomName = 'Ruang Teori';
                        }

                        $teacher = $this->findTeacherStrict($teacherName, $teacherCode);
                        if (!$teacher) {
                            $skipped++;
                            continue;
                        }
                        $room = $this->resolveRoom($roomName, $createdRooms);
                        $day = $days[$cellIndex % 5] ?? 'Senin';

                        $sessionHour = str_pad(min(max($rowIndex, 1), 10), 2, '0', STR_PAD_LEFT) . ':00';
                        $sessionEnd  = $this->calculateNextSession($sessionHour, $day);

                        $existing = Schedule::where('class_id', $schoolClass->id)
                            ->where('day_of_week', $day)
                            ->where('start_time', $sessionHour)
                            ->where('cycle_type', $cycleType)
                            ->first();

                        if ($existing) {
                            if ($duplicateAction === 'keep') {
                                $skipped++;
                                continue;
                            }
                            $existing->update([
                                'teacher_id' => $teacher->id,
                                'room_id'    => $room->id,
                                'subject'    => $subject,
                                'end_time'   => $sessionEnd,
                            ]);
                        } else {
                            Schedule::create([
                                'teacher_id'  => $teacher->id,
                                'class_id'    => $schoolClass->id,
                                'room_id'     => $room->id,
                                'subject'     => $subject,
                                'day_of_week' => $day,
                                'start_time'  => $sessionHour,
                                'end_time'    => $sessionEnd,
                                'cycle_type'  => $cycleType,
                            ]);
                        }

                        $imported++;
                    }
                }
            }
        }

        return [
            'imported'         => $imported,
            'skipped'          => $skipped,
            'created_teachers' => $createdTeachers,
            'created_classes'  => $createdClasses,
            'created_rooms'    => $createdRooms,
        ];
    }

    /**
     * Import dari spreadsheet Excel / CSV dengan auto-provisioning
     */
    protected function importFromSpreadsheet(UploadedFile $file, string $cycleType, string $duplicateAction = 'replace'): array
    {
        $rows = $this->spreadsheetService->parse($file);
        $config = SystemConfiguration::current();

        $imported = 0;
        $skipped = 0;
        $createdTeachers = 0;
        $createdClasses = 0;
        $createdRooms = 0;

        foreach ($rows as $row) {
            $teacherInput = trim($row['nama_guru_atau_nip'] ?? $row['nama_guru___nip'] ?? $row['guru'] ?? $row['nama_guru'] ?? $row['nip'] ?? $row['teacher'] ?? '');
            if (empty($teacherInput)) continue;

            $className = trim($row['kelas'] ?? $row['nama_kelas'] ?? $row['class'] ?? '');
            if (empty($className)) continue;

            $roomName = trim($row['ruang'] ?? $row['ruang_aktual'] ?? $row['nama_ruang'] ?? $row['ruangan'] ?? $row['room'] ?? 'Ruang Teori');
            $subject  = trim($row['mapel'] ?? $row['mata_pelajaran'] ?? $row['subject'] ?? 'Mata Pelajaran Umum');

            $dayRaw   = ucfirst(strtolower(trim($row['hari'] ?? $row['day'] ?? 'Senin')));
            $dayOfWeek = in_array($dayRaw, Schedule::DAYS) ? $dayRaw : 'Senin';

            $startRaw = trim((string)($row['jam_mulai'] ?? $row['jam_ke'] ?? $row['start'] ?? '1'));
            $endRaw   = trim((string)($row['jam_selesai'] ?? $row['end'] ?? $startRaw));
            $startTime = str_contains($startRaw, ':') ? $startRaw : str_pad((int)$startRaw, 2, '0', STR_PAD_LEFT) . ':00';
            $endTime   = str_contains($endRaw, ':') ? $endRaw : str_pad((int)$endRaw, 2, '0', STR_PAD_LEFT) . ':00';

            // Resolve Teacher (HANYA MENCOCOKKAN, TIDAK MEMBUAT BARU)
            $teacher = $this->findTeacherStrict($teacherInput);
            if (!$teacher) {
                $skipped++;
                continue;
            }
            $schoolClass = $this->resolveClass($className, $createdClasses);
            $room = $this->resolveRoom($roomName, $createdRooms);

            $putaranRaw = trim($row['putaran'] ?? $row['siklus'] ?? $cycleType);
            $putaran = in_array($putaranRaw, ['Putaran 1', 'Putaran 2']) ? $putaranRaw : ($config->active_cycle ?? 'Putaran 1');

            $existing = Schedule::where('class_id', $schoolClass->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $startTime)
                ->where('cycle_type', $putaran)
                ->first();

            if ($existing) {
                if ($duplicateAction === 'keep') {
                    $skipped++;
                    continue;
                }
                $existing->update([
                    'teacher_id' => $teacher->id,
                    'room_id'    => $room->id,
                    'subject'    => $subject,
                    'end_time'   => $endTime,
                ]);
            } else {
                Schedule::create([
                    'teacher_id'  => $teacher->id,
                    'class_id'    => $schoolClass->id,
                    'room_id'     => $room->id,
                    'subject'     => $subject,
                    'day_of_week' => $dayOfWeek,
                    'start_time'  => $startTime,
                    'end_time'    => $endTime,
                    'cycle_type'  => $putaran,
                ]);
            }

            $imported++;
        }

        $this->consolidateSportsRooms();

        return [
            'imported'         => $imported,
            'skipped'          => $skipped,
            'created_teachers' => $createdTeachers,
            'created_classes'  => $createdClasses,
            'created_rooms'    => $createdRooms,
        ];
    }

    /**
     * Menyatukan seluruh varian ruangan olahraga (OR1, OR2, OR3, Lapangan Olahraga 1/2/3) ke Lapangan Olahraga
     */
    public function consolidateSportsRooms(): void
    {
        try {
            $mainSportRoom = Room::whereRaw('UPPER(TRIM(room_name)) = ?', ['LAPANGAN OLAHRAGA'])->first();
            if ($mainSportRoom) {
                $dupSportRooms = Room::whereIn('room_name', [
                    'Lapangan Olahraga 1', 'Lapangan Olahraga 2', 'Lapangan Olahraga 3',
                    'OR1', 'OR2', 'OR3', 'Lapangan 1', 'Lapangan 2', 'Lapangan 3'
                ])->get();

                foreach ($dupSportRooms as $dr) {
                    Schedule::where('room_id', $dr->id)->update(['room_id' => $mainSportRoom->id]);
                    $dr->delete();
                }
            }
        } catch (\Throwable $e) {
            // silent fallback
        }
    }
    }

    /**
     * Mencari dan mencocokkan akun guru yang sudah ada di sistem (HANYA MENCOCOKKAN, TIDAK MEMBUAT BARU)
     */
    public function findTeacherStrict(
        string $rawName,
        ?string $code = null,
        $allTeachers = null
    ): ?User {
        $cleanName = preg_replace('/^\d+[\.\)]\s*/', '', trim($rawName, " \t\n\r\0\x0B\"'"));

        if ($allTeachers !== null) {
            // 1. Cari dengan flexible name matching
            $teacher = $this->schoolDocService->findTeacherByNameFlexible($cleanName, $allTeachers);

            // 2. Jika tidak ditemukan lewat nama, cari lewat NIP atau username guru_{code}
            if (!$teacher && !empty($code)) {
                $codeStr = trim((string)$code);
                $teacher = $allTeachers->first(fn($t) => 
                    (string)$t->nip === $codeStr || 
                    $t->username === "guru_{$codeStr}" ||
                    $t->username === "guru_{$codeStr}_"
                );
            }

            return $teacher;
        }

        // Query database langsung jika koleksi in-memory tidak dioper
        $teacher = $this->schoolDocService->findTeacherByNameFlexible($cleanName);

        if (!$teacher && !empty($code)) {
            $codeStr = trim((string)$code);
            $teacher = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Admin']))
                ->where(function ($q) use ($codeStr) {
                    $q->where('nip', $codeStr)
                      ->orWhere('username', "guru_{$codeStr}");
                })->first();
        }

        return $teacher;
    }

    /**
     * Helper kompatibilitas pencarian akun guru (hanya mencocokkan)
     */
    public function findOrCreateTeacherSafe(
        string $rawName,
        ?string $code,
        &$allTeachers,
        array &$existingUsernames,
        array &$existingEmails,
        Role $guruRole,
        int &$createdCounter
    ): ?User {
        return $this->findTeacherStrict($rawName, $code, $allTeachers);
    }

    /**
     * Otomatis mencari dan mencocokkan akun Guru (Fallback Helper - Tidak membuat akun baru)
     */
    public function resolveTeacher(string $name, ?string $code = null, int &$createdCounter = 0): ?User
    {
        return $this->findTeacherStrict($name, $code);
    }

    /**
     * Otomatis mencari atau membuat Kelas & Jurusan
     */
    public function resolveClass(string $className, int &$createdCounter): SchoolClass
    {
        $normalized = $this->normalizeClassName($className);
        $schoolClass = SchoolClass::where('class_name', $normalized)->first();

        if ($schoolClass) {
            return $schoolClass;
        }

        $dummy = 0;
        $this->schoolDocService->autoProvisionMajor($normalized, $dummy);

        $schoolClass = SchoolClass::firstOrCreate(['class_name' => $normalized]);
        if ($schoolClass->wasRecentlyCreated) {
            $createdCounter++;
        }

        return $schoolClass;
    }

    /**
     * Memetakan kode ruangan (LAB 16, L.16, 12, OR, HALL, dll) ke nama ruangan standar
     */
    public function canonicalRoomName(string $raw): string
    {
        $c = strtoupper(trim((string)$raw));
        if ($c === '' || $c === '-X-' || $c === 'RUANG TEORI') return 'Ruang 1';
        if (preg_match('/^(?:LAB\s*|L\.\s*)(\d+)$/i', $c, $m)) {
            return "Laboratorium Komputer " . (int)$m[1];
        }
        if (preg_match('/^(?:LAB\s*IPA|L\.\s*IPA|ABORATORIUM)/i', $c)) return 'Laboratorium IPA';
        if (preg_match('/^(?:OR\d*|LAPANGAN(?:\s*OLAHRAGA)?(?:\s*\d+)?|OLAHRAGA)$/i', $c)) return 'Lapangan Olahraga';
        if ($c === 'HALL' || str_contains($c, 'MENARI') || str_contains($c, 'TARI')) return 'Ruang Seni Tari / Hall';
        if (preg_match('/^(?:RUANG|R\.?)\s*(\d+)$/i', $c, $m) || preg_match('/^(\d+)$/', $c, $m)) {
            return "Ruang " . (int)$m[1];
        }
        if ($c === 'L.PB' || str_contains($c, 'PENATAAN') || str_contains($c, 'BARANG')) return 'Laboratorium Penataan Barang';
        return trim($raw);
    }

    /**
     * Otomatis mencari atau membuat Ruangan (Case-Insensitive agar tidak duplikat)
     */
    public function resolveRoom(string $roomName, int &$createdCounter): Room
    {
        $clean = $this->canonicalRoomName($roomName);

        $room = Room::whereRaw('UPPER(TRIM(room_name)) = ?', [strtoupper(trim($clean))])->first();
        if ($room) {
            return $room;
        }

        $room = Room::create([
            'room_name' => $clean,
        ]);

        $createdCounter++;
        return $room;
    }

    /**
     * Memproses Matriks Jadwal Ruang (2.304 entri penempatan ruang per kelas/hari/sesi):
     * 1. Menyelaraskan master lesson_hours jika belum terisi
     * 2. Memetakan lokasi ruangan presisi (Ruang 12, LAB 16, HALL, Lapangan Olahraga, dll.) ke setiap jadwal kelas aktif
     */
    public function importRoomAssignments(
        array $pyResult,
        string $cycleType,
        int &$createdRooms,
        int &$createdClasses
    ): int {
        // 1. Sinkronisasi master lesson_hours awal jika ada deteksi jam dari berkas
        if (!empty($pyResult['lesson_hours']) && \Illuminate\Support\Facades\Schema::hasTable('lesson_hours')) {
            $existingLh = \App\Models\LessonHour::all()
                ->keyBy(fn($lh) => "{$lh->day_of_week}_{$lh->session_number}");
            $lhToInsert = [];
            foreach ($pyResult['lesson_hours'] as $lh) {
                $key = "{$lh['day']}_{$lh['session']}";
                if (!isset($existingLh[$key])) {
                    $lhToInsert[] = [
                        'day_of_week'      => $lh['day'],
                        'session_number'   => (int) $lh['session'],
                        'name'             => "Jam ke - {$lh['session']}",
                        'start_time'       => $lh['start'],
                        'end_time'         => $lh['end'],
                        'duration_minutes' => 40,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ];
                }
            }
            if (!empty($lhToInsert)) {
                \App\Models\LessonHour::insert($lhToInsert);
            }
        }

        // 2. Preload seluruh Rooms dan Classes yang ada ke memory
        $allRooms = Room::all()->keyBy(fn($r) => strtoupper(trim($r->room_name)));
        $allClasses = SchoolClass::all()->keyBy(fn($c) => strtoupper(trim($c->class_name)));

        // 3. Preload SEMUA Jadwal pada siklus ini dalam 1 query tunggal (~700 baris, butuh hanya ~100ms)
        $schedules = Schedule::where('cycle_type', $cycleType)
            ->select('id', 'class_id', 'day_of_week', 'start_time', 'end_time', 'room_id')
            ->get();

        $scheduleMap = [];
        foreach ($schedules as $sch) {
            $key = "{$sch->class_id}_{$sch->day_of_week}";
            $scheduleMap[$key][] = [
                'id'       => $sch->id,
                'start'    => (int) substr($sch->start_time, 0, 2),
                'end'      => (int) substr($sch->end_time, 0, 2),
                'room_id'  => $sch->room_id,
            ];
        }

        $pendingRoomUpdates = []; // [$roomId => [$schId => $schId]]

        // 4. Proses pencocokan seluruh 2.304 entri di memori PHP (selesai dalam < 0.05 detik)
        foreach ($pyResult['room_assignments'] as $item) {
            $normClass = $this->normalizeClassName($item['class']);
            if (empty($normClass)) continue;
            $normClassUpper = strtoupper($normClass);

            if (!isset($allClasses[$normClassUpper])) {
                $sc = SchoolClass::firstOrCreate(['class_name' => $normClass]);
                if ($sc->wasRecentlyCreated) {
                    $createdClasses++;
                }
                $allClasses[$normClassUpper] = $sc;
            }
            $classId = $allClasses[$normClassUpper]->id;

            // Resolve room
            $mappedRoom = $this->canonicalRoomName($item['room']);
            $mappedRoomUpper = strtoupper($mappedRoom);
            if (isset($allRooms[$mappedRoomUpper])) {
                $roomId = $allRooms[$mappedRoomUpper]->id;
            } else {
                $r = Room::firstOrCreate(['room_name' => $mappedRoom]);
                if ($r->wasRecentlyCreated) {
                    $createdRooms++;
                }
                $allRooms[$mappedRoomUpper] = $r;
                $roomId = $r->id;
            }

            $day = $item['day'];
            $sessionNum = (int) $item['session'];
            $mapSlotKey = "{$classId}_{$day}";

            if (!isset($scheduleMap[$mapSlotKey])) {
                continue;
            }

            foreach ($scheduleMap[$mapSlotKey] as &$schRef) {
                if ($sessionNum >= $schRef['start'] && $sessionNum <= $schRef['end']) {
                    if ($schRef['room_id'] !== $roomId) {
                        $pendingRoomUpdates[$roomId][$schRef['id']] = $schRef['id'];
                        $schRef['room_id'] = $roomId; // tandai di memory agar tidak diduplikasi
                    }
                }
            }
            unset($schRef);
        }

        // 5. Eksekusi batch updates grouped by target roomId (~20-30 query saja, selesai dalam 1-2 detik)
        $updatedCount = 0;
        if (!empty($pendingRoomUpdates)) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($pendingRoomUpdates, &$updatedCount) {
                foreach ($pendingRoomUpdates as $rId => $schIds) {
                    $chunkIds = array_values($schIds);
                    $affected = Schedule::whereIn('id', $chunkIds)->update(['room_id' => $rId]);
                    $updatedCount += $affected;
                }
            });
        }

        return $updatedCount;
    }

    /**
     * Normalisasi nama kelas (misal: "X RPL1" -> "X RPL 1", "XI TKJ2" -> "XI TKJ 2", "X BD" -> "X BD")
     */
    public function normalizeClassName(string $raw): string
    {
        $trimmed = trim($raw);
        // Jika format "XI RPL1" (tanpa spasi sebelum angka rombel)
        if (preg_match('/^(XII|XI|X)\s*([A-Za-z]+)\s*(\d+)$/i', $trimmed, $m)) {
            return strtoupper($m[1]) . ' ' . strtoupper($m[2]) . ' ' . $m[3];
        }
        // Jika format "XI BD" (tanpa angka rombel)
        if (preg_match('/^(XII|XI|X)\s*([A-Za-z]+)$/i', $trimmed, $m)) {
            return strtoupper($m[1]) . ' ' . strtoupper($m[2]);
        }
        return $trimmed;
    }

    /**
     * Cek apakah string adalah nama ruangan/laboratorium
     */
    public function isRoomName(string $str): bool
    {
        $upper = strtoupper(trim($str));
        return str_starts_with($upper, 'LAB') ||
               str_starts_with($upper, 'OR') ||
               str_starts_with($upper, 'HALL') ||
               str_starts_with($upper, 'L.') ||
               str_starts_with($upper, 'RUANG') ||
               $upper === 'OR1' || $upper === 'OR2' || $upper === 'OR3';
    }

    /**
     * Mengonversi waktu jam (07:15, 08:00, 08:45, dsb) ke sesi pelajaran 1 - 10
     */
    public function mapTimeToSession(string $time): string
    {
        $clean = str_replace('.', ':', trim($time));
        if (preg_match('/^(\d{1,2}):(\d{2})/', $clean, $m)) {
            $h = (int)$m[1];
            $min = (int)$m[2];
            $totalMin = ($h * 60) + $min;

            if ($totalMin <= 480) return '01:00';      // ~07:15 - 08:00 (Jam 1)
            if ($totalMin <= 525) return '02:00';      // ~08:00 - 08:45 (Jam 2)
            if ($totalMin <= 585) return '03:00';      // ~08:45 - 09:45 (Jam 3)
            if ($totalMin <= 630) return '04:00';      // ~09:45 - 10:30 (Jam 4)
            if ($totalMin <= 675) return '05:00';      // ~10:30 - 11:15 (Jam 5)
            if ($totalMin <= 750) return '06:00';      // ~11:15 - 12:30 (Jam 6)
            if ($totalMin <= 795) return '07:00';      // ~12:30 - 13:15 (Jam 7)
            if ($totalMin <= 840) return '08:00';      // ~13:15 - 14:00 (Jam 8)
            if ($totalMin <= 885) return '09:00';      // ~14:00 - 14:45 (Jam 9)
            return '10:00';                            // ~14:45+ (Jam 10)
        }
        return '01:00';
    }

    /**
     * Menghitung sesi berakhir
     */
    public function calculateNextSession(string $startSession, string $day): string
    {
        $startNum = (int) substr($startSession, 0, 2);
        $nextNum = $startNum + 1;

        if ($day === 'Jumat' && $nextNum > 8) {
            $nextNum = 8;
        } elseif ($nextNum > 10) {
            $nextNum = 10;
        }

        return str_pad($nextNum, 2, '0', STR_PAD_LEFT) . ':00';
    }
}
