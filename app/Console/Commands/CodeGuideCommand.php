<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Schedule;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;

class CodeGuideCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah console.
     *
     * @var string
     */
    protected $signature = 'scp:guide 
                            {module? : Modul spesifik (overview, auth, waka, guru, wali, siswa, services, all)} 
                            {--search= : Cari penjelasan fungsi berdasarkan kata kunci}';

    /**
     * Deskripsi perintah.
     *
     * @var string
     */
    protected $description = 'Menampilkan panduan arsitektur, fungsi kode, dan logika sistem School Class Progress (SCP) untuk bahan presentasi & pengujian';

    /**
     * Eksekusi perintah console.
     */
    public function handle(): int
    {
        $this->printBanner();

        $search = $this->option('search');
        if ($search) {
            $this->performSearch($search);
            return 0;
        }

        $module = strtolower((string) $this->argument('module'));

        if (!empty($module)) {
            $this->renderModule($module);
            return 0;
        }

        // Mode Interaktif Menu
        while (true) {
            $choice = $this->choice(
                'Pilih modul kode yang ingin Anda pelajari fungsinya:',
                [
                    '0' => 'Ikhtisar Proyek & Statistik Riil Basis Data',
                    '1' => 'Autentikasi, Keamanan & Multi-Role RBAC (Login, Middleware, Auto-Heal)',
                    '2' => 'Modul Waka Kurikulum (Master Akademik, Siswa, Guru, Siklus Blok)',
                    '3' => 'Modul Guru Pengampu (Jadwal, Pengisian Jurnal KBM, Kunci Waktu)',
                    '4' => 'Modul Wali Kelas & Guru BK (Verifikasi Izin & Monitoring Kelas)',
                    '5' => 'Modul Siswa & Orang Tua (Portal Keluarga, Kamera Izin Real-Time)',
                    '6' => 'Mesin Parser & Service (SchoolDocumentParser, JournalReminder)',
                    '7' => 'Tampilkan SEMUA Dokumentasi Sekaligus (Bahan Presentasi Lengkap)',
                    '8' => 'Cari Fungsi / Kata Kunci Tertentu',
                    '9' => 'Keluar',
                ],
                '0'
            );

            if ($choice === 'Keluar' || $choice === '9') {
                $this->info("Semoga sukses presentasi UKK projek SCP hari ini!\n");
                break;
            }

            switch ($choice) {
                case 'Ikhtisar Proyek & Statistik Riil Basis Data':
                case '0':
                    $this->showOverview();
                    break;
                case 'Autentikasi, Keamanan & Multi-Role RBAC (Login, Middleware, Auto-Heal)':
                case '1':
                    $this->showAuth();
                    break;
                case 'Modul Waka Kurikulum (Master Akademik, Siswa, Guru, Siklus Blok)':
                case '2':
                    $this->showWaka();
                    break;
                case 'Modul Guru Pengampu (Jadwal, Pengisian Jurnal KBM, Kunci Waktu)':
                case '3':
                    $this->showTeacher();
                    break;
                case 'Modul Wali Kelas & Guru BK (Verifikasi Izin & Monitoring Kelas)':
                case '4':
                    $this->showStudentAffairs();
                    break;
                case 'Modul Siswa & Orang Tua (Portal Keluarga, Kamera Izin Real-Time)':
                case '5':
                    $this->showFamily();
                    break;
                case 'Mesin Parser & Service (SchoolDocumentParser, JournalReminder)':
                case '6':
                    $this->showServices();
                    break;
                case 'Tampilkan SEMUA Dokumentasi Sekaligus (Bahan Presentasi Lengkap)':
                case '7':
                    $this->renderModule('all');
                    break;
                case 'Cari Fungsi / Kata Kunci Tertentu':
                case '8':
                    $keyword = $this->ask('Masukkan kata kunci yang dicari (misal: "jurnal", "blok", "izin", "auto-heal"):');
                    if ($keyword) {
                        $this->performSearch($keyword);
                    }
                    break;
            }

            if (!$this->confirm('Apakah ingin melihat modul kode lainnya?', true)) {
                $this->info("Semoga sukses presentasi UKK projek SCP hari ini!\n");
                break;
            }
        }

        return 0;
    }

    protected function printBanner(): void
    {
        $this->line("");
        $this->line("<fg=cyan>╔════════════════════════════════════════════════════════════════════════════════╗</>");
        $this->line("<fg=cyan>║</>     <fg=bright-white;options=bold>School Class Progress (SCP) — Panduan Fungsi Kode & Arsitektur</>        <fg=cyan>║</>");
        $this->line("<fg=cyan>║</>     <fg=yellow>SMK Negeri 1 Bantul • Sistem Presensi, Jurnal & Perizinan Terpadu</>       <fg=cyan>║</>");
        $this->line("<fg=cyan>╚════════════════════════════════════════════════════════════════════════════════╝</>");
        $this->line("");
    }

    protected function renderModule(string $module): void
    {
        switch ($module) {
            case 'overview':
                $this->showOverview();
                break;
            case 'auth':
                $this->showAuth();
                break;
            case 'waka':
                $this->showWaka();
                break;
            case 'guru':
            case 'teacher':
                $this->showTeacher();
                break;
            case 'wali':
            case 'bk':
                $this->showStudentAffairs();
                break;
            case 'siswa':
            case 'keluarga':
            case 'family':
                $this->showFamily();
                break;
            case 'services':
            case 'parser':
                $this->showServices();
                break;
            case 'all':
                $this->showOverview();
                $this->showAuth();
                $this->showWaka();
                $this->showTeacher();
                $this->showStudentAffairs();
                $this->showFamily();
                $this->showServices();
                break;
            default:
                $this->error("Modul '{$module}' tidak ditemukan. Pilihan: overview, auth, waka, guru, wali, siswa, services, all.");
                break;
        }
    }

    protected function showOverview(): void
    {
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        $this->line("<fg=bright-white;options=bold>📌 1. IKHTISAR SISTEM & STATISTIK RIIL DATA (LIVE FROM DATABASE)</>");
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        
        $totalSiswa   = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))->count();
        $totalGuru    = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))->count();
        $totalOrtu    = User::whereHas('role', fn($q) => $q->where('role_name', 'Orang Tua'))->count();
        $totalKelas   = SchoolClass::count();
        $totalJadwal  = Schedule::count();
        $totalJurnal  = Journal::count();
        $totalIzin    = LeaveRequest::count();

        $this->table(
            ['Komponen Entitas', 'Jumlah Riil', 'Peran & Keterangan'],
            [
                ['Siswa Terdaftar', $totalSiswa . ' Siswa', 'Memiliki akun NIS & portal kehadiran mandiri'],
                ['Dewan Guru & Staf', $totalGuru . ' Guru/BK', 'Multi-peran: Guru Mapel, Wali Kelas, Guru BK'],
                ['Akun Orang Tua Terkait', $totalOrtu . ' Akun', 'Sinkron otomatis format ortu_[NIS] / sandi p[NIS]'],
                ['Rombongan Belajar (Kelas)', $totalKelas . ' Kelas', 'Kelas tingkat X, XI, XII seluruh jurusan SMK'],
                ['Jadwal KBM Aktif', $totalJadwal . ' Jadwal', 'Mendukung sistem Siklus Blok (Putaran 1 & 2)'],
                ['Jurnal KBM Terekam', $totalJurnal . ' Jurnal', 'Dilengkapi presensi sesi fisik & topik materi'],
                ['Permohonan Izin Siswa', $totalIzin . ' Pengajuan', 'Dilengkapi foto bukti kamera langsung real-time'],
            ]
        );

        $this->line("<fg=green>✔ Keunggulan Arsitektur:</>");
        $this->line("  • Framework: Laravel 11 / 12 dengan arsitektur RESTful & Blade Component");
        $this->line("  • Frontend: Tailwind CSS + Alpine.js (Reaktif, Tanpa reload berat, Mode Terang & Gelap)");
        $this->line("  • Multi-Role RBAC: Mengakomodasi 6 jenis hak akses dalam 1 ekosistem terpadu.\n");
    }

    protected function showAuth(): void
    {
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        $this->line("<fg=bright-white;options=bold>🔐 2. AUTENTIKASI, KEAMANAN & MULTI-ROLE RBAC</>");
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        
        $this->line("<fg=cyan;options=bold>[Berkas Kunci]</> App\\Http\\Requests\\Auth\\LoginRequest.php");
        $this->line("  <fg=bright-white>• Multi-Identifier Login:</> User dapat masuk menggunakan Email, Username, NIS (Siswa), maupun NIP (Guru/Staff).");
        $this->line("  <fg=bright-white>• Rate Limiting & Brute-Force Protection:</> Maksimal 5x percobaan gagal. Pada kegagalan ke-5, server menangguhkan akun selama 60 detik.");
        $this->line("  <fg=bright-white>• Fitur Auto-Heal Orang Tua:</> Otomatis memvalidasi dan menyelaraskan kata sandi orang tua ke format baku p[NIS] (misal: p18694) secara instan tanpa mengunci database.");
        $this->line("  <fg=bright-white>• Proteksi Status Alumni:</> Siswa berstatus 'Lulus' otomatis dinonaktifkan dari sesi login.");
        $this->line("");

        $this->line("<fg=cyan;options=bold>[Berkas Kunci]</> App\\Http\\Middleware\\RoleMiddleware.php");
        $this->line("  <fg=bright-white>• Role Gatekeeper:</> Mengamankan setiap rute web dengan filter role parameter (contoh: role:Waka Kurikulum atau role:Guru,Wali Kelas,Guru BK).");
        $this->line("  <fg=bright-white>• Rejection Otomatis:</> Mencegah pengguna mengakses menu di luar wewenangnya dengan respon 403 Forbidden.");
        $this->line("");

        $this->line("<fg=cyan;options=bold>[Berkas Kunci]</> App\\Http\\Controllers\\Web\\DashboardController.php");
        $this->line("  <fg=bright-white>• Smart Role Redirection:</> Rute tunggal /dashboard yang mengarahkan user secara mulus ke dashboard perannya masing-masing:");
        $this->line("    - Waka Kurikulum       --> /waka/dashboard");
        $this->line("    - Guru, Wali Kelas, BK --> /teacher/dashboard");
        $this->line("    - Siswa, Orang Tua     --> /family/dashboard\n");
    }

    protected function showWaka(): void
    {
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        $this->line("<fg=bright-white;options=bold>🏫 3. MODUL WAKA KURIKULUM (MASTER AKADEMIK & MONITORING)</>");
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        $this->line("<fg=cyan;options=bold>[Berkas Kunci]</> App\\Http\\Controllers\\Web\\WakaController.php");
        
        $this->table(
            ['Fungsi Kode / Method', 'Tujuan & Perilaku Teknis'],
            [
                ['dashboard()', 'Menghitung statistik global: persentase jurnal hari ini, guru terlambat, & status siklus blok.'],
                ['toggleCycle()', 'Mengganti putaran aktif KBM SMK (Putaran 1 <--> Putaran 2) secara instan.'],
                ['toggleLock()', 'Mengunci atau membuka pengisian jadwal KBM oleh para guru pengampu.'],
                ['studentsIndex()', 'Katalog data siswa, filter kelas/tingkat/status, dan modal penaut akun Orang Tua.'],
                ['studentsSyncParents()', 'Sinkronisasi massal seluruh akun Orang Tua dengan sandi p[NIS] & bebas kunci database.'],
                ['journalsIndex()', 'Monitoring kepatuhan jurnal KBM harian, deteksi guru belum mengisi, & cetak dokumen KOP sekolah.'],
                ['academicYearStore()', 'Kelola master Tahun Ajaran & penentuan Semester aktif (Ganjil / Genap).'],
                ['lessonHoursStore()', 'Pengaturan slot jam pelajaran sekolah (Senin - Jumat, 40 menit per JP).'],
            ]
        );
        $this->line("");
    }

    protected function showTeacher(): void
    {
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        $this->line("<fg=bright-white;options=bold>👨‍🏫 4. MODUL GURU PENGAMPU (JADWAL & JURNAL KBM HARIAN)</>");
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        $this->line("<fg=cyan;options=bold>[Berkas Kunci]</> App\\Http\\Controllers\\Web\\TeacherController.php & TeacherJournalController.php");

        $this->line("  <fg=bright-white>• Validasi Jam Mengajar:</> Tombol 'Isi Jurnal' hanya aktif saat jam pelajaran telah dimulai.");
        $this->line("    Fungsi: <fg=green>\$schedule->isTeachingTimeStarted()</>. Jika belum waktunya, tombol terkunci (Disabled: 'Dibuka Pukul XX:XX').");
        $this->line("  <fg=bright-white>• Presensi Sesi Terintegrasi:</> Mengisi kehadiran siswa kelas per jam tatap muka (Hadir, Sakit, Izin, Alpa).");
        $this->line("  <fg=bright-white>• Fleksibilitas Edit Hingga 23:59:</> Setelah jurnal tersimpan, guru tetap dapat memperbarui catatan presensi siswa hingga jam 23:59 malam pada hari yang sama.");
        $this->line("  <fg=bright-white>• Riwayat Jurnal Mandiri:</> Menampilkan log seluruh materi yang pernah disampaikan guru per rombel.\n");
    }

    protected function showStudentAffairs(): void
    {
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        $this->line("<fg=bright-white;options=bold>📋 5. MODUL WALI KELAS & GURU BK (VERIFIKASI IZIN & BINA KELAS)</>");
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        $this->line("<fg=cyan;options=bold>[Rute Kunci]</> /student-affairs/leaves (web.php)");

        $this->line("  <fg=bright-white>• Verifikasi Perizinan Siswa:</> Wali Kelas & Guru BK memiliki wewenang meninjau izin permohonan siswa (Pending).");
        $this->line("  <fg=bright-white>• Pemeriksaan Bukti Foto Fisik:</> Menampilkan foto asli dari kamera surat dokter / dispensasi lomba.");
        $this->line("  <fg=bright-white>• Aksi Setujui / Tolak:</> Jika disetujui, kehadiran siswa otomatis berstatus 'Izin' atau 'Sakit' pada jurnal guru. Jika ditolak, wajib melampirkan catatan alasan penolakan.");
        $this->line("  <fg=bright-white>• Lingkup Akses Perwalian:</> Wali Kelas dibatasi pada kelas binaannya, sedangkan Guru BK dapat memantau seluruh rombel yang diampunya.\n");
    }

    protected function showFamily(): void
    {
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        $this->line("<fg=bright-white;options=bold>👨‍👩‍👧 6. MODUL SISWA & ORANG TUA (PORTAL KELUARGA)</>");
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        $this->line("<fg=cyan;options=bold>[Berkas Kunci]</> resources/views/dashboards/family.blade.php & family/leaves/create.blade.php");

        $this->line("  <fg=bright-white>• Transparansi Akademik:</> Orang tua dapat melihat rekap kehadiran anak secara langsung (Tingkat persentase hadir, total sakit, izin, alpa).");
        $this->line("  <fg=bright-white>• 3 Ragam Permohonan Izin:</>");
        $this->line("    1. Sakit (Seharian penuh dengan surat dokter)");
        $this->line("    2. Izin Keperluan (Acara keluarga / surat dinas)");
        $this->line("    3. Potong Jam Pelajaran (Izin pada jam KBM tertentu, tetap hadir sebelum/sesudahnya)");
        $this->line("  <fg=bright-white>• Kamera Real-Time Anti-Kecurangan:</> Upload dari galeri dinonaktifkan. Surat bukti wajib difoto langsung menggunakan kamera gawai (WebRTC camera stream dengan watermark tanggal).\n");
    }

    protected function showServices(): void
    {
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        $this->line("<fg=bright-white;options=bold>⚙️ 7. SERVICE ENGINE (PARSER DOKUMEN & REMINDER)</>");
        $this->line("<fg=bright-yellow;options=bold>================================================================================</>");
        
        $this->line("<fg=cyan;options=bold>[Service 1]</> App\\Services\\SchoolDocumentParserService.php");
        $this->line("  <fg=bright-white>• Kemampuan:</> Mampu mengekstrak jadwal KBM dan data siswa dari berkas PDF maupun Spreadsheet (Excel).");
        $this->line("  <fg=bright-white>• Python pdfplumber Integration:</> Menggunakan layout 2D tabel presisi tinggi untuk membaca berkas jadwal resmi sekolah.");
        $this->line("  <fg=bright-white>• Auto-Provisioning:</> Saat dokumen siswa diimpor, sistem otomatis membuatkan akun Siswa, akun Orang Tua (sandi p[NIS]), Kelas, dan Jurusan sekaligus.");
        $this->line("");

        $this->line("<fg=cyan;options=bold>[Service 2]</> App\\Services\\JournalReminderService.php");
        $this->line("  <fg=bright-white>• Kemampuan:</> Menghitung dan mendeteksi secara otomatis jadwal guru yang telah lewat jam KBM-nya namun belum diisi jurnalnya.");
        $this->line("  <fg=bright-white>• Dashboard Alert:</> Memberi notifikasi visual kepada guru bersangkutan dan rekapitulasi ke Waka Kurikulum.\n");
    }

    protected function performSearch(string $keyword): void
    {
        $this->line("<fg=yellow>Mencari fungsi dan kode dengan kata kunci: '</><fg=bright-white;options=bold>{$keyword}</><fg=yellow>'...</>\n");

        $database = [
            [
                'komponen' => 'Autentikasi (Login)',
                'file'     => 'app/Http/Requests/Auth/LoginRequest.php',
                'keywords' => 'login nis nip username sandi auto-heal rate limiter lockout alumni',
                'desc'     => 'Validasi multi-identifier, rate limiting 5x, auto-heal sandi Orang Tua ke p[NIS].',
            ],
            [
                'komponen' => 'Otorisasi Role (Middleware)',
                'file'     => 'app/Http/Middleware/RoleMiddleware.php',
                'keywords' => 'role rbac hak akses wewenang alumni lulus 403 forbidden',
                'desc'     => 'Proteksi rute berdasarkan nama role dan blokir akses akun siswa lulus.',
            ],
            [
                'komponen' => 'Siklus Blok & Kunci Jadwal',
                'file'     => 'app/Http/Controllers/Web/WakaController.php',
                'keywords' => 'siklus blok putaran 1 putaran 2 toggle lock ganjil genap',
                'desc'     => 'toggleCycle() dan toggleLock() untuk manajemen putaran blok SMK.',
            ],
            [
                'komponen' => 'Sinkronisasi Akun Orang Tua',
                'file'     => 'app/Http/Controllers/Web/WakaController.php',
                'keywords' => 'sinkronisasi orang tua ortu p[nis] password lock-free wal',
                'desc'     => 'studentsSyncParents() dan /sync-parents-direct menyelaraskan akun & sandi p[NIS].',
            ],
            [
                'komponen' => 'Hapus Jadwal (Semua / Terpilih)',
                'file'     => 'app/Http/Controllers/Web/WakaController.php',
                'keywords' => 'hapus delete jadwal schedulesDeleteAll schedulesDeleteSelected putaran bersihkan',
                'desc'     => 'schedulesDeleteAll() & schedulesDeleteSelected() menghapus jadwal per putaran atau sekaligus dengan proteksi gembok.',
            ],
            [
                'komponen' => 'Hapus Guru (Semua / Terpilih)',
                'file'     => 'app/Http/Controllers/Web/WakaController.php',
                'keywords' => 'hapus delete guru teachersDeleteAll teachersDeleteSelected peran wali kelas guru bk',
                'desc'     => 'teachersDeleteAll() membersihkan akun guru, relasi wali kelas, dan penugasan BK secara aman.',
            ],
            [
                'komponen' => 'Hapus Ruangan (Semua / Satu)',
                'file'     => 'app/Http/Controllers/Web/WakaController.php',
                'keywords' => 'hapus delete ruangan roomsDeleteAll roomsDestroy lab teori',
                'desc'     => 'roomsDeleteAll() menghapus master ruang kelas/laboratorium.',
            ],
            [
                'komponen' => 'Kenaikan Kelas & Kelulusan Massal',
                'file'     => 'app/Http/Controllers/Web/WakaController.php',
                'keywords' => 'kenaikan kelas naik tingkat lulus alumni gradesPromoteMass gradesGraduateMass xii',
                'desc'     => 'Memproses kenaikan tingkat X->XI, XI->XII, dan meluluskan siswa XII menjadi Alumni.',
            ],
            [
                'komponen' => 'Monitoring Jurnal KBM',
                'file'     => 'app/Http/Controllers/Web/WakaController.php',
                'keywords' => 'monitoring jurnal kbm cetak kop sekolah arsip materi guru',
                'desc'     => 'journalsIndex() memantau kepatuhan pengisian jurnal dan cetak laporan resmi.',
            ],
            [
                'komponen' => 'Pengisian Jurnal & Presensi Sesi',
                'file'     => 'app/Http/Controllers/Web/TeacherController.php',
                'keywords' => 'isi jurnal presensi sesi kbm mengajar materi topik hadir sakit izin alpa',
                'desc'     => 'storeJournal() mencatat jurnal KBM dan kehadiran siswa per jam tatap muka.',
            ],
            [
                'komponen' => 'Validasi Jam Mengajar Terbuka',
                'file'     => 'app/Models/Schedule.php',
                'keywords' => 'isTeachingTimeStarted jam mengajar kunci waktu buka pukul disabled',
                'desc'     => 'Mengecek apakah waktu lokal sudah memasuki rentang jam jadwal sebelum boleh isi jurnal.',
            ],
            [
                'komponen' => 'Verifikasi Perizinan Siswa',
                'file'     => 'routes/web.php (student-affairs)',
                'keywords' => 'verifikasi izin wali kelas guru bk approve reject setujui tolak',
                'desc'     => 'Persetujuan izin pending oleh Wali Kelas/BK dan integrasi otomatis ke presensi.',
            ],
            [
                'komponen' => 'Pengajuan Izin & Kamera Real-Time',
                'file'     => 'resources/views/family/leaves/create.blade.php',
                'keywords' => 'kamera bukti izin potong jam sakit keperluan webrtc foto langsung',
                'desc'     => 'Formulir izin 3 jenis dengan pengambilan bukti surat fisik langsung via kamera WebRTC.',
            ],
            [
                'komponen' => 'Parser Dokumen PDF & Excel',
                'file'     => 'app/Services/SchoolDocumentParserService.php',
                'keywords' => 'parser pdf excel spreadsheet import jadwal siswa python pdfplumber',
                'desc'     => 'Ekstraksi dokumen jadwal dan siswa dengan auto-provisioning kelas & akun.',
            ],
            [
                'komponen' => 'Deteksi Keterlambatan Jurnal',
                'file'     => 'app/Services/JournalReminderService.php',
                'keywords' => 'reminder terlambat jurnal notifikasi alert overdue',
                'desc'     => 'overdueFor() mendeteksi jadwal yang terlewat tanpa pengisian jurnal.',
            ],
        ];

        $results = [];
        $lowerKey = strtolower($keyword);

        foreach ($database as $item) {
            if (
                str_contains(strtolower($item['komponen']), $lowerKey) ||
                str_contains(strtolower($item['file']), $lowerKey) ||
                str_contains(strtolower($item['keywords']), $lowerKey) ||
                str_contains(strtolower($item['desc']), $lowerKey)
            ) {
                $results[] = [
                    $item['komponen'],
                    $item['file'],
                    $item['desc'],
                ];
            }
        }

        if (empty($results)) {
            $this->warn("Tidak ditemukan kode atau fungsi yang cocok dengan kata kunci: '{$keyword}'.");
            $this->line("Coba kata kunci lain seperti: 'login', 'jurnal', 'izin', 'ortu', 'blok', 'parser'.\n");
            return;
        }

        $this->table(['Komponen Fitur', 'Lokasi Berkas', 'Penjelasan Fungsi Kode'], $results);
        $this->line("");
    }
}

