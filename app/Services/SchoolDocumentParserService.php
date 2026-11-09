<?php

namespace App\Services;

use App\Models\Major;
use App\Models\Role;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;

class SchoolDocumentParserService
{
    public function __construct(
        protected SpreadsheetImportService $spreadsheetService
    ) {}

    // =========================================================================
    // 1. DATA GURU (TEACHERS): PDF & EXCEL/CSV AUTO-PROVISIONING
    // =========================================================================

    /**
     * Memproses berkas data guru baik PDF maupun Spreadsheet (Excel/CSV)
     */
    public function importTeachers(UploadedFile $file, string $duplicateAction = 'replace'): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'pdf') {
            return $this->parseTeachersPdf($file->getRealPath(), $duplicateAction);
        }

        return $this->parseTeachersSpreadsheet($file, $duplicateAction);
    }

    /**
     * Parsing Data Guru dari Berkas PDF (Mendukung Dokumen FET, SK Mengajar, & Daftar Guru)
     */
    protected function parseTeachersPdf(string $path, string $duplicateAction = 'replace'): array
    {
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
        $waliRole = Role::where('role_name', 'Wali Kelas')->first() ?? $guruRole;
        $bkRole   = Role::where('role_name', 'Guru BK')->first() ?? $guruRole;

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $createdClasses = 0;
        $createdMajors = 0;

        // 1. Coba Parser Python (pdfplumber) terlebih dahulu
        $pyResult = $this->spreadsheetService->runPythonParser($path);
        if ($pyResult && !empty($pyResult['teachers'])) {
            \Illuminate\Support\Facades\DB::transaction(function () use (
                $pyResult, $guruRole, $waliRole, $duplicateAction, &$created, &$updated, &$skipped, &$createdClasses, &$createdMajors
            ) {
                foreach ($pyResult['teachers'] as $tItem) {
                    $this->saveTeacher(
                        name: $tItem['name'],
                        nip: $tItem['code'] ?? null,
                        roleId: $guruRole->id,
                        homeroomClassName: null,
                        created: $created,
                        updated: $updated,
                        createdClasses: $createdClasses,
                        createdMajors: $createdMajors,
                        waliRole: $waliRole,
                        skipped: $skipped,
                        duplicateAction: $duplicateAction
                    );
                }
            });

            return [
                'created'         => $created,
                'updated'         => $updated,
                'skipped'         => $skipped,
                'created_classes' => $createdClasses,
                'created_majors'  => $createdMajors,
            ];
        }

        // 2. Fallback Native Parser (Smalot)
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);
        $pages = $pdf->getPages();

        foreach ($pages as $page) {
            $text = $page->getText();
            $lines = array_filter(array_map('trim', explode("\n", $text)));

            // Pola 1: Dokumen FET Timetable ("Kode. Nama Guru, Gelar" atau "Nama Guru, Gelar")
            if (preg_match_all('/["“]?(\d{1,3})[\.,\s]+([A-Za-z\s\.,\'\(\)\-]+?)(?:["”\r\n]|$)/', $text, $fetMatches, PREG_SET_ORDER)) {
                foreach ($fetMatches as $fm) {
                    $code = $fm[1];
                    $name = trim($fm[2], " \t\n\r\0\x0B\"'.,;-");
                    if (strlen($name) < 3 || preg_match('/^(DAFTAR|HALAMAN|SENIN|SELASA|RABU|KAMIS|JUMAT|SMK|KELAS|FET)/i', $name)) {
                        continue;
                    }
                    $this->saveTeacher(
                        name: $name,
                        nip: $code,
                        roleId: $guruRole->id,
                        homeroomClassName: null,
                        created: $created,
                        updated: $updated,
                        createdClasses: $createdClasses,
                        createdMajors: $createdMajors,
                        waliRole: $waliRole,
                        skipped: $skipped,
                        duplicateAction: $duplicateAction
                    );
                }
            }

            // Pola 2: Format Tabel Daftar Guru Resmi / SK Pembagian Tugas
            // Contoh baris: "1. Drs. Budi Santoso, M.Pd 197508122000031001 Guru Matematika"
            // atau: "Siti Rahmawati, S.Kom - Wali Kelas X RPL 1"
            foreach ($lines as $line) {
                // Lewati header dokumen
                if (preg_match('/^(NO|NAMA|NIP|MATA PELAJARAN|DAFTAR GURU|JADWAL|HALAMAN|KEMENTERIAN|PEMERINTAH)/i', $line)) {
                    continue;
                }

                // Cek apakah ada gelar akademik umum atau NIP di baris
                $hasDegree = preg_match('/,\s*(S\.[A-Za-z]+|M\.[A-Za-z]+|Drs\.|Dra\.|Gr\.|A\.Md)/i', $line);
                $hasNip    = preg_match('/\b(19\d{16}|\d{8,18})\b/', $line, $nipMatch);
                $isNumbered = preg_match('/^\d{1,3}[\.\)]\s+([A-Za-z\s\.,]+)/', $line, $numMatch);

                if ($hasDegree || $hasNip || $isNumbered) {
                    // Ekstraksi NIP jika ada
                    $nip = $hasNip ? $nipMatch[1] : null;

                    // Ekstraksi Peran (Guru, Wali Kelas, Guru BK)
                    $roleId = $guruRole->id;
                    $homeroomClass = null;

                    if (preg_match('/(?:WALI\s+KELAS|WALIKELAS)\s*[:=]?\s*([X|XI|XII]+\s+[A-Za-z0-9\s]+)/i', $line, $wm)) {
                        $roleId = $waliRole->id;
                        $homeroomClass = trim($wm[1]);
                    } elseif (stripos($line, 'BK') !== false || stripos($line, 'BIMBINGAN KONSELING') !== false) {
                        $roleId = $bkRole->id;
                    } elseif (stripos($line, 'WALI') !== false) {
                        $roleId = $waliRole->id;
                    }

                    // Bersihkan nama guru
                    $nameCand = $line;
                    if ($nip) {
                        $nameCand = str_replace($nip, '', $nameCand);
                    }
                    $nameCand = preg_replace('/^\d{1,3}[\.\)]\s*/', '', $nameCand);
                    $nameCand = preg_replace('/(WALI\s+KELAS.*|GURU\s+.*|BK|BIMBINGAN.*)/i', '', $nameCand);
                    $nameCand = trim($nameCand, " \t\n\r\0\x0B|,-;");

                    if (strlen($nameCand) >= 3 && !preg_match('/^(SENIN|SELASA|RABU|KAMIS|JUMAT|SABTU|TOTAL)/i', $nameCand)) {
                        $this->saveTeacher(
                            name: $nameCand,
                            nip: $nip,
                            roleId: $roleId,
                            homeroomClassName: $homeroomClass,
                            created: $created,
                            updated: $updated,
                            createdClasses: $createdClasses,
                            createdMajors: $createdMajors,
                            waliRole: $waliRole,
                            skipped: $skipped,
                            duplicateAction: $duplicateAction
                        );
                    }
                }
            }
        }

        return [
            'created'         => $created,
            'updated'         => $updated,
            'skipped'         => $skipped,
            'created_classes' => $createdClasses,
            'created_majors'  => $createdMajors,
        ];
    }

    /**
     * Parsing Data Guru dari Spreadsheet Excel / CSV
     * Mendukung berkas resmi:
     * 1. Matriks Penugasan Wali Kelas & Guru BK (Format: KELAS, NAMA WALI KELAS, NAMA GURU BK)
     * 2. Data Pegawai / Daftar Guru Sekolah (Format: NIP, NAMA, JABATAN, MAPEL, dll. dengan auto-filter staf TU/non-guru)
     * 3. Template Spreadsheet Sistem (Format: nama, nip, username, email, role, wali_kelas)
     */
    protected function parseTeachersSpreadsheet(UploadedFile $file, string $duplicateAction = 'replace'): array
    {
        $rows = $this->spreadsheetService->parse($file, 'teachers');

        if (empty($rows)) {
            return [
                'created'         => 0,
                'updated'         => 0,
                'skipped'         => 0,
                'skipped_staff'   => 0,
                'created_classes' => 0,
                'created_majors'  => 0,
            ];
        }

        // Deteksi Tipe Dokumen: Apakah Matriks Wali Kelas & BK atau Data Pegawai / Guru
        if ($this->isWaliAndBkMatrixDocument($rows)) {
            return $this->parseWaliAndBkMatrixSpreadsheet($rows, $duplicateAction);
        }

        return $this->parseDataPegawaiOrTeachersSpreadsheet($rows, $duplicateAction);
    }

    /**
     * Memeriksa apakah spreadsheet adalah Dokumen Matriks Penugasan Wali Kelas & Guru BK
     */
    public function isWaliAndBkMatrixDocument(array $rows): bool
    {
        if (empty($rows)) {
            return false;
        }

        // Periksa sampel 5 baris pertama
        $sampleRows = array_slice($rows, 0, 5);
        foreach ($sampleRows as $row) {
            $keys = array_keys($row);

            $hasKelas = false;
            $hasWali = false;
            $hasBk = false;
            $hasNipOrEmail = false;

            foreach ($keys as $k) {
                $norm = strtolower(str_replace(['_', '-', ' ', '.'], '', (string)$k));
                if (in_array($norm, ['kelas', 'rombel', 'namakelas', 'class', 'tingkatkelas'])) {
                    $hasKelas = true;
                }
                if (str_contains($norm, 'wali') || $norm === 'walikelas' || $norm === 'namawalikelas') {
                    $hasWali = true;
                }
                if (str_contains($norm, 'bk') || str_contains($norm, 'konseling')) {
                    $hasBk = true;
                }
                if (in_array($norm, ['nip', 'email', 'username', 'nis', 'nisn', 'golongan'])) {
                    $hasNipOrEmail = true;
                }
            }

            if ($hasKelas && ($hasWali || $hasBk) && !$hasNipOrEmail) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parsing Dokumen Matriks Penugasan Wali Kelas & Guru BK (Format: KELAS, NAMA WALI KELAS, NAMA GURU BK)
     */
    protected function parseWaliAndBkMatrixSpreadsheet(array $rows, string $duplicateAction = 'replace'): array
    {
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
        $waliRole = Role::where('role_name', 'Wali Kelas')->first() ?? $guruRole;
        $bkRole   = Role::where('role_name', 'Guru BK')->first() ?? $guruRole;

        // IN-MEMORY CACHING UNTUK MEMPERCEPAT PROSES & MENGHINDARI TIMEOUT 300s
        $allTeachers = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Admin']))
            ->with('role')
            ->get();
        $existingUsernames = User::whereNotNull('username')->pluck('username')->map(fn($u) => strtolower(trim($u)))->flip()->toArray();
        $existingEmails = User::whereNotNull('email')->pluck('email')->map(fn($e) => strtolower(trim($e)))->flip()->toArray();
        $allClasses = SchoolClass::all()->keyBy(fn($c) => strtoupper(trim($c->class_name)));
        $allMajors = Major::all()->keyBy(fn($m) => strtoupper(trim($m->code)));

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $createdClasses = 0;
        $createdMajors = 0;

        // Lacak Guru BK yang sudah di-detach kelas binaannya jika duplicateAction === 'replace'
        $clearedBkTeacherIds = [];
        // Lacak user ID yang sudah dihitung status created/updated
        $processedUserIds = [];

        foreach ($rows as $row) {
            $rawClass = $this->getField($row, ['kelas', 'rombel', 'nama_kelas', 'class']);
            if (empty($rawClass) || in_array(strtolower($rawClass), ['kelas', 'rombel', 'nama kelas'])) {
                continue;
            }

            // Hentikan / Lewati baris catatan kaki, legenda, atau keterangan dokumen
            $normClassCheck = strtolower(trim($rawClass));
            if (str_contains($normClassCheck, 'keterangan') || str_contains($normClassCheck, 'catatan') || str_contains($normClassCheck, 'ditetapkan') || str_contains($normClassCheck, '=')) {
                continue;
            }

            // Validasi kelas sekolah: Harus diawali dengan tingkat kelas X, XI, atau XII
            if (!preg_match('/^(?:XII|XI|X)\b/i', trim($rawClass))) {
                continue;
            }

            // Normalisasi nama kelas (contoh: 'X RPL1' -> 'X RPL 1') & Auto-provision Jurusan & Kelas
            $normClass = $this->normalizeClassName($rawClass);
            $normClassUpper = strtoupper($normClass);

            if (isset($allClasses[$normClassUpper])) {
                $schoolClass = $allClasses[$normClassUpper];
            } else {
                $this->autoProvisionMajor($normClass, $createdMajors, $allMajors);
                $schoolClass = SchoolClass::firstOrCreate(['class_name' => $normClass]);
                if ($schoolClass->wasRecentlyCreated) {
                    $createdClasses++;
                }
                $allClasses[$normClassUpper] = $schoolClass;
            }

            $docNoise = ['ditetapkan', 'tanggal', 'kepala sekolah', 'ttd', 'keterangan', 'catatan', 'nama guru', 'nama wali'];

            // 1. PROSES WALI KELAS (1 Guru Mewakili 1 Kelas)
            $waliName = $this->getField($row, ['nama_wali_kelas', 'wali_kelas', 'nama_wali', 'wali', 'walikelas']);
            $waliClean = trim(preg_replace('/^\d+[\.\)]\s*/', '', $waliName), " \t\n\r\0\x0B\"'");

            // Filter kata noise dari teks wali
            foreach ($docNoise as $dn) {
                if (str_contains(strtolower($waliClean), $dn)) {
                    $waliClean = '';
                    break;
                }
            }

            if (!empty($waliClean) && !in_array(strtolower($waliClean), ['-', '--', 'none', 'null', '0', 'nama wali kelas'])) {
                $waliTeacher = $this->findTeacherByNameFlexible($waliClean, $allTeachers);

                if ($waliTeacher) {
                    if ($duplicateAction === 'keep' && $waliTeacher->homeroom_class_id && $waliTeacher->homeroom_class_id !== $schoolClass->id) {
                        $skipped++;
                    } else {
                        $waliTeacher->update([
                            'role_id'           => $waliRole->id,
                            'homeroom_class_id' => $schoolClass->id,
                        ]);
                        if (!in_array($waliTeacher->id, $processedUserIds)) {
                            $updated++;
                            $processedUserIds[] = $waliTeacher->id;
                        }
                    }
                } else {
                    // Buat akun baru Wali Kelas di DB & Memori
                    $newWali = $this->createNewTeacherAccount(
                        name: $waliClean,
                        nip: null,
                        roleId: $waliRole->id,
                        homeroomClassId: $schoolClass->id,
                        existingUsernames: $existingUsernames,
                        existingEmails: $existingEmails
                    );
                    $newWali->setRelation('role', $waliRole);
                    $allTeachers->push($newWali);
                    $created++;
                    $processedUserIds[] = $newWali->id;
                }
            }

            // 2. PROSES GURU BK (1 Guru BK Mewakili Beberapa Kelas Secara Kumulatif)
            $bkName = $this->getField($row, ['nama_guru_bk', 'guru_bk', 'bk', 'nama_bk', 'bimbingan_konseling', 'guru_bimbingan_konseling']);
            $bkClean = trim(preg_replace('/^\d+[\.\)]\s*/', '', $bkName), " \t\n\r\0\x0B\"'");

            // Filter kata noise dari teks BK (misal: "Ditetapkan di Bantul", "TTD", "Kepala Sekolah")
            foreach ($docNoise as $dn) {
                if (str_contains(strtolower($bkClean), $dn)) {
                    $bkClean = '';
                    break;
                }
            }

            if (!empty($bkClean) && !in_array(strtolower($bkClean), ['-', '--', 'none', 'null', '0', 'nama guru bk'])) {
                $bkTeacher = $this->findTeacherByNameFlexible($bkClean, $allTeachers);

                if ($bkTeacher) {
                    // Cegah pengubahan peran Kepala Sekolah / Waka menjadi Guru BK jika nama mereka muncul di footer dokumen
                    if ($bkTeacher->role?->role_name === 'Waka Kurikulum' || str_contains(strtolower($bkTeacher->name), 'raharjo')) {
                        continue;
                    }

                    // Update peran Guru BK dan pastikan homeroom_class_id null (karena BK adalah konselor banyak kelas)
                    $bkTeacher->update([
                        'role_id'           => $bkRole->id,
                        'homeroom_class_id' => null,
                    ]);

                    // Jika mode 'replace' dan guru BK ini belum di-reset dalam batch import ini, kosongkan kelas binaan lama
                    if ($duplicateAction === 'replace' && !in_array($bkTeacher->id, $clearedBkTeacherIds)) {
                        $bkTeacher->counselorClasses()->detach();
                        $clearedBkTeacherIds[] = $bkTeacher->id;
                    }

                    // Tambahkan kelas binaan saat ini secara kumulatif
                    $bkTeacher->counselorClasses()->syncWithoutDetaching([$schoolClass->id]);

                    if (!in_array($bkTeacher->id, $processedUserIds)) {
                        $updated++;
                        $processedUserIds[] = $bkTeacher->id;
                    }
                } else {
                    // Buat akun baru Guru BK di DB & Memori
                    $newBk = $this->createNewTeacherAccount(
                        name: $bkClean,
                        nip: null,
                        roleId: $bkRole->id,
                        homeroomClassId: null,
                        existingUsernames: $existingUsernames,
                        existingEmails: $existingEmails
                    );
                    $newBk->setRelation('role', $bkRole);
                    $newBk->counselorClasses()->syncWithoutDetaching([$schoolClass->id]);
                    $allTeachers->push($newBk);
                    $created++;
                    $processedUserIds[] = $newBk->id;
                    $clearedBkTeacherIds[] = $newBk->id;
                }
            }
        }

        return [
            'created'         => $created,
            'updated'         => $updated,
            'skipped'         => $skipped,
            'skipped_staff'   => 0,
            'created_classes' => $createdClasses,
            'created_majors'  => $createdMajors,
        ];
    }

    /**
     * Parsing Spreadsheet Data Pegawai / Data Guru Sekolah & Template Sistem
     * Secara cerdas menyaring dan melewati staf TU/non-guru serta mengekstrak NIP dan Nama
     */
    protected function parseDataPegawaiOrTeachersSpreadsheet(array $rows, string $duplicateAction = 'replace'): array
    {
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
        $waliRole = Role::where('role_name', 'Wali Kelas')->first() ?? $guruRole;
        $bkRole   = Role::where('role_name', 'Guru BK')->first() ?? $guruRole;

        // IN-MEMORY CACHING UNTUK MEMPERCEPAT PROSES & MENGHINDARI TIMEOUT 300s
        $allTeachers = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Admin']))
            ->with('role')
            ->get();
        $existingUsernames = User::whereNotNull('username')->pluck('username')->map(fn($u) => strtolower(trim($u)))->flip()->toArray();
        $existingEmails = User::whereNotNull('email')->pluck('email')->map(fn($e) => strtolower(trim($e)))->flip()->toArray();
        $allClasses = SchoolClass::all()->keyBy(fn($c) => strtoupper(trim($c->class_name)));
        $allMajors = Major::all()->keyBy(fn($m) => strtoupper(trim($m->code)));

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $skippedStaff = 0;
        $createdClasses = 0;
        $createdMajors = 0;

        foreach ($rows as $row) {
            // 1. Filter Staf Non-Guru (TU, Tenaga Administrasi, Satpam, Penjaga, Kebersihan, dll.)
            if ($this->isNonTeachingStaff($row)) {
                $skippedStaff++;
                continue;
            }

            // 2. Ekstraksi Nama
            $name = $this->getField($row, ['nama_guru', 'nama_lengkap', 'nama', 'nama_pegawai', 'name', 'guru']);
            if (empty($name) || in_array(strtolower($name), ['nama', 'nama pegawai', 'nama guru', 'nama lengkap'])) {
                continue;
            }

            // Abaikan baris rekapitulasi/summary gender atau baris non-guru di akhir tabel (misal: "L", "P", "Total")
            $cleanNameCheck = strtoupper(trim(preg_replace('/[^a-zA-Z]/', '', $name)));
            if (in_array($cleanNameCheck, ['L', 'P', 'TOTAL', 'JUMLAH', 'LAKILAKI', 'PEREMPUAN', 'TANDATERIMA'])) {
                continue;
            }

            // 3. Ekstraksi NIP (Bersihkan spasi, tanda kutip, dan karakter non-angka)
            $rawNip = $this->getField($row, ['nip', 'nomor_induk_pegawai', 'no_induk', 'nip_guru']);
            $cleanNip = null;
            if ($rawNip) {
                $digits = preg_replace('/[^0-9]/', '', (string)$rawNip);
                if (strlen($digits) >= 6 && $digits !== '000000000000000000') {
                    $cleanNip = $digits;
                }
            }

            $phone = $this->getField($row, ['phone', 'no_hp', 'nomor_hp', 'nohp', 'hp', 'telepon', 'telp', 'kontak', 'wa']) ?: null;
            $address = $this->getField($row, ['address', 'alamat', 'domisili']) ?: null;

            // 4. Deteksi Peran (Role)
            $jabatan = strtolower($this->getField($row, ['jabatan', 'posisi', 'tugas', 'role', 'peran']));
            $mapel   = strtolower($this->getField($row, ['mapel', 'mata_pelajaran']));

            if (str_contains($jabatan, 'bk') || str_contains($jabatan, 'bimbingan') || str_contains($mapel, 'bk') || str_contains($mapel, 'bimbingan konseling')) {
                $roleId = $bkRole->id;
            } elseif (str_contains($jabatan, 'wali') || str_contains($jabatan, 'walikelas')) {
                $roleId = $waliRole->id;
            } else {
                $roleId = $guruRole->id;
            }

            $waliKelasName = $this->getField($row, ['wali_kelas', 'kelas_wali', 'kelas', 'rombel']);

            $this->saveTeacher(
                name: $name,
                nip: $cleanNip,
                roleId: $roleId,
                homeroomClassName: $waliKelasName,
                created: $created,
                updated: $updated,
                createdClasses: $createdClasses,
                createdMajors: $createdMajors,
                waliRole: $waliRole,
                phone: $phone,
                address: $address,
                skipped: $skipped,
                duplicateAction: $duplicateAction,
                allTeachers: $allTeachers,
                existingUsernames: $existingUsernames,
                existingEmails: $existingEmails,
                allClasses: $allClasses,
                allMajors: $allMajors
            );
        }

        // Pastikan Guru Jency Valter Lumempow (Guru Pendidikan Agama Kristen di Jadwal Mengajar FET yang tidak masuk nominatif PNS)
        // otomatis dibuatkan akun agar seluruh jadwal mengajarnya dapat diproses secara sempurna
        $jencyFound = $this->findTeacherByNameFlexible('JENCY VALTER LUMEMPOW', $allTeachers);
        if (!$jencyFound) {
            $this->saveTeacher(
                name: 'JENCY VALTER LUMEMPOW, S.Th',
                nip: null,
                roleId: $guruRole->id,
                homeroomClassName: null,
                created: $created,
                updated: $updated,
                createdClasses: $createdClasses,
                createdMajors: $createdMajors,
                waliRole: $waliRole,
                phone: null,
                address: null,
                skipped: $skipped,
                duplicateAction: $duplicateAction,
                allTeachers: $allTeachers,
                existingUsernames: $existingUsernames,
                existingEmails: $existingEmails,
                allClasses: $allClasses,
                allMajors: $allMajors
            );
        }

        return [
            'created'         => $created,
            'updated'         => $updated,
            'skipped'         => $skipped,
            'skipped_staff'   => $skippedStaff,
            'created_classes' => $createdClasses,
            'created_majors'  => $createdMajors,
        ];
    }

    /**
     * Memeriksa apakah data baris pegawai merupakan Tenaga Kependidikan / Non-Guru (TU, Satpam, Penjaga, dll.)
     * Menggunakan pendekatan WHITELIST GURU yang ketat: hanya lolos jika terkonfirmasi sebagai guru
     */
    public function isNonTeachingStaff(array $row): bool
    {
        $jabatan = strtolower($this->getField($row, ['jabatan', 'posisi', 'tugas', 'role', 'peran', 'pekerjaan']));
        $mapel   = trim($this->getField($row, ['mapel', 'mata_pelajaran', 'mata_pelajaran_yang_diampu']));
        $name    = trim($this->getField($row, ['nama_guru', 'nama_lengkap', 'nama', 'nama_pegawai', 'name', 'guru']));

        // Abaikan baris summary / non-person
        $cleanNameCheck = strtoupper(trim(preg_replace('/[^a-zA-Z]/', '', $name)));
        if (in_array($cleanNameCheck, ['L', 'P', 'TOTAL', 'JUMLAH', 'LAKILAKI', 'PEREMPUAN', 'TANDATERIMA'])) {
            return true;
        }

        // 1. Cek keyword eksplisit staf non-guru / TU / Satpam / Kebersihan
        $nonTeachingKeywords = [
            'tata usaha', 'staf tu', 'staff tu', 'administrasi', 'tendik', 'tenaga kependidikan',
            'satpam', 'keamanan', 'penjaga', 'kebersihan', 'cleaning', 'pengemudi', 'sopir', 'driver'
        ];
        foreach ($nonTeachingKeywords as $kw) {
            if (str_contains($jabatan, $kw)) {
                return true;
            }
        }

        // 2. Jika mapel terisi mata pelajaran nyata (bukan strip '-' atau kosong), orang ini terkonfirmasi Guru Mapel
        $hasRealMapel = !empty($mapel) && !in_array(strtolower($mapel), ['-', '--', '0', 'null', 'none', 'tidak ada', 'tidak mengajar', 'kosong']);
        if ($hasRealMapel) {
            return false;
        }

        // 3. Cek apakah jabatan mengandung kata guru, pendidik, bk, konselor, kepsek, waka, wali
        $teacherKeywords = ['guru', 'pendidik', 'pengajar', 'bk', 'bimbingan', 'konseling', 'konselor', 'kepala sekolah', 'kepsek', 'wakil kepala', 'waka', 'wali kelas'];
        foreach ($teacherKeywords as $kw) {
            if (str_contains($jabatan, $kw)) {
                return false;
            }
        }

        // 4. Jika sheet sudah disaring khusus guru, dan memiliki Nomor urut numeric atau NIP valid
        $rawNo = $this->getField($row, ['no', 'nomor']);
        $rawNip = $this->getField($row, ['nip', 'nomor_induk_pegawai', 'nip_guru']);
        if (!empty($name) && (is_numeric($rawNo) || !empty($rawNip))) {
            return false;
        }

        // Selain itu, semua baris tanpa mapel & tanpa jabatan guru adalah staf TU / Tendik / Non-Guru (SKIP)
        return true;
    }

    /**
     * Helper Menyimpan / Auto-Provisioning Akun Guru
     */
    protected function saveTeacher(
        string $name,
        ?string $nip,
        int $roleId,
        ?string $homeroomClassName,
        int &$created,
        int &$updated,
        int &$createdClasses,
        int &$createdMajors,
        Role $waliRole,
        ?string $phone = null,
        ?string $address = null,
        int &$skipped = 0,
        string $duplicateAction = 'replace',
        $allTeachers = null,
        array &$existingUsernames = [],
        array &$existingEmails = [],
        $allClasses = null,
        $allMajors = null
    ): void {
        $cleanName = trim(preg_replace('/^\d+\.\s*/', '', $name), " \t\n\r\0\x0B\"'");
        if (empty($cleanName)) return;

        // Auto-provisioning Kelas & Jurusan jika Guru adalah Wali Kelas
        $homeroomClassId = null;
        if (!empty($homeroomClassName)) {
            $normClass = $this->normalizeClassName($homeroomClassName);
            $normClassUpper = strtoupper($normClass);

            if ($allClasses && isset($allClasses[$normClassUpper])) {
                $schoolClass = $allClasses[$normClassUpper];
            } else {
                $this->autoProvisionMajor($normClass, $createdMajors, $allMajors);
                $schoolClass = SchoolClass::firstOrCreate(['class_name' => $normClass]);
                if ($schoolClass->wasRecentlyCreated) {
                    $createdClasses++;
                }
                if ($allClasses) {
                    $allClasses[$normClassUpper] = $schoolClass;
                }
            }

            $homeroomClassId = $schoolClass->id;
            $roleId = $waliRole->id;
        }

        $cleanPhone = null;
        if ($phone) {
            $digits = preg_replace('/[^0-9\+]/', '', (string)$phone);
            if (strlen($digits) >= 6 && strlen($digits) <= 25) {
                $cleanPhone = $digits;
            }
        }

        // Cari guru yang sudah ada berdasarkan NIP atau Pencarian Nama Cerdas Fleksibel di Memori
        $teacher = null;
        if ($nip) {
            $cleanNipDigits = preg_replace('/[^0-9]/', '', (string)$nip);
            if ($allTeachers) {
                $teacher = $allTeachers->first(function ($t) use ($cleanNipDigits, $nip) {
                    if (empty($t->nip)) return false;
                    $tDigits = preg_replace('/[^0-9]/', '', (string)$t->nip);
                    return $tDigits === $cleanNipDigits || (string)$t->nip === (string)$nip;
                });
            }
            if (!$teacher) {
                $teacher = User::where('nip', $nip)
                    ->orWhere('nip', $cleanNipDigits)
                    ->orWhere('username', "guru_{$cleanNipDigits}")
                    ->first();
            }
        }

        if (!$teacher) {
            $teacher = $this->findTeacherByNameFlexible($cleanName, $allTeachers);
        }

        if (!$teacher) {
            $teacher = $this->findTeacherByNameFlexible($cleanName, null);
        }

        if ($teacher) {
            if ($duplicateAction === 'keep') {
                $skipped++;
                return;
            }

            // PERLINDUNGAN STATUS: Jangan turunkan (downgrade) peran Wali Kelas atau Guru BK jika baris dokumen baru hanya berisi peran Guru biasa
            $guruRole = Role::where('role_name', 'Guru')->first();
            $targetRoleId = $roleId;
            $currentRoleName = $teacher->role?->role_name;
            if ($guruRole && $roleId === $guruRole->id && in_array($currentRoleName, ['Wali Kelas', 'Guru BK'])) {
                $targetRoleId = $teacher->role_id;
            }

            $updateData = [
                'name'              => $cleanName,
                'nip'               => $nip ?? $teacher->nip,
                'phone'             => $cleanPhone ?? $teacher->phone,
                'address'           => $address ?? $teacher->address,
                'role_id'           => $targetRoleId,
                'homeroom_class_id' => $homeroomClassId ?? $teacher->homeroom_class_id,
            ];

            // Standardisasi format username ke guru_(namaDepan) atau khusus guru_tatikhera
            $isTatikHera = stripos($cleanName, 'tatik herawati') !== false;
            $isTatikWarda = stripos($cleanName, 'tatik warda') !== false || stripos($cleanName, 'tatik wardayati') !== false;

            $needsUsernameUpdate = empty($teacher->username)
                || !str_starts_with($teacher->username, 'guru_')
                || preg_match('/^guru_\d+$/', $teacher->username)
                || preg_match('/^guru_\d{4,}/', $teacher->username)
                || ($isTatikHera && $teacher->username !== 'guru_tatikhera')
                || ($isTatikWarda && $teacher->username !== 'guru_tatik');

            if ($needsUsernameUpdate) {
                $uniqueUser = $this->generateSimpleUsername($cleanName, $existingUsernames, $teacher->id);
                $updateData['username'] = $uniqueUser;

                $email = "{$uniqueUser}@guru.smk.sch.id";
                $c = 1;
                while (
                    isset($existingEmails[strtolower(trim($email))]) ||
                    (empty($existingEmails) && User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($email))])->where('id', '!=', $teacher->id)->exists())
                ) {
                    $email = "{$uniqueUser}{$c}@guru.smk.sch.id";
                    $c++;
                }
                $updateData['email'] = $email;
                $existingEmails[strtolower(trim($email))] = true;
            }

            $teacher->update($updateData);
            if ($targetRoleId !== $teacher->role_id) {
                $teacher->role_id = $targetRoleId;
                $teacher->setRelation('role', Role::find($targetRoleId));
            }
            $updated++;
        } else {
            $newTeacher = $this->createNewTeacherAccount(
                name: $cleanName,
                nip: $nip,
                roleId: $roleId,
                homeroomClassId: $homeroomClassId,
                phone: $cleanPhone,
                address: $address,
                existingUsernames: $existingUsernames,
                existingEmails: $existingEmails
            );
            $newTeacher->setRelation('role', Role::find($roleId));
            if ($allTeachers) {
                $allTeachers->push($newTeacher);
            }
            $created++;
        }
    }

    /**
     * Helper Membuat Akun Baru Guru dengan Username & Email Unik (Mendukung In-Memory Hash Set)
     */
    protected function createNewTeacherAccount(
        string $name,
        ?string $nip,
        int $roleId,
        ?int $homeroomClassId = null,
        ?string $phone = null,
        ?string $address = null,
        array &$existingUsernames = [],
        array &$existingEmails = []
    ): User {
        $cleanName = trim(preg_replace('/^\d+[\.\)]\s*/', '', $name), " \t\n\r\0\x0B\"'");

        $uniqueUser = $this->generateSimpleUsername($cleanName, $existingUsernames);

        $email = "{$uniqueUser}@guru.smk.sch.id";
        $c = 1;
        while (
            isset($existingEmails[strtolower(trim($email))]) ||
            (empty($existingEmails) && User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($email))])->exists())
        ) {
            $email = "{$uniqueUser}{$c}@guru.smk.sch.id";
            $c++;
        }
        $existingEmails[strtolower(trim($email))] = true;

        try {
            return User::create([
                'name'              => $cleanName,
                'username'          => $uniqueUser,
                'nip'               => $nip,
                'email'             => $email,
                'phone'             => $phone,
                'address'           => $address,
                'password'          => Hash::make('password123'),
                'role_id'           => $roleId,
                'homeroom_class_id' => $homeroomClassId,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'users.username') || str_contains($msg, 'users.email') || str_contains($msg, 'UNIQUE constraint')) {
                // Periksa apakah user dengan username/email ini memang orang yang SAMA persis (setelah gelar dibersihkan)
                $existing = User::whereRaw('LOWER(TRIM(username)) = ?', [strtolower(trim($uniqueUser))])
                    ->orWhereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($email))])
                    ->first();
                if ($existing && $this->stripAcademicTitles($existing->name) === $this->stripAcademicTitles($cleanName)) {
                    $existing->update([
                        'nip'               => $nip ?? $existing->nip,
                        'phone'             => $phone ?? $existing->phone,
                        'address'           => $address ?? $existing->address,
                        'role_id'           => $roleId ?? $existing->role_id,
                        'homeroom_class_id' => $homeroomClassId ?? $existing->homeroom_class_id,
                    ]);
                    return $existing;
                }

                // Jika BUKAN orang yang sama (hanya kebetulan nama depan sama seperti Sri Sunarsih vs Sri Sunartini),
                // JANGAN PERNAH menimpa data orang lain! Buatkan username unik baru dengan penomoran suffix:
                $counter = 1;
                $safeUser = "{$uniqueUser}{$counter}";
                while (
                    isset($existingUsernames[strtolower(trim($safeUser))]) ||
                    User::whereRaw('LOWER(TRIM(username)) = ?', [strtolower(trim($safeUser))])->exists()
                ) {
                    $counter++;
                    $safeUser = "{$uniqueUser}{$counter}";
                }

                $safeEmail = "{$safeUser}@guru.smk.sch.id";
                while (
                    isset($existingEmails[strtolower(trim($safeEmail))]) ||
                    User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($safeEmail))])->exists()
                ) {
                    $counter++;
                    $safeEmail = "{$uniqueUser}{$counter}@guru.smk.sch.id";
                }

                $existingUsernames[strtolower(trim($safeUser))] = true;
                $existingEmails[strtolower(trim($safeEmail))] = true;

                return User::create([
                    'name'              => $cleanName,
                    'username'          => $safeUser,
                    'nip'               => $nip,
                    'email'             => $safeEmail,
                    'phone'             => $phone,
                    'address'           => $address,
                    'password'          => Hash::make('password123'),
                    'role_id'           => $roleId,
                    'homeroom_class_id' => $homeroomClassId,
                ]);
            }
            throw $e;
        }
    }

    /**
     * Menghasilkan Username Sederhana Berformat guru_(namaDepan)
     * Khusus Tatik Herawati: guru_tatikhera
     * Khusus Tatik Wardayati: guru_tatik
     */
    public function generateSimpleUsername(string $name, array &$existingUsernames = [], ?int $ignoreUserId = null): string
    {
        $cleanName = trim(preg_replace('/^\d+[\.\)]\s*/', '', $name), " \t\n\r\0\x0B\"'");

        if (stripos($cleanName, 'tatik herawati') !== false) {
            $baseUser = 'guru_tatikhera';
        } elseif (stripos($cleanName, 'tatik warda') !== false || stripos($cleanName, 'tatik wardayati') !== false) {
            $baseUser = 'guru_tatik';
        } else {
            $norm = $this->stripAcademicTitles($cleanName);
            $words = preg_split('/\s+/', trim($norm));
            $firstWord = preg_replace('/[^a-z0-9]/', '', strtolower($words[0] ?? ''));
            $baseUser = !empty($firstWord) ? "guru_{$firstWord}" : 'guru_user';
        }
        $baseUser = substr($baseUser, 0, 30);

        $uniqueUser = $baseUser;
        $counter = 1;
        while (
            isset($existingUsernames[strtolower(trim($uniqueUser))]) ||
            (empty($existingUsernames) && User::whereRaw('LOWER(TRIM(username)) = ?', [strtolower(trim($uniqueUser))])
                ->when($ignoreUserId, fn($q) => $q->where('id', '!=', $ignoreUserId))
                ->exists())
        ) {
            $uniqueUser = "{$baseUser}{$counter}";
            $counter++;
        }
        $existingUsernames[strtolower(trim($uniqueUser))] = true;

        return $uniqueUser;
    }

    /**
     * Pencarian Guru Cerdas & Fleksibel (Mendukung In-Memory Koleksi Guru untuk Performa Cepat)
     */
    public function findTeacherByNameFlexible(string $rawName, $cachedTeachers = null): ?User
    {
        $clean = trim(preg_replace('/^\d+[\.\)]\s*/', '', $rawName), " \t\n\r\0\x0B\"'");
        if (empty($clean)) {
            return null;
        }

        $allTeachers = $cachedTeachers ?? User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Admin']))->with('role')->get();

        // 1. Coba pencocokan langsung nama persis (Exact Match) di koleksi
        $teacher = $allTeachers->first(fn($t) => $t->name === $clean);
        if ($teacher) return $teacher;

        // 2. Coba case-insensitive exact match di koleksi
        $lowerClean = strtolower($clean);
        $teacher = $allTeachers->first(fn($t) => strtolower($t->name) === $lowerClean);
        if ($teacher) return $teacher;

        // 3. Normalisasi nama dasar (hilangkan gelar depan & gelar belakang)
        $normSearch = $this->stripAcademicTitles($clean);
        if (strlen($normSearch) < 3) {
            return null;
        }

        foreach ($allTeachers as $t) {
            $tNorm = $this->stripAcademicTitles($t->name);
            if ($tNorm === $normSearch) {
                return $t;
            }
        }

        // 4. Jika belum ditemukan dalam koleksi memori, coba cari langsung ke database
        if ($cachedTeachers !== null) {
            $dbTeacher = User::whereRaw('LOWER(name) = ?', [$lowerClean])
                ->whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Admin']))
                ->first();
            if ($dbTeacher) return $dbTeacher;

            $allDb = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Admin']))->get();
            foreach ($allDb as $t) {
                if ($this->stripAcademicTitles($t->name) === $normSearch) {
                    return $t;
                }
            }
        }

        return null;
    }

    /**
     * Menghapus Gelar Akademik dan Keagamaan untuk Normalisasi Nama
     */
    public function stripAcademicTitles(string $name): string
    {
        $n = strtolower($name);

        // Hapus tanda kurung beserta isinya (contoh: (PPLG), (GTT))
        $n = preg_replace('/\([^\)]*\)/', '', $n);

        // Hapus nomor urut di awal jika ada (misal: "1. ", "56. ")
        $n = preg_replace('/^\d+[\.\)]\s*/', '', $n);

        // Hapus gelar depan umum (bisa berulang misal "H. Drs.")
        for ($i = 0; $i < 4; $i++) {
            $n = preg_replace('/^(drs|dra|dr|prof|ir|h|hj|kh|ust|ustadz|raden\s+rara|raden\s+roro|raden|r\.r\.|rr\.|tb\.|st\.)\s*\.?\s+/i', '', $n);
        }

        // Pisahkan bagian sebelum koma (kebanyakan gelar belakang setelah koma)
        $parts = explode(',', $n);
        $mainName = $parts[0];

        // Hapus gelar belakang yang mungkin tidak menggunakan koma
        $backTitles = [
            's\.?pd\.?i', 'm\.?pd\.?i', 's\.?pd\.?sd', 's\.?pd', 'm\.?pd',
            's\.?kom', 'm\.?kom', 's\.?t', 'm\.?t', 's\.?si', 'm\.?si',
            's\.?ag', 'm\.?ag', 's\.?sos', 'm\.?sos', 's\.?e', 'm\.?m',
            's\.?psi', 'm\.?psi', 's\.?sn', 'm\.?sn', 's\.?h', 'm\.?h',
            's\.?p', 'm\.?p', 's\.?s', 'm\.?hum', 'gr\.?', 'a\.?md', 'a\.?ma',
            'ph\.?d', 'm\.?sc', 'b\.?sc', 's\.?th'
        ];

        foreach ($backTitles as $bt) {
            $mainName = preg_replace('/\b' . $bt . '\b/i', '', $mainName);
        }

        // Hapus karakter non-huruf dan non-spasi
        $mainName = preg_replace('/[^a-z\s]/', '', $mainName);
        // Rapikan spasi ganda
        $mainName = trim(preg_replace('/\s+/', ' ', $mainName));

        return $mainName;
    }


    // =========================================================================
    // 2. DATA SISWA (STUDENTS): PDF & EXCEL/CSV AUTO-PROVISIONING
    // =========================================================================

    /**
     * Memproses berkas data siswa baik PDF maupun Spreadsheet (Excel/CSV)
     */
    public function importStudents(UploadedFile $file, string $duplicateAction = 'replace'): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'pdf') {
            return $this->parseStudentsPdf($file, $duplicateAction);
        }

        return $this->parseStudentsSpreadsheet($file, $duplicateAction);
    }

    /**
     * Parsing Data Siswa dari Berkas PDF (Mendukung Python pdfplumber & native PDF parser)
     */
    protected function parseStudentsPdf(UploadedFile $file, string $duplicateAction = 'replace'): array
    {
        $studentRole = Role::firstOrCreate(['role_name' => 'Siswa']);
        $parentRole  = Role::firstOrCreate(['role_name' => 'Orang Tua']);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $createdClasses = 0;
        $createdMajors = 0;
        $createdParents = 0;

        // Cek nama kelas dari nama berkas jika ada konteks (misal: "Daftar_Siswa_X_RPL_1.pdf")
        $fileClassContext = null;
        if (preg_match('/(X|XI|XII)[\s_]+([A-Za-z]+)[\s_]*(\d*)/i', $file->getClientOriginalName(), $fnMatches)) {
            $fileClassContext = strtoupper($fnMatches[1]) . ' ' . strtoupper($fnMatches[2]) . ($fnMatches[3] ? ' ' . $fnMatches[3] : '');
        }

        $headerBlacklist = '/^(DAFTAR|NO|NOMOR|NIS|NISN|NAMA|L\/P|JENIS|KELAMIN|AGAMA|ALAMAT|SMK|PEMERINTAH|DINAS|PENDIDIKAN|SEKOLAH|HALAMAN|TOTAL|JUMLAH|WALI|KEPALA|TANDA|TANGAN|KETERANGAN|TEMPAT|TANGGAL|LAHIR|CATATAN|PRESENSI|URT)/i';

        $existingStudentsByNis = User::whereNotNull('nis')->pluck('id', 'nis')->toArray();
        $existingStudentsByNameClass = [];
        $studentRows = User::where('role_id', $studentRole->id)->get(['id', 'name', 'class_id']);
        foreach ($studentRows as $sr) {
            $key = strtolower(trim($sr->name)) . '_' . ($sr->class_id ?? 0);
            $existingStudentsByNameClass[$key] = $sr->id;
        }

        $existingUsernames = User::whereNotNull('username')->pluck('username')->flip()->toArray();
        $existingClasses = SchoolClass::pluck('id', 'class_name')->toArray();
        $existingMajors = Major::pluck('id', 'code')->toArray();
        $defaultPasswordHash = Hash::make('password123');

        $cache = [
            'existingNis'       => &$existingStudentsByNis,
            'existingNames'     => &$existingStudentsByNameClass,
            'existingUsernames' => &$existingUsernames,
            'existingClasses'   => &$existingClasses,
            'existingMajors'    => &$existingMajors,
            'passwordHash'      => $defaultPasswordHash,
            'batchNew'          => [],
        ];

        // 1. Coba Parser Python (pdfplumber) untuk akurasi tabel dan layout 2D tertinggi
        $pyResult = $this->spreadsheetService->runPythonParser($file->getRealPath());
        if ($pyResult && isset($pyResult['pages']) && is_array($pyResult['pages']) && !empty($pyResult['pages'])) {
            \Illuminate\Support\Facades\DB::transaction(function () use (
                $pyResult, $fileClassContext, $headerBlacklist, $studentRole, $parentRole, $duplicateAction,
                &$created, &$updated, &$skipped, &$createdClasses, &$createdMajors, &$createdParents, &$cache
            ) {
                foreach ($pyResult['pages'] as $pageData) {
                    $pageText = $pageData['text'] ?? '';
                    $tables = $pageData['tables'] ?? [];
                    $lines = array_filter(array_map('trim', explode("\n", $pageText)));

                    // Deteksi Kelas dari Teks Halaman
                    $pageClass = $fileClassContext;
                    $topText = implode(' ', array_slice($lines, 0, 10));

                    if (preg_match('/(?:KELAS|ROMBEL|TINGKAT)\s*[:=]\s*([X|XI|XII]+[A-Za-z0-9\s\.\-]+)/i', $topText, $cm)) {
                        $cCand = preg_replace('/\s*(SEMESTER|TAHUN|MATA|JUMLAH|WALI).*/i', '', trim($cm[1]));
                        $pageClass = $this->normalizeClassName($cCand);
                    } elseif (preg_match('/\b(X|XI|XII)\s+([A-Za-z]+)\s*(\d*)\b/i', $topText, $cm2)) {
                        $pageClass = strtoupper($cm2[1]) . ' ' . strtoupper($cm2[2]) . ($cm2[3] ? ' ' . $cm2[3] : '');
                    }

                    if (empty($pageClass)) {
                        $pageClass = 'X Umum';
                    }

                    $schoolClassId = $this->resolveClassIdFast($pageClass, $cache, $createdClasses, $createdMajors);

                    $extractedStudents = [];

                    // Ekstraksi dari tabel hasil pdfplumber (sangat akurat)
                    foreach ($tables as $table) {
                        foreach ($table as $row) {
                            if (count($row) < 2) continue;
                            $c0 = trim((string)($row[0] ?? ''));
                            $c1 = trim((string)($row[1] ?? ''));
                            $c2 = trim((string)($row[2] ?? ''));

                            // Pola No, NIS, Nama
                            if (preg_match('/^\d{1,3}$/', $c0) && preg_match('/^\d{4,10}$/', $c1) && preg_match('/[A-Za-z]{3,}/', $c2) && !preg_match($headerBlacklist, $c2)) {
                                $extractedStudents[] = ['nis' => $c1, 'name' => $c2];
                            }
                            // Pola NIS, Nama
                            elseif (preg_match('/^\d{4,10}$/', $c0) && preg_match('/[A-Za-z]{3,}/', $c1) && !preg_match($headerBlacklist, $c1)) {
                                $extractedStudents[] = ['nis' => $c0, 'name' => $c1];
                            }
                            // Pola No, Nama, NIS
                            elseif (preg_match('/^\d{1,3}$/', $c0) && preg_match('/[A-Za-z]{3,}/', $c1) && preg_match('/^\d{4,10}$/', $c2) && !preg_match($headerBlacklist, $c1)) {
                                $extractedStudents[] = ['nis' => $c2, 'name' => $c1];
                            }
                        }
                    }

                    // Jika tabel tidak terdeteksi, lakukan ekstraksi baris teks layout
                    if (empty($extractedStudents)) {
                        foreach ($lines as $line) {
                            if (preg_match($headerBlacklist, $line)) continue;

                            if (preg_match('/^\s*(?:\d{1,3}[\.\)]\s*)?(\d{4,10})\s+([A-Za-z\s\'\.\,\-]+?)(?:\s+[LP]|\s+(?:X|XI|XII)|\s*$)/i', $line, $p1)) {
                                $extractedStudents[] = ['nis' => trim($p1[1]), 'name' => trim($p1[2])];
                            } elseif (preg_match('/\b(\d{4,10})\b/', $line, $p3)) {
                                $nisP = $p3[1];
                                $cleaned = str_replace($nisP, '', $line);
                                $cleaned = preg_replace('/^\s*\d{1,3}[\.\)]\s*/', '', $cleaned);
                                $cleaned = preg_replace('/\b[LP]\b$/i', '', $cleaned);
                                $cleaned = trim($cleaned, " \t\n\r\0\x0B|,-;");
                                if (strlen($cleaned) >= 3 && preg_match('/[A-Za-z]/', $cleaned) && !preg_match($headerBlacklist, $cleaned)) {
                                    $extractedStudents[] = ['nis' => $nisP, 'name' => $cleaned];
                                }
                            }
                        }
                    }

                    // Simpan setiap siswa yang diekstrak dari halaman ini
                    foreach ($extractedStudents as $item) {
                        $this->saveStudent(
                            name: $item['name'],
                            nis: $item['nis'],
                            schoolClassId: $schoolClassId,
                            birthDate: null,
                            phone: null,
                            address: null,
                            studentRole: $studentRole,
                            parentRole: $parentRole,
                            created: $created,
                            updated: $updated,
                            createdParents: $createdParents,
                            skipped: $skipped,
                            duplicateAction: $duplicateAction,
                            email: null,
                            cache: $cache
                        );
                    }
                }

                if (!empty($cache['batchNew'])) {
                    foreach (array_chunk($cache['batchNew'], 100) as $chunk) {
                        User::insert($chunk);
                    }
                    $cache['batchNew'] = [];
                }
            });

            return [
                'created'         => $created,
                'updated'         => $updated,
                'skipped'         => $skipped,
                'created_classes' => $createdClasses,
                'created_majors'  => $createdMajors,
                'created_parents' => $createdParents,
            ];
        }

        // 2. Fallback Native Parser (Smalot)
        $parser = new PdfParser();
        $pdf = $parser->parseFile($file->getRealPath());
        $pages = $pdf->getPages();

        foreach ($pages as $page) {
            $text = $page->getText();
            $lines = array_filter(array_map('trim', explode("\n", $text)));
            if (empty($lines)) continue;

            $pageClass = $fileClassContext;
            $topText = implode(' ', array_slice($lines, 0, 10));

            if (preg_match('/(?:KELAS|ROMBEL|TINGKAT)\s*[:=]?\s*([X|XI|XII]+[A-Za-z0-9\s\.\-]+)/i', $topText, $cm)) {
                $cCand = preg_replace('/\s*(SEMESTER|TAHUN|MATA|JUMLAH|WALI).*/i', '', trim($cm[1]));
                $pageClass = $this->normalizeClassName($cCand);
            } elseif (preg_match('/\b(X|XI|XII)\s+([A-Za-z]+)\s*(\d*)\b/i', $topText, $cm2)) {
                $pageClass = strtoupper($cm2[1]) . ' ' . strtoupper($cm2[2]) . ($cm2[3] ? ' ' . $cm2[3] : '');
            }

            if (empty($pageClass)) {
                $pageClass = 'X Umum';
            }

            $schoolClassId = $this->resolveClassIdFast($pageClass, $cache, $createdClasses, $createdMajors);

            $extractedStudents = [];

            foreach ($lines as $line) {
                if (preg_match($headerBlacklist, $line)) continue;

                $nis = null;
                $nameCand = null;

                if (preg_match('/^\s*(?:\d{1,3}[\.\)]\s*)?(\d{4,10})\s+([A-Za-z\s\'\.\,\-]+?)(?:\s+[LP]|\s+(?:X|XI|XII)|\s*$)/i', $line, $p1)) {
                    $nis = trim($p1[1]);
                    $nameCand = trim($p1[2]);
                } elseif (preg_match('/\b(\d{4,10})\b/', $line, $p3)) {
                    $nis = $p3[1];
                    $cleaned = str_replace($nis, '', $line);
                    $cleaned = preg_replace('/^\s*\d{1,3}[\.\)]\s*/', '', $cleaned);
                    $cleaned = preg_replace('/\b[LP]\b$/i', '', $cleaned);
                    $nameCand = trim($cleaned, " \t\n\r\0\x0B|,-;");
                }

                if ($nameCand) {
                    $nameCand = trim(preg_replace('/^\s*\d{1,3}[\.\)]\s*/', '', $nameCand));
                    $nameCand = trim(preg_replace('/\b[LP]\b$/i', '', $nameCand));
                    $nameCand = trim($nameCand, " \t\n\r\0\x0B|,-;");

                    if (strlen($nameCand) >= 3 && preg_match('/[A-Za-z]/', $nameCand) && !preg_match($headerBlacklist, $nameCand)) {
                        $extractedStudents[] = [
                            'name' => $nameCand,
                            'nis'  => $nis,
                        ];
                    }
                }
            }

            foreach ($extractedStudents as $item) {
                $this->saveStudent(
                    name: $item['name'],
                    nis: $item['nis'],
                    schoolClassId: $schoolClassId,
                    birthDate: null,
                    phone: null,
                    address: null,
                    studentRole: $studentRole,
                    parentRole: $parentRole,
                    created: $created,
                    updated: $updated,
                    createdParents: $createdParents,
                    skipped: $skipped,
                    duplicateAction: $duplicateAction,
                    email: null,
                    cache: $cache
                );
            }
        }

        return [
            'created'         => $created,
            'updated'         => $updated,
            'skipped'         => $skipped,
            'created_classes' => $createdClasses,
            'created_majors'  => $createdMajors,
            'created_parents' => $createdParents,
        ];
    }

    /**
     * Parsing Data Siswa dari Spreadsheet Excel / CSV (Mendukung Multi-Sheet, Absensi Rombel, & Format Tabular)
     */
    protected function parseStudentsSpreadsheet(UploadedFile $file, string $duplicateAction = 'replace'): array
    {
        $allSheets = $this->spreadsheetService->parseAllSheets($file);

        $studentRole = Role::firstOrCreate(['role_name' => 'Siswa']);
        $parentRole  = Role::firstOrCreate(['role_name' => 'Orang Tua']);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $createdClasses = 0;
        $createdMajors = 0;
        $createdParents = 0;

        // In-Memory Preloaded Cache untuk menghilangkan ribuan round-trip queries remote Supabase
        $existingStudentsByNis = User::whereNotNull('nis')->pluck('id', 'nis')->toArray();
        $existingStudentsByNameClass = [];
        $studentRows = User::where('role_id', $studentRole->id)->get(['id', 'name', 'class_id']);
        foreach ($studentRows as $sr) {
            $key = strtolower(trim($sr->name)) . '_' . ($sr->class_id ?? 0);
            $existingStudentsByNameClass[$key] = $sr->id;
        }

        $existingUsernames = User::whereNotNull('username')->pluck('username')->flip()->toArray();
        $existingClasses = SchoolClass::pluck('id', 'class_name')->toArray();
        $existingMajors = Major::pluck('id', 'code')->toArray();
        $defaultPasswordHash = Hash::make('password123');

        $cache = [
            'existingNis'       => &$existingStudentsByNis,
            'existingNames'     => &$existingStudentsByNameClass,
            'existingUsernames' => &$existingUsernames,
            'existingClasses'   => &$existingClasses,
            'existingMajors'    => &$existingMajors,
            'passwordHash'      => $defaultPasswordHash,
            'batchNew'          => [],
        ];

        // Cek nama kelas dari nama berkas jika tidak ada deklarasi kelas di dalam lembar
        $classFromFileName = null;
        if (preg_match('/(X|XI|XII)[\s_]+([A-Za-z]+)[\s_]*(\d*)/i', $file->getClientOriginalName(), $fnMatches)) {
            $classFromFileName = strtoupper($fnMatches[1]) . ' ' . strtoupper($fnMatches[2]) . ($fnMatches[3] ? ' ' . $fnMatches[3] : '');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $allSheets, $duplicateAction, $studentRole, $parentRole,
            &$created, &$updated, &$skipped, &$createdClasses, &$createdMajors, &$createdParents,
            &$cache, $classFromFileName
        ) {
            foreach ($allSheets as $sheetName => $matrix) {
                if (empty($matrix)) continue;

                $currentClass = null;

                // Jika nama sheet itu sendiri adalah nama rombel (misal: "X RPL 1", "XI TKJ 2")
                if (preg_match('/^(X|XI|XII)\s+[A-Za-z0-9\s]+$/i', trim($sheetName))) {
                    $currentClass = $this->normalizeClassName($sheetName);
                }

                $headers = null;
                $rowCount = count($matrix);

                for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
                    $row = $matrix[$rowIndex];
                    if (empty($row)) continue;

                    $rowText = implode(' ', array_filter($row));

                    // 1. Deteksi Baris Deklarasi Kelas (Contoh: "KELAS : X RPL 1" atau "ROMBEL : XI TKJ 1")
                    if (preg_match('/(?:KELAS|ROMBEL|TINGKAT)\s*[:=]\s*([X|XI|XII]+[A-Za-z0-9\s\.\-]+)/i', $rowText, $cm)) {
                        $detectedClass = trim($cm[1]);
                        $detectedClass = preg_replace('/\s*(SEMESTER|TAHUN|MATA|GURU|JUMLAH|WALI).*/i', '', $detectedClass);
                        $normDetected = $this->normalizeClassName($detectedClass);
                        if (!empty($normDetected)) {
                            $currentClass = $normDetected;
                        }
                        continue;
                    }

                    // 2. Deteksi Format Presensi / Roster Siswa (Seperti Berkas "Absen 2026-2027.xlsx")
                    $col0 = trim((string)($row[0] ?? ''));
                    $col1 = trim((string)($row[1] ?? ''));
                    $col2 = trim((string)($row[2] ?? ''));
                    $col3 = trim((string)($row[3] ?? ''));

                    $isRoster = false;
                    $nisVal = null;
                    $nameVal = null;

                    // Format Roster 1: Col 0 = No urut, Col 1 = NIS, Col 2 = Nama Siswa
                    if (preg_match('/^\d{1,3}$/', $col0) && preg_match('/^\d{4,10}$/', $col1) && preg_match('/[A-Za-z]{3,}/', $col2) && !preg_match('/^(NAMA|SISWA|NOMOR|NIS|URT)/i', $col2)) {
                        $isRoster = true;
                        $nisVal = $col1;
                        $nameVal = $col2;
                    }
                    // Format Roster 2: Col 0 = NIS, Col 1 = Nama Siswa
                    elseif (preg_match('/^\d{4,10}$/', $col0) && preg_match('/[A-Za-z]{3,}/', $col1) && !preg_match('/^(NAMA|SISWA|NOMOR|NIS|URT)/i', $col1)) {
                        $isRoster = true;
                        $nisVal = $col0;
                        $nameVal = $col1;
                    }
                    // Format Roster 3: Col 0 = No urut, Col 1 = Nama Siswa, Col 2 = NIS
                    elseif (preg_match('/^\d{1,3}$/', $col0) && preg_match('/[A-Za-z]{3,}/', $col1) && preg_match('/^\d{4,10}$/', $col2) && !preg_match('/^(NAMA|SISWA|NOMOR|NIS|URT)/i', $col1)) {
                        $isRoster = true;
                        $nisVal = $col2;
                        $nameVal = $col1;
                    }

                    if ($isRoster && !empty($nameVal)) {
                        $effectiveClass = $currentClass ?: ($classFromFileName ?: $this->resolveFallbackClass($sheetName));
                        $normEffectiveClass = $this->normalizeClassName($effectiveClass);

                        $schoolClassId = $this->resolveClassIdFast($normEffectiveClass, $cache, $createdClasses, $createdMajors);

                        $this->saveStudent(
                            name: $nameVal,
                            nis: $nisVal,
                            schoolClassId: $schoolClassId,
                            birthDate: null,
                            phone: null,
                            address: null,
                            studentRole: $studentRole,
                            parentRole: $parentRole,
                            created: $created,
                            updated: $updated,
                            createdParents: $createdParents,
                            skipped: $skipped,
                            duplicateAction: $duplicateAction,
                            email: null,
                            cache: $cache
                        );
                        continue;
                    }

                    // 3. Deteksi Baris Header Tabel Tabular Standar
                    if ($headers === null) {
                        $rowLower = array_map(fn($c) => strtolower(trim((string)$c)), $row);
                        $hasNama = in_array('nama', $rowLower) || in_array('nama_siswa', $rowLower) || in_array('nama lengkap', $rowLower);
                        $hasNis  = in_array('nis', $rowLower) || in_array('nisn', $rowLower) || in_array('no induk', $rowLower);

                        if ($hasNama || ($hasNis && count($rowLower) >= 2)) {
                            $headers = [];
                            foreach ($row as $ci => $h) {
                                $slug = strtolower(trim((string)$h));
                                $slug = str_replace([' ', '-', '/', '.'], '_', $slug);
                                $slug = preg_replace('/[^a-z0-9_]/', '', $slug);
                                $headers[$ci] = $slug;
                            }
                            continue;
                        }
                    }

                    // 4. Proses Baris Tabular Berdasarkan Header yang Ditemukan
                    if ($headers !== null) {
                        $assocRow = [];
                        foreach ($headers as $ci => $hName) {
                            if (!empty($hName)) {
                                $assocRow[$hName] = trim((string)($row[$ci] ?? ''));
                            }
                        }

                        $name = $this->getField($assocRow, ['nama_siswa', 'nama_lengkap', 'nama_peserta_didik', 'peserta_didik', 'nama_peserta', 'siswa', 'nama', 'name', 'student_name']);
                        if (empty($name)) continue;

                        $nis = $this->getField($assocRow, ['nis', 'nisn', 'nis_nisn', 'no_induk', 'nomor_induk', 'no_induk_siswa', 'nomor_induk_siswa', 'nisn_nis']) ?: null;
                        $phone = $this->getField($assocRow, ['phone', 'no_hp', 'nomor_hp', 'nohp', 'no_telepon', 'nomor_telepon', 'telepon', 'telp', 'no_telp', 'kontak', 'no_kontak', 'whatsapp', 'no_wa', 'nomor_wa']) ?: null;
                        $address = $this->getField($assocRow, ['address', 'alamat', 'alamat_siswa', 'domisili', 'tempat_tinggal']) ?: null;
                        $birthDate = $this->getField($assocRow, ['birth_date', 'tanggal_lahir', 'tgl_lahir', 'tgllahir', 'tanggal_lahir_siswa', 'tgl_lahir_siswa']) ?: null;
                        $email = $this->getField($assocRow, ['email', 'email_siswa', 'surel', 'e_mail']) ?: null;

                        $rawClassName = $this->getField($assocRow, ['kelas', 'rombel', 'nama_kelas', 'rombongan_belajar', 'kelas_rombel', 'tingkat', 'jurusan']);
                        $effectiveClass = $rawClassName ?: ($currentClass ?: ($classFromFileName ?: $this->resolveFallbackClass($sheetName)));
                        $normClass = $this->normalizeClassName($effectiveClass);

                        $schoolClassId = $this->resolveClassIdFast($normClass, $cache, $createdClasses, $createdMajors);

                        $this->saveStudent(
                            name: $name,
                            nis: $nis,
                            schoolClassId: $schoolClassId,
                            birthDate: $birthDate,
                            phone: $phone,
                            address: $address,
                            studentRole: $studentRole,
                            parentRole: $parentRole,
                            created: $created,
                            updated: $updated,
                            createdParents: $createdParents,
                            skipped: $skipped,
                            duplicateAction: $duplicateAction,
                            email: $email,
                            cache: $cache
                        );
                    }
                }
            }

            if (!empty($cache['batchNew'])) {
                foreach (array_chunk($cache['batchNew'], 100) as $chunk) {
                    User::insert($chunk);
                }
                $cache['batchNew'] = [];
            }
        });

        return [
            'created'         => $created,
            'updated'         => $updated,
            'skipped'         => $skipped,
            'created_classes' => $createdClasses,
            'created_majors'  => $createdMajors,
            'created_parents' => $createdParents,
        ];
    }

    /**
     * Resolusi Kelas dan Jurusan secara Cepat Menggunakan In-Memory Cache
     */
    protected function resolveClassIdFast(string $normClass, array &$cache, int &$createdClasses, int &$createdMajors): int
    {
        if (preg_match('/^(?:X|XI|XII)\s+([A-Z]+)/i', $normClass, $m)) {
            $mCode = strtoupper($m[1]);
            if (!isset($cache['existingMajors'][$mCode])) {
                $major = Major::create([
                    'code'        => $mCode,
                    'name'        => "Jurusan {$mCode}",
                    'rombel_x_xi' => 2,
                    'rombel_xii'  => 2,
                ]);
                $cache['existingMajors'][$mCode] = $major->id;
                $createdMajors++;
            }
        }

        if (!isset($cache['existingClasses'][$normClass])) {
            $sc = SchoolClass::create(['class_name' => $normClass]);
            $cache['existingClasses'][$normClass] = $sc->id;
            $createdClasses++;
        }

        return $cache['existingClasses'][$normClass];
    }

    /**
     * Menentukan fallback nama kelas dari nama sheet
     */
    protected function resolveFallbackClass(string $sheetName): string
    {
        $sheetName = trim($sheetName);
        if (preg_match('/^(X|XI|XII)\s+[A-Za-z0-9\s]+$/i', $sheetName)) {
            return $this->normalizeClassName($sheetName);
        }
        if (strcasecmp($sheetName, 'X') === 0) return 'X Umum';
        if (strcasecmp($sheetName, 'XI') === 0) return 'XI Umum';
        if (strcasecmp($sheetName, 'XII') === 0) return 'XII Umum';
        return !empty($sheetName) ? $this->normalizeClassName($sheetName) : 'X Umum';
    }

    /**
     * Helper Menyimpan / Auto-Provisioning Akun Siswa & Akun Orang Tua (Dengan In-Memory Cache)
     */
    protected function saveStudent(
        string $name,
        ?string $nis,
        int $schoolClassId,
        ?string $birthDate,
        ?string $phone,
        ?string $address,
        Role $studentRole,
        Role $parentRole,
        int &$created,
        int &$updated,
        int &$createdParents,
        int &$skipped = 0,
        string $duplicateAction = 'replace',
        ?string $email = null,
        ?array &$cache = null
    ): void {
        $cleanName = trim($name);
        $formattedBirth = null;
        if ($birthDate) {
            $ts = strtotime($birthDate);
            if ($ts !== false) $formattedBirth = date('Y-m-d', $ts);
        }

        $cleanPhone = null;
        if ($phone) {
            $digits = preg_replace('/[^0-9\+]/', '', (string)$phone);
            if (strlen($digits) >= 6 && strlen($digits) <= 25) {
                $cleanPhone = $digits;
            }
        }

        // Cari siswa yang sudah ada berdasarkan NIS atau Nama + Kelas
        $studentId = null;
        if ($cache) {
            if ($nis && isset($cache['existingNis'][$nis])) {
                $studentId = $cache['existingNis'][$nis];
            } else {
                $nameKey = strtolower($cleanName) . '_' . $schoolClassId;
                if (isset($cache['existingNames'][$nameKey])) {
                    $studentId = $cache['existingNames'][$nameKey];
                }
            }
        } else {
            $student = null;
            if ($nis) {
                $student = User::where('nis', $nis)->first();
            }
            if (!$student) {
                $student = User::where('name', $cleanName)
                    ->where('class_id', $schoolClassId)
                    ->whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
                    ->first();
            }
            if ($student) {
                $studentId = $student->id;
            }
        }

        // Jika data siswa sudah terdaftar sebelumnya (dobel)
        if ($studentId) {
            if ($duplicateAction === 'keep') {
                $skipped++;
                return;
            }

            $updateData = [
                'name'           => $cleanName,
                'class_id'       => $schoolClassId,
                'student_status' => 'Aktif',
            ];

            if ($nis) $updateData['nis'] = $nis;
            if ($cleanPhone !== null) $updateData['phone'] = $cleanPhone;
            if ($address !== null) $updateData['address'] = $address;
            if ($formattedBirth !== null) $updateData['birth_date'] = $formattedBirth;

            if (!empty($email)) {
                $updateData['email'] = $email;
            }

            // Jika siswa sudah ada namun belum memiliki akun orang tua, buatkan otomatis
            $existingStudent = User::find($studentId);
            if ($existingStudent && !$existingStudent->parent_id) {
                $baseParentUser = $nis ? "ortu_{$nis}" : "ortu_" . $existingStudent->username;
                $uniqueParentUser = substr($baseParentUser, 0, 45);
                $pCounter = 1;
                while (User::where('username', $uniqueParentUser)->exists()) {
                    $uniqueParentUser = substr($baseParentUser, 0, 38) . "_{$pCounter}";
                    $pCounter++;
                }
                $parentPasswordPlain = $nis ? "p{$nis}" : "portu123";
                $parentUser = User::create([
                    'name'           => 'Orang tua ' . $cleanName,
                    'username'       => $uniqueParentUser,
                    'password'       => Hash::make($parentPasswordPlain),
                    'role_id'        => $parentRole->id,
                    'phone'          => $cleanPhone,
                    'address'        => $address,
                    'student_status' => 'Aktif',
                ]);
                $updateData['parent_id'] = $parentUser->id;
                $createdParents++;
            }

            User::where('id', $studentId)->update($updateData);
            $updated++;
        } else {
            // Data baru: buat akun siswa
            $baseUser = $nis ? "siswa_{$nis}" : Str::slug($cleanName, '.');
            if (empty($baseUser)) $baseUser = 'siswa_' . Str::random(5);
            $baseUser = substr($baseUser, 0, 35);

            $uniqueUser = $baseUser;
            $counter = 1;
            if ($cache) {
                while (isset($cache['existingUsernames'][$uniqueUser])) {
                    $uniqueUser = "{$baseUser}{$counter}";
                    $counter++;
                }
                $cache['existingUsernames'][$uniqueUser] = true;
            } else {
                while (User::where('username', $uniqueUser)->exists()) {
                    $uniqueUser = "{$baseUser}{$counter}";
                    $counter++;
                }
            }

            $finalEmail = !empty($email) ? $email : null;
            $passHash = $cache['passwordHash'] ?? Hash::make('password123');

            // Otomatis buatkan akun Orang Tua siswa
            $baseParentUser = $nis ? "ortu_{$nis}" : "ortu_{$uniqueUser}";
            $uniqueParentUser = substr($baseParentUser, 0, 45);
            $pCounter = 1;
            if ($cache) {
                while (isset($cache['existingUsernames'][strtolower($uniqueParentUser)])) {
                    $uniqueParentUser = substr($baseParentUser, 0, 38) . "_{$pCounter}";
                    $pCounter++;
                }
                $cache['existingUsernames'][strtolower($uniqueParentUser)] = true;
            } else {
                while (User::where('username', $uniqueParentUser)->exists()) {
                    $uniqueParentUser = substr($baseParentUser, 0, 38) . "_{$pCounter}";
                    $pCounter++;
                }
            }

            $parentPasswordPlain = $nis ? "p{$nis}" : "portu123";
            $parentPassHash = Hash::make($parentPasswordPlain);

            $parentUser = User::create([
                'name'           => 'Orang tua ' . $cleanName,
                'username'       => $uniqueParentUser,
                'password'       => $parentPassHash,
                'role_id'        => $parentRole->id,
                'phone'          => $cleanPhone,
                'address'        => $address,
                'student_status' => 'Aktif',
            ]);
            $parentId = $parentUser->id;
            $createdParents++;

            if ($cache && isset($cache['batchNew'])) {
                $cache['batchNew'][] = [
                    'name'           => $cleanName,
                    'username'       => $uniqueUser,
                    'nis'            => $nis,
                    'email'          => $finalEmail,
                    'class_id'       => $schoolClassId,
                    'parent_id'      => $parentId,
                    'phone'          => $cleanPhone,
                    'address'        => $address,
                    'birth_date'     => $formattedBirth,
                    'password'       => $passHash,
                    'role_id'        => $studentRole->id,
                    'student_status' => 'Aktif',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
                if ($nis) $cache['existingNis'][$nis] = true;
                $cache['existingNames'][strtolower($cleanName) . '_' . $schoolClassId] = true;
                $created++;
            } else {
                $newStudent = User::create([
                    'name'           => $cleanName,
                    'username'       => $uniqueUser,
                    'nis'            => $nis,
                    'email'          => $finalEmail,
                    'class_id'       => $schoolClassId,
                    'parent_id'      => $parentId,
                    'phone'          => $cleanPhone,
                    'address'        => $address,
                    'birth_date'     => $formattedBirth,
                    'password'       => $passHash,
                    'role_id'        => $studentRole->id,
                    'student_status' => 'Aktif',
                ]);

                if ($cache) {
                    if ($nis) $cache['existingNis'][$nis] = $newStudent->id;
                    $cache['existingNames'][strtolower($cleanName) . '_' . $schoolClassId] = $newStudent->id;
                }
                $created++;
            }
        }
    }


    // =========================================================================
    // 3. DATA RUANGAN (ROOMS): PDF & EXCEL/CSV AUTO-PROVISIONING
    // =========================================================================

    /**
     * Memproses berkas data ruangan baik PDF (Legenda FET / Sarpras) maupun Spreadsheet
     */
    public function importRooms(UploadedFile $file, string $duplicateAction = 'replace'): array
    {
        $this->consolidateSportsRooms();

        $extension = strtolower($file->getClientOriginalExtension());
        $res = ($extension === 'pdf')
            ? $this->parseRoomsPdf($file->getRealPath(), $duplicateAction)
            : $this->parseRoomsSpreadsheet($file, $duplicateAction);

        $this->consolidateSportsRooms();
        return $res;
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
                    \App\Models\Schedule::where('room_id', $dr->id)->update(['room_id' => $mainSportRoom->id]);
                    $dr->delete();
                }
            }
        } catch (\Throwable $e) {
            // silent fallback
        }
    }

    /**
     * Parsing Ruangan dari Dokumen PDF
     * Menghasilkan nama ruangan unik tanpa duplikasi (misal: Ruang 1 - 34, Lab 1 - 22, Lab IPA, OR, Hall)
     */
    protected function parseRoomsPdf(string $path, string $duplicateAction = 'replace'): array
    {
        $created = 0;
        $existing = 0;

        // Coba ekstrak ruangan aktual dari PDF via Python
        $pyResult = $this->spreadsheetService->runPythonParser($path);
        $extractedNames = [];

        if (!empty($pyResult['rooms'])) {
            foreach ($pyResult['rooms'] as $r) {
                if (!empty($r['name'])) {
                    $extractedNames[] = $r['name'];
                }
            }
        }

        // Daftar 55 Ruangan Standar SMK Negeri 1 Bantul
        $canonical55Rooms = [
            // 31 Ruang Teori / Kelas
            'Ruang 1', 'Ruang 2', 'Ruang 3', 'Ruang 4', 'Ruang 5', 'Ruang 6', 'Ruang 7', 'Ruang 8', 'Ruang 9', 'Ruang 10',
            'Ruang 11', 'Ruang 12', 'Ruang 13', 'Ruang 14', 'Ruang 15', 'Ruang 16', 'Ruang 17', 'Ruang 18', 'Ruang 19', 'Ruang 20',
            'Ruang 21', 'Ruang 22', 'Ruang 23', 'Ruang 24', 'Ruang 25', 'Ruang 26', 'Ruang 29', 'Ruang 30', 'Ruang 31', 'Ruang 32', 'Ruang 34',
            // Lab Komputer Aktif (1 s/d 22)
            'Laboratorium Komputer 1', 'Laboratorium Komputer 2', 'Laboratorium Komputer 3', 'Laboratorium Komputer 4', 'Laboratorium Komputer 5',
            'Laboratorium Komputer 6', 'Laboratorium Komputer 7', 'Laboratorium Komputer 8', 'Laboratorium Komputer 9', 'Laboratorium Komputer 10',
            'Laboratorium Komputer 11', 'Laboratorium Komputer 12', 'Laboratorium Komputer 13', 'Laboratorium Komputer 14', 'Laboratorium Komputer 15',
            'Laboratorium Komputer 16', 'Laboratorium Komputer 17', 'Laboratorium Komputer 18', 'Laboratorium Komputer 19', 'Laboratorium Komputer 20',
            'Laboratorium Komputer 21', 'Laboratorium Komputer 22',
            // Fasilitas Khusus
            'Laboratorium IPA',
            'Laboratorium Penataan Barang',
            'Lapangan Olahraga',
            'Ruang Seni Tari / Hall',
            'SINIAR'
        ];

        $targetRooms = !empty($extractedNames) ? array_merge($extractedNames, $canonical55Rooms) : $canonical55Rooms;

        $fetService = app(\App\Services\FetTimetableParserService::class);
        $uniqueTargets = [];
        foreach ($targetRooms as $rRaw) {
            $cleanName = $fetService->canonicalRoomName($rRaw);
            $key = strtoupper(trim($cleanName));
            if (!empty($key) && !isset($uniqueTargets[$key])) {
                $uniqueTargets[$key] = $cleanName;
            }
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($uniqueTargets, $duplicateAction, &$created, &$existing) {
            $existingRooms = Room::all()->keyBy(fn($r) => strtoupper(trim($r->room_name)));

            foreach ($uniqueTargets as $key => $cleanName) {
                if (isset($existingRooms[$key])) {
                    $existing++;
                    // Jika aksi 'replace' dan format huruf berbeda, sinkronkan ke title case yang rapi
                    if ($duplicateAction === 'replace') {
                        $model = $existingRooms[$key];
                        if ($model->room_name !== $cleanName) {
                            $model->update(['room_name' => $cleanName]);
                        }
                    }
                } else {
                    $newR = Room::create(['room_name' => $cleanName]);
                    $existingRooms[$key] = $newR;
                    $created++;
                }
            }
        });

        return [
            'created'  => $created,
            'existing' => $existing,
            'total'    => count($uniqueTargets),
        ];
    }

    /**
     * Parsing Ruangan dari Spreadsheet Excel / CSV
     */
    protected function parseRoomsSpreadsheet(UploadedFile $file, string $duplicateAction = 'replace'): array
    {
        $rows = $this->spreadsheetService->parse($file);

        $created = 0;
        $existing = 0;
        $existingRooms = Room::all()->keyBy(fn($r) => strtoupper(trim($r->room_name)));
        $fetService = app(\App\Services\FetTimetableParserService::class);

        // Kumpulkan daftar nama ruangan unik dari seluruh baris berkas (deduplikasi baris slot waktu)
        $uniqueRooms = [];
        foreach ($rows as $row) {
            $roomName = $this->getField($row, [
                'nama_ruang', 'ruang', 'ruang_penggunaan', 'nama_ruangan', 'nama_ruangan___fasilitas',
                'nama', 'room_name', 'lab', 'laboratorium', 'fasilitas'
            ]);
            if (empty($roomName)) continue;

            $cleanName = $fetService->canonicalRoomName($roomName);
            $key = strtoupper(trim($cleanName));
            if (!empty($key) && !isset($uniqueRooms[$key])) {
                $uniqueRooms[$key] = $cleanName;
            }
        }

        foreach ($uniqueRooms as $key => $cleanName) {
            if (isset($existingRooms[$key])) {
                $existing++;
                if ($duplicateAction === 'replace') {
                    $model = $existingRooms[$key];
                    if ($model->room_name !== $cleanName) {
                        $model->update(['room_name' => $cleanName]);
                    }
                }
            } else {
                $newR = Room::create(['room_name' => $cleanName]);
                $existingRooms[$key] = $newR;
                $created++;
            }
        }

        return [
            'created'  => $created,
            'existing' => $existing,
            'total'    => count($uniqueRooms),
        ];
    }


    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Normalisasi nama rombel kelas (contoh: "X RPL1" -> "X RPL 1")
     */
    public function normalizeClassName(string $raw): string
    {
        $trimmed = trim($raw);
        if (preg_match('/^(XII|XI|X)\s*([A-Za-z]+)\s*(\d+)$/i', $trimmed, $m)) {
            return strtoupper($m[1]) . ' ' . strtoupper($m[2]) . ' ' . $m[3];
        }
        if (preg_match('/^(XII|XI|X)\s*([A-Za-z]+)$/i', $trimmed, $m)) {
            return strtoupper($m[1]) . ' ' . strtoupper($m[2]);
        }
        return $trimmed;
    }

    /**
     * Auto-Provisioning Jurusan (Major) dari Nama Kelas (Mendukung In-Memory Hash Map)
     */
    public function autoProvisionMajor(string $className, int &$createdCounter, $allMajors = null): ?Major
    {
        if (preg_match('/^(?:XII|XI|X)\s+([A-Z]+)/i', $className, $matches)) {
            $majorCode = strtoupper($matches[1]);

            if ($allMajors && isset($allMajors[$majorCode])) {
                return $allMajors[$majorCode];
            }

            $major = Major::firstOrCreate(
                ['code' => $majorCode],
                [
                    'name'        => "Jurusan {$majorCode}",
                    'rombel_x'    => 2,
                    'rombel_xi'   => 2,
                    'rombel_xii'  => 2,
                ]
            );

            if ($major->wasRecentlyCreated) {
                $createdCounter++;
            }

            if ($allMajors) {
                $allMajors[$majorCode] = $major;
            }

            return $major;
        }

        return null;
    }

    /**
     * Helper mengambil nilai dari baris spreadsheet dengan berbagai alias header
     */
    protected function getField(array $row, array $aliases, string $default = ''): string
    {
        // 1. Cek langsung key yang cocok persis
        foreach ($aliases as $alias) {
            if (isset($row[$alias]) && trim((string)$row[$alias]) !== '') {
                return trim((string)$row[$alias]);
            }
        }

        // 2. Cek loose key dengan normalisasi (HANYA jika sama persis setelah normalisasi)
        // Menghindari str_contains yang keliru (misal kata 'wa' mencocokkan 'siswa')
        foreach ($row as $key => $val) {
            $normKey = strtolower(str_replace(['_', '-', ' ', '.'], '', (string)$key));
            foreach ($aliases as $alias) {
                $normAlias = strtolower(str_replace(['_', '-', ' ', '.'], '', (string)$alias));
                if ($normKey === $normAlias) {
                    if (trim((string)$val) !== '') {
                        return trim((string)$val);
                    }
                }
            }
        }

        return $default;
    }
}
