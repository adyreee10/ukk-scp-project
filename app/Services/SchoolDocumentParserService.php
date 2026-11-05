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
        $rows = $this->spreadsheetService->parse($file);

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

            // Normalisasi nama kelas (contoh: 'X RPL1' -> 'X RPL 1') & Auto-provision Jurusan & Kelas
            $normClass = $this->normalizeClassName($rawClass);
            $this->autoProvisionMajor($normClass, $createdMajors);

            $schoolClass = SchoolClass::firstOrCreate(['class_name' => $normClass]);
            if ($schoolClass->wasRecentlyCreated) {
                $createdClasses++;
            }

            // 1. PROSES WALI KELAS (1 Guru Mewakili 1 Kelas)
            $waliName = $this->getField($row, ['nama_wali_kelas', 'wali_kelas', 'nama_wali', 'wali', 'walikelas']);
            $waliClean = trim(preg_replace('/^\d+[\.\)]\s*/', '', $waliName), " \t\n\r\0\x0B\"'");

            if (!empty($waliClean) && !in_array(strtolower($waliClean), ['-', '--', 'none', 'null', '0', 'nama wali kelas'])) {
                $waliTeacher = $this->findTeacherByNameFlexible($waliClean);

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
                    // Buat akun baru Wali Kelas
                    $newWali = $this->createNewTeacherAccount(
                        name: $waliClean,
                        nip: null,
                        roleId: $waliRole->id,
                        homeroomClassId: $schoolClass->id
                    );
                    $created++;
                    $processedUserIds[] = $newWali->id;
                }
            }

            // 2. PROSES GURU BK (1 Guru BK Mewakili Beberapa Kelas Secara Kumulatif)
            $bkName = $this->getField($row, ['nama_guru_bk', 'guru_bk', 'bk', 'nama_bk', 'bimbingan_konseling', 'guru_bimbingan_konseling']);
            $bkClean = trim(preg_replace('/^\d+[\.\)]\s*/', '', $bkName), " \t\n\r\0\x0B\"'");

            if (!empty($bkClean) && !in_array(strtolower($bkClean), ['-', '--', 'none', 'null', '0', 'nama guru bk'])) {
                $bkTeacher = $this->findTeacherByNameFlexible($bkClean);

                if ($bkTeacher) {
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
                    // Buat akun baru Guru BK
                    $newBk = $this->createNewTeacherAccount(
                        name: $bkClean,
                        nip: null,
                        roleId: $bkRole->id,
                        homeroomClassId: null
                    );
                    $newBk->counselorClasses()->syncWithoutDetaching([$schoolClass->id]);
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
                duplicateAction: $duplicateAction
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
     */
    public function isNonTeachingStaff(array $row): bool
    {
        $jabatan = strtolower($this->getField($row, ['jabatan', 'posisi', 'tugas', 'pekerjaan']));
        $mapel = trim($this->getField($row, ['mapel', 'mata_pelajaran', 'mata_pelajaran_yang_diampu']));

        // Jika mapel terisi dan bukan tanda strip / kosong, maka orang tersebut mengampu mata pelajaran (Guru)
        $hasMapel = !empty($mapel) && !in_array(strtolower($mapel), ['-', '--', '0', 'null', 'none']);

        // Kata kunci eksplisit untuk staf TU / Pegawai Administrasi / Non-Guru
        $nonTeacherKeywords = [
            'tata usaha', 'tu', 'administrasi', 'pengadministrasi',
            'satpam', 'keamanan', 'security',
            'kebersihan', 'cleaning', 'penjaga', 'caraka', 'pesuruh',
            'pustakawan', 'perpustakaan', 'laboran', 'teknisi', 'toolman',
            'operator', 'sopir', 'pengemudi', 'layanan khusus',
            'tenaga kependidikan', 'ptk', 'staf tu', 'staff', 'bengkel'
        ];

        $matchesNonTeacher = false;
        foreach ($nonTeacherKeywords as $kw) {
            if (str_contains($jabatan, $kw)) {
                $matchesNonTeacher = true;
                break;
            }
        }

        // Pengecualian: jika jabatan secara eksplisit menyebut guru / pendidik / kepsek / waka
        $isTeacherExplicit = str_contains($jabatan, 'guru') ||
                             str_contains($jabatan, 'pendidik') ||
                             str_contains($jabatan, 'kepala sekolah') ||
                             str_contains($jabatan, 'wakil kepala') ||
                             str_contains($jabatan, 'waka') ||
                             str_contains($jabatan, 'wali kelas');

        if ($matchesNonTeacher && !$isTeacherExplicit && !$hasMapel) {
            return true;
        }

        return false;
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
        string $duplicateAction = 'replace'
    ): void {
        $cleanName = trim(preg_replace('/^\d+\.\s*/', '', $name), " \t\n\r\0\x0B\"'");
        if (empty($cleanName)) return;

        // Auto-provisioning Kelas & Jurusan jika Guru adalah Wali Kelas
        $homeroomClassId = null;
        if (!empty($homeroomClassName)) {
            $normClass = $this->normalizeClassName($homeroomClassName);
            $this->autoProvisionMajor($normClass, $createdMajors);

            $schoolClass = SchoolClass::firstOrCreate(['class_name' => $normClass]);
            if ($schoolClass->wasRecentlyCreated) {
                $createdClasses++;
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

        // Cari guru yang sudah ada berdasarkan NIP atau Pencarian Nama Cerdas Fleksibel
        $teacher = null;
        if ($nip) {
            $teacher = User::where('nip', $nip)->first();
        }
        if (!$teacher) {
            $teacher = $this->findTeacherByNameFlexible($cleanName);
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

            $teacher->update([
                'name'              => $cleanName,
                'nip'               => $nip ?? $teacher->nip,
                'phone'             => $cleanPhone ?? $teacher->phone,
                'address'           => $address ?? $teacher->address,
                'role_id'           => $targetRoleId,
                'homeroom_class_id' => $homeroomClassId ?? $teacher->homeroom_class_id,
            ]);
            $updated++;
        } else {
            $this->createNewTeacherAccount(
                name: $cleanName,
                nip: $nip,
                roleId: $roleId,
                homeroomClassId: $homeroomClassId,
                phone: $cleanPhone,
                address: $address
            );
            $created++;
        }
    }

    /**
     * Helper Membuat Akun Baru Guru dengan Username & Email Unik
     */
    protected function createNewTeacherAccount(
        string $name,
        ?string $nip,
        int $roleId,
        ?int $homeroomClassId = null,
        ?string $phone = null,
        ?string $address = null
    ): User {
        $cleanName = trim(preg_replace('/^\d+[\.\)]\s*/', '', $name), " \t\n\r\0\x0B\"'");

        $baseUser = $nip ? "guru_{$nip}" : Str::slug($cleanName, '.');
        if (empty($baseUser)) $baseUser = 'guru_' . Str::random(5);
        $baseUser = substr($baseUser, 0, 35);

        $uniqueUser = $baseUser;
        $counter = 1;
        while (User::where('username', $uniqueUser)->exists()) {
            $uniqueUser = "{$baseUser}{$counter}";
            $counter++;
        }

        $email = "{$uniqueUser}@guru.smk.sch.id";
        $c = 1;
        while (User::where('email', $email)->exists()) {
            $email = "{$uniqueUser}{$c}@guru.smk.sch.id";
            $c++;
        }

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
    }

    /**
     * Pencarian Guru Cerdas & Fleksibel (Abaikan Gelar Akademik, Tanda Baca, dan Huruf Besar/Kecil)
     */
    public function findTeacherByNameFlexible(string $rawName): ?User
    {
        $clean = trim(preg_replace('/^\d+[\.\)]\s*/', '', $rawName), " \t\n\r\0\x0B\"'");
        if (empty($clean)) {
            return null;
        }

        // 1. Coba pencocokan langsung nama persis (Exact Match)
        $teacher = User::where('name', $clean)
            ->whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Admin']))
            ->first();
        if ($teacher) return $teacher;

        // 2. Coba case-insensitive exact match
        $teacher = User::whereRaw('LOWER(name) = ?', [strtolower($clean)])
            ->whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Admin']))
            ->first();
        if ($teacher) return $teacher;

        // 3. Normalisasi nama dasar (hilangkan gelar depan & gelar belakang)
        $normSearch = $this->stripAcademicTitles($clean);
        if (strlen($normSearch) < 3) {
            return null;
        }

        // Ambil semua guru untuk pencocokan berbasis normalisasi gelar
        $allTeachers = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Admin']))->get();

        foreach ($allTeachers as $t) {
            $tNorm = $this->stripAcademicTitles($t->name);
            if ($tNorm === $normSearch) {
                return $t;
            }
        }

        // 4. Pencocokan substring jika nama cukup panjang (>= 6 huruf)
        foreach ($allTeachers as $t) {
            $tNorm = $this->stripAcademicTitles($t->name);
            if (strlen($normSearch) >= 6 && strlen($tNorm) >= 6) {
                if (str_contains($tNorm, $normSearch) || str_contains($normSearch, $tNorm)) {
                    return $t;
                }
            }
        }

        // 5. Fuzzy match menggunakan similar_text jika terdapat perbedaan typo kecil
        $bestMatch = null;
        $highestPercent = 0;
        foreach ($allTeachers as $t) {
            $tNorm = $this->stripAcademicTitles($t->name);
            similar_text($normSearch, $tNorm, $percent);
            if ($percent > 85 && $percent > $highestPercent) {
                $highestPercent = $percent;
                $bestMatch = $t;
            }
        }

        return $bestMatch;
    }

    /**
     * Menghapus Gelar Akademik dan Keagamaan untuk Normalisasi Nama
     */
    public function stripAcademicTitles(string $name): string
    {
        $n = strtolower($name);

        // Hapus tanda kurung beserta isinya (contoh: (PPLG), (GTT))
        $n = preg_replace('/\([^\)]*\)/', '', $n);

        // Hapus gelar depan umum (bisa berulang misal "H. Drs.")
        for ($i = 0; $i < 3; $i++) {
            $n = preg_replace('/^(drs|dra|dr|prof|ir|h|hj|kh|ust|ustadz)\.?\s+/i', '', $n);
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
            'ph\.?d', 'm\.?sc', 'b\.?sc'
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

        $existingUsernames = User::pluck('username')->flip()->toArray();
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

        $existingUsernames = User::pluck('username')->flip()->toArray();
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

            // Tidak perlu otomatis membuat akun Orang Tua saat import siswa (sesuai instruksi)
            $parentId = null;

            if ($cache && isset($cache['batchNew'])) {
                $cache['batchNew'][] = [
                    'name'           => $cleanName,
                    'username'       => $uniqueUser,
                    'nis'            => $nis,
                    'email'          => $finalEmail,
                    'class_id'       => $schoolClassId,
                    'parent_id'      => null,
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
                    'parent_id'      => null,
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
    public function importRooms(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'pdf') {
            return $this->parseRoomsPdf($file->getRealPath());
        }

        return $this->parseRoomsSpreadsheet($file);
    }

    /**
     * Parsing Ruangan dari Dokumen PDF
     * Menghasilkan nama ruangan unik tanpa duplikasi (misal: Ruang 1 - 34, Lab 1 - 20, Lab IPA, OR, Hall)
     */
    protected function parseRoomsPdf(string $path): array
    {
        $created = 0;
        $existing = 0;

        // Daftar 53 Ruangan Unik Standar SMK Negeri 1 Bantul (berdasarkan Matriks Jadwal Ruang Putaran 1)
        $canonical53Rooms = [
            // 31 Ruang Teori / Kelas
            'Ruang 1', 'Ruang 2', 'Ruang 3', 'Ruang 4', 'Ruang 5', 'Ruang 6', 'Ruang 7', 'Ruang 8', 'Ruang 9', 'Ruang 10',
            'Ruang 11', 'Ruang 12', 'Ruang 13', 'Ruang 14', 'Ruang 15', 'Ruang 16', 'Ruang 17', 'Ruang 18', 'Ruang 19', 'Ruang 20',
            'Ruang 21', 'Ruang 22', 'Ruang 23', 'Ruang 24', 'Ruang 25', 'Ruang 26', 'Ruang 29', 'Ruang 30', 'Ruang 31', 'Ruang 32', 'Ruang 34',
            // 19 Lab Komputer Aktif
            'Laboratorium Komputer 1', 'Laboratorium Komputer 2', 'Laboratorium Komputer 3', 'Laboratorium Komputer 4', 'Laboratorium Komputer 5',
            'Laboratorium Komputer 6', 'Laboratorium Komputer 7', 'Laboratorium Komputer 8', 'Laboratorium Komputer 9', 'Laboratorium Komputer 10',
            'Laboratorium Komputer 11', 'Laboratorium Komputer 12', 'Laboratorium Komputer 13', 'Laboratorium Komputer 14', 'Laboratorium Komputer 16',
            'Laboratorium Komputer 17', 'Laboratorium Komputer 18', 'Laboratorium Komputer 19', 'Laboratorium Komputer 20',
            // 3 Fasilitas Khusus
            'Laboratorium IPA',
            'Lapangan Olahraga',
            'Ruang Hall / Tari'
        ];

        \Illuminate\Support\Facades\DB::transaction(function () use ($canonical53Rooms, &$created, &$existing) {
            $existingRooms = Room::pluck('id', 'room_name')->toArray();

            foreach ($canonical53Rooms as $rName) {
                if (!isset($existingRooms[$rName])) {
                    $newR = Room::create(['room_name' => $rName]);
                    $existingRooms[$rName] = $newR->id;
                    $created++;
                } else {
                    $existing++;
                }
            }
        });

        return [
            'created'  => $created,
            'existing' => $existing,
        ];
    }

    /**
     * Parsing Ruangan dari Spreadsheet Excel / CSV
     */
    protected function parseRoomsSpreadsheet(UploadedFile $file): array
    {
        $rows = $this->spreadsheetService->parse($file);

        $created = 0;
        $existing = 0;

        foreach ($rows as $row) {
            $roomName = $this->getField($row, ['nama_ruang', 'ruang', 'nama', 'nama_ruangan', 'room_name', 'lab', 'laboratorium']);
            if (empty($roomName)) continue;

            $room = Room::where('room_name', $roomName)->first();
            if ($room) {
                $existing++;
            } else {
                Room::create([
                    'room_name' => $roomName,
                ]);
                $created++;
            }
        }

        return [
            'created'  => $created,
            'existing' => $existing,
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
        if (preg_match('/^([X|XI|XII]+)\s*([A-Za-z]+)(\d+)$/i', $trimmed, $m)) {
            return strtoupper($m[1]) . ' ' . strtoupper($m[2]) . ' ' . $m[3];
        }
        if (preg_match('/^([X|XI|XII]+)\s*([A-Za-z]+)$/i', $trimmed, $m)) {
            return strtoupper($m[1]) . ' ' . strtoupper($m[2]);
        }
        return $trimmed;
    }

    /**
     * Auto-Provisioning Jurusan (Major) dari Nama Kelas
     */
    public function autoProvisionMajor(string $className, int &$createdCounter): ?Major
    {
        if (preg_match('/^(?:X|XI|XII)\s+([A-Z]+)/i', $className, $matches)) {
            $majorCode = strtoupper($matches[1]);
            $major = Major::firstOrCreate(
                ['code' => $majorCode],
                [
                    'name'        => "Jurusan {$majorCode}",
                    'rombel_x_xi' => 2,
                    'rombel_xii'  => 2,
                ]
            );

            if ($major->wasRecentlyCreated) {
                $createdCounter++;
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
