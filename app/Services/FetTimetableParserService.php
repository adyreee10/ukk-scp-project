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
        protected SpreadsheetImportService $spreadsheetService
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
                    $code = strtoupper(trim($rItem['code']));
                    $name = trim($rItem['name']);
                    $this->resolveRoom($name, $createdRooms);
                    if ($code !== $name) {
                        $this->resolveRoom($code, $createdRooms);
                    }
                }
            }

            // B. Daftarkan seluruh guru dari daftar isi FET jika ada
            if (!empty($pyResult['teachers'])) {
                foreach ($pyResult['teachers'] as $tItem) {
                    $this->resolveTeacher($tItem['name'], $tItem['code'] ?? null, $createdTeachers);
                }
            }

            // C. Jika Python berhasil mengekstrak jadwal langsung dari tabel FET (Jadwal Kelas / Jadwal Guru)
            if (!empty($pyResult['schedules'])) {
                \Illuminate\Support\Facades\DB::transaction(function () use (
                    $pyResult, $cycleType, $duplicateAction, &$imported, &$skipped, &$createdTeachers, &$createdClasses, &$createdRooms
                ) {
                    $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);

                    $teachersByName = [];
                    $teachersByCode = [];
                    foreach (User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))->get() as $t) {
                        $teachersByName[strtolower(trim($t->name))] = $t;
                        if ($t->nip) $teachersByCode[$t->nip] = $t;
                    }

                    $classesByName = SchoolClass::pluck('id', 'class_name')->toArray();
                    $roomsByName = Room::pluck('id', 'room_name')->toArray();
                    $defaultPassword = Hash::make('password123');

                    // Pre-create any missing teachers from pyResult
                    foreach ($pyResult['teachers'] ?? [] as $tItem) {
                        $tCode = $tItem['code'] ?? null;
                        $tName = trim($tItem['name']);
                        $tKey = strtolower($tName);
                        if (!isset($teachersByName[$tKey]) && (!$tCode || !isset($teachersByCode[$tCode]))) {
                            $baseUser = $tCode ? "guru_{$tCode}" : Str::slug($tName, '.');
                            $uniqueUser = substr($baseUser ?: 'guru_' . Str::random(4), 0, 35);
                            $newT = User::create([
                                'name'     => $tName,
                                'username' => $uniqueUser,
                                'nip'      => $tCode,
                                'email'    => "{$uniqueUser}@guru.smk1bantul.sch.id",
                                'password' => $defaultPassword,
                                'role_id'  => $guruRole->id,
                            ]);
                            $teachersByName[$tKey] = $newT;
                            if ($tCode) $teachersByCode[$tCode] = $newT;
                            $createdTeachers++;
                        }
                    }

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
                        $normClass = $this->normalizeClassName($item['class']);
                        if (empty($normClass)) continue;

                        if (!isset($classesByName[$normClass])) {
                            $sc = SchoolClass::create(['class_name' => $normClass]);
                            $classesByName[$normClass] = $sc->id;
                            $createdClasses++;
                        }
                        $classId = $classesByName[$normClass];

                        $tCode = $item['teacher_code'] ?? null;
                        $tName = trim($item['teacher_name'] ?? '');
                        $tKey = strtolower($tName);
                        $teacher = $teachersByName[$tKey] ?? ($tCode && isset($teachersByCode[$tCode]) ? $teachersByCode[$tCode] : null);
                        if (!$teacher) {
                            $baseUser = $tCode ? "guru_{$tCode}" : Str::slug($tName, '.');
                            $uniqueUser = substr($baseUser ?: 'guru_' . Str::random(4), 0, 35);
                            $teacher = User::create([
                                'name'     => $tName ?: 'Guru Pengajar',
                                'username' => $uniqueUser,
                                'nip'      => $tCode,
                                'email'    => "{$uniqueUser}@guru.smk1bantul.sch.id",
                                'password' => $defaultPassword,
                                'role_id'  => $guruRole->id,
                            ]);
                            $teachersByName[$tKey] = $teacher;
                            if ($tCode) $teachersByCode[$tCode] = $teacher;
                            $createdTeachers++;
                        }

                        $mappedRoom = $this->canonicalRoomName($item['room'] ?? 'Ruang Teori');
                        if (!isset($roomsByName[$mappedRoom])) {
                            $r = Room::create(['room_name' => $mappedRoom]);
                            $roomsByName[$mappedRoom] = $r->id;
                            $createdRooms++;
                        }
                        $roomId = $roomsByName[$mappedRoom];

                        $day = $item['day'];
                        $rawStart = $item['start_time'];
                        $startSession = $this->mapTimeToSession($rawStart);
                        $endSession = $this->calculateNextSession($startSession, $day);

                        $slotKey = "{$classId}_{$day}_{$startSession}";

                        // Cek apakah slot jadwal sudah ada di sistem
                        if (isset($existingScheduleMap[$slotKey])) {
                            if ($duplicateAction === 'keep') {
                                $skipped++;
                                continue; // Pertahankan data awal yang ada di sistem, lewati data baru
                            } else {
                                // Ganti / perbarui yang baru: jadwalkan penghapusan jadwal lama yang bentrok
                                $oldId = $existingScheduleMap[$slotKey];
                                if (is_numeric($oldId)) {
                                    $schedulesToDelete[] = $oldId;
                                }
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

                    if (!empty($schedulesToDelete)) {
                        Schedule::whereIn('id', array_unique($schedulesToDelete))->delete();
                    }

                    foreach (array_chunk($schedulesToInsert, 100) as $chunk) {
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
                        $teacher = $this->resolveTeacher($teacherName, $teacherCode, $createdTeachers);

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
                $teacher = $this->resolveTeacher($teacherName, $teacherCode, $createdTeachers);

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

                $teacher = $this->resolveTeacher($teacherName, $teacherCode, $createdTeachers);
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

                        $teacher = $this->resolveTeacher($teacherName, $teacherCode, $createdTeachers);
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
            $teacherInput = trim($row['nama_guru_atau_nip'] ?? $row['guru'] ?? $row['nama_guru'] ?? $row['nip'] ?? '');
            if (empty($teacherInput)) continue;

            $className = trim($row['kelas'] ?? $row['nama_kelas'] ?? '');
            if (empty($className)) continue;

            $roomName = trim($row['ruang'] ?? $row['nama_ruang'] ?? $row['ruangan'] ?? 'Ruang Teori');
            $subject  = trim($row['mapel'] ?? $row['mata_pelajaran'] ?? 'Mata Pelajaran Umum');

            $dayRaw   = ucfirst(strtolower(trim($row['hari'] ?? 'Senin')));
            $dayOfWeek = in_array($dayRaw, Schedule::DAYS) ? $dayRaw : 'Senin';

            $startRaw = trim((string)($row['jam_mulai'] ?? '1'));
            $endRaw   = trim((string)($row['jam_selesai'] ?? '2'));
            $startTime = str_contains($startRaw, ':') ? $startRaw : str_pad((int)$startRaw, 2, '0', STR_PAD_LEFT) . ':00';
            $endTime   = str_contains($endRaw, ':') ? $endRaw : str_pad((int)$endRaw, 2, '0', STR_PAD_LEFT) . ':00';

            // Auto-resolve Teacher, Class, Room
            $teacher = $this->resolveTeacher($teacherInput, null, $createdTeachers);
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

        return [
            'imported'         => $imported,
            'skipped'          => $skipped,
            'created_teachers' => $createdTeachers,
            'created_classes'  => $createdClasses,
            'created_rooms'    => $createdRooms,
        ];
    }

    /**
     * Otomatis mencari atau membuat akun Guru
     */
    public function resolveTeacher(string $name, ?string $code, int &$createdCounter): User
    {
        $cleanName = preg_replace('/^\d+\.\s*/', '', trim($name, " \t\n\r\0\x0B\"'"));

        $teacher = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))
            ->where(function ($q) use ($cleanName, $code) {
                $q->where('name', $cleanName)
                  ->orWhere('name', 'like', "%{$cleanName}%");
                if ($code) {
                    $q->orWhere('nip', $code);
                }
            })->first();

        if ($teacher) {
            return $teacher;
        }

        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
        $baseUser = $code ? "guru_{$code}" : Str::slug($cleanName, '.');
        $uniqueUser = $baseUser;
        $counter = 1;
        while (User::where('username', $uniqueUser)->exists()) {
            $uniqueUser = "{$baseUser}{$counter}";
            $counter++;
        }

        $email = "{$uniqueUser}@guru.smk1bantul.sch.id";
        $c = 1;
        while (User::where('email', $email)->exists()) {
            $email = "{$uniqueUser}{$c}@guru.smk1bantul.sch.id";
            $c++;
        }

        $teacher = User::create([
            'name'     => $cleanName,
            'username' => $uniqueUser,
            'nip'      => $code,
            'email'    => $email,
            'password' => Hash::make('password123'),
            'role_id'  => $guruRole->id,
        ]);

        $createdCounter++;
        return $teacher;
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

        // Otomatis buat Jurusan jika belum ada (misal: "X RPL 1" -> Major "RPL")
        if (preg_match('/^(?:X|XI|XII)\s+([A-Z]+)/i', $normalized, $matches)) {
            $majorCode = strtoupper($matches[1]);
            Major::firstOrCreate(
                ['code' => $majorCode],
                [
                    'name'        => "Jurusan {$majorCode}",
                    'rombel_x_xi' => 2,
                    'rombel_xii'  => 2,
                ]
            );
        }

        $schoolClass = SchoolClass::create(['class_name' => $normalized]);
        $createdCounter++;

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
        if (preg_match('/^(?:OR\d*|LAPANGAN)$/i', $c)) return 'Lapangan Olahraga';
        if ($c === 'HALL' || str_contains($c, 'MENARI')) return 'Ruang Hall / Tari';
        if (preg_match('/^(\d+)$/', $c, $m)) return "Ruang " . (int)$m[1];
        if (preg_match('/^R\.\s*(\d+)$/i', $c, $m)) return "Ruang " . (int)$m[1];
        return $raw;
    }

    /**
     * Otomatis mencari atau membuat Ruangan
     */
    public function resolveRoom(string $roomName, int &$createdCounter): Room
    {
        $clean = $this->canonicalRoomName($roomName);

        $room = Room::where('room_name', $clean)->first();
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
     * Normalisasi nama kelas (misal: "X RPL1" -> "X RPL 1", "XI TKJ2" -> "XI TKJ 2", "X BD" -> "X BD")
     */
    public function normalizeClassName(string $raw): string
    {
        $trimmed = trim($raw);
        // Jika format "X RPL1" (tanpa spasi sebelum angka rombel)
        if (preg_match('/^([X|XI|XII]+)\s*([A-Za-z]+)(\d+)$/i', $trimmed, $m)) {
            return strtoupper($m[1]) . ' ' . strtoupper($m[2]) . ' ' . $m[3];
        }
        // Jika format "X BD" (tanpa angka rombel)
        if (preg_match('/^([X|XI|XII]+)\s*([A-Za-z]+)$/i', $trimmed, $m)) {
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
