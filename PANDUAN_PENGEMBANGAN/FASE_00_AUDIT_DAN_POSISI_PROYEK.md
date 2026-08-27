# Fase 00 — Audit dan Posisi Proyek Saat Ini

## Kesimpulan utama

Proyek saat ini berada pada tahap **fondasi database dan REST API awal**, belum masuk tahap implementasi lengkap aturan bisnis UKK. Estimasi berbasis requirement, bukan jumlah file:

- fondasi proyek Laravel/Breeze: sekitar 90%;
- schema dan model awal: sekitar 75%;
- autentikasi REST Sanctum: sekitar 70%;
- API operasional dasar: sekitar 30%;
- lima aturan bisnis kunci kelulusan: sekitar 5%;
- UI khusus SCP: sekitar 5%;
- automated test fitur SCP: 0%;
- kesiapan produk terhadap seluruh SRS: sekitar **30–35%**.

Angka ini bukan skor UKK. Ini indikator kemajuan implementasi terhadap SRS.

## Bukti Sanctum

REST API sudah menggunakan Laravel Sanctum karena:

- `laravel/sanctum` tercantum pada `composer.json`;
- model `User` memakai trait `HasApiTokens`;
- migration `personal_access_tokens` tersedia;
- login membuat token melalui `createToken(...)->plainTextToken`;
- endpoint privat memakai middleware `auth:sanctum`;
- logout menghapus current access token.

Jadi Sanctum **tidak perlu dipasang ulang**. Yang perlu dilakukan adalah memperkuat kontrak token, profil API, pembatasan role, rate limiting, dan test.

## Yang sudah ada

### Fondasi

- Laravel 13, PHP 8.3, Breeze Blade, Tailwind, Vite, Pest.
- Git aktif dan commit mencerminkan perkembangan fitur.
- SRS sangat lengkap dan dapat dijadikan acceptance criteria.

### Database dan model

Sudah ada entitas `Role`, `User`, `SchoolClass`, `Room`, `Schedule`, `Journal`, `Attendance`, `LeaveRequest`, dan `SystemConfiguration`. Foreign key dasar juga sudah dibuat.

### API

- login, register, forgot/reset password, me, logout;
- guru: lihat jadwal, daftar/detail/buat jurnal, lihat presensi;
- siswa: lihat jadwal/jurnal/presensi, daftar/detail/buat pengajuan izin;
- kesiswaan: daftar/detail, approve, reject pengajuan.

## Yang baru setengah jadi atau bermasalah

| Area | Kondisi aktual | Dampak |
|---|---|---|
| Role | Hanya 5 role; Wali Kelas dan Guru BK digabung menjadi `Kesiswaan` | Tidak sama dengan 6 role SRS |
| Seeder utama | `DatabaseSeeder` hanya memanggil role, kelas, ruang, Waka | Seeder lain tidak ikut; urutan dependency belum lengkap |
| Nama tabel kelas | Implementasi memakai `school_classes`, blueprint menyebut `classes` | Perlu diselaraskan atau didokumentasikan eksplisit |
| Konfigurasi sistem | Tabel/model ada, tetapi tidak ada seed, endpoint, atau pemakaian | Active cycle dan lock belum bekerja |
| Jadwal guru | Hanya endpoint GET | Guru belum bisa input/edit/hapus |
| Konflik jadwal | Belum ada | BR-001 belum terpenuhi |
| Lock schedule | Belum ada pengecekan backend | BR-003 belum terpenuhi |
| Active cycle | Jadwal guru/siswa tidak difilter | BR-002 belum terpenuhi |
| Jurnal | Bisa dibuat dan upload foto opsional | SRS meminta bukti foto; belum cek hari, cycle, duplikasi, dan sinkronisasi |
| Presensi | Hanya endpoint GET | Guru belum bisa input/update presensi |
| Pengajuan izin | `document_path` diterima sebagai string | Belum benar-benar upload/validasi file |
| Jenis izin | Tidak ada field eksplisit `Izin`/`Sakit` | Sync tidak dapat menentukan status secara andal |
| Orang tua | Tidak punya route pengajuan | FR-STD belum lengkap |
| Verifikasi | Semua data terlihat oleh role gabungan | Belum dibatasi kelas perwalian/otoritas |
| Sinkronisasi izin | Belum ada | BR-004 belum terpenuhi |
| Reminder | Belum ada | BR-005 belum terpenuhi |
| Waka | Belum ada API master/config/rekap | Seluruh fitur Waka belum ada |
| UI | Dashboard masih bawaan Breeze | Belum ada empat kelompok UI SCP |
| Test | Hanya test bawaan Breeze | Tidak ada bukti acceptance criteria SCP |

## Bug dan risiko konkret yang ditemukan

1. `DatabaseSeeder` tidak memanggil `GuruSeeder`, `OrangTuaSeeder`, `SiswaSeeder`, dan seeder transaksional. Menjalankan seed standar tidak menghasilkan data demo lengkap.
2. `ScheduleSeeder` membutuhkan guru, tetapi guru tidak dibuat oleh `DatabaseSeeder`.
3. `SiswaSeeder` membutuhkan orang tua; urutan pemanggilan wajib `OrangTuaSeeder` sebelum `SiswaSeeder`.
4. `AttendanceSeeder`, `JournalSeeder`, dan `LeaveRequestSeeder` mengakses `->id` tanpa memastikan record dependency tersedia.
5. Endpoint registrasi publik selalu membuat siswa tanpa kelas. Ini dapat menghasilkan akun siswa yang tidak operasional dan merupakan keputusan keamanan yang perlu dikunci.
6. Endpoint forgot password mengembalikan reset token ke client. Cocok hanya untuk demo lokal; tidak aman untuk produksi.
7. `TeacherJournalController::store()` membuat foto opsional, padahal bukti foto diwajibkan SRS.
8. Tidak ada unique constraint jurnal per `schedule_id + date`; jurnal ganda mungkin terjadi.
9. Tidak ada unique constraint presensi per `journal_id + student_id`; data presensi ganda mungkin terjadi.
10. Pengajuan izin tidak mempunyai `leave_type`; membaca kata dari `reason` akan rapuh.
11. Status database memakai `Disetujui`, sementara SRS dan pseudocode memakai `Approved`. Tanpa satu kontrak, query sinkronisasi mudah gagal.
12. Query jadwal siswa/guru mengembalikan dua putaran sekaligus karena tidak membaca `active_cycle`.
13. Verifikator dapat melihat dan memproses seluruh pengajuan tanpa scope kelas.
14. Tidak ada ownership/role check yang membuktikan `teacher_id` benar-benar role Guru, `student_id` role Siswa, dan relasi orang tua-anak sah.
15. API update profile untuk token client belum ada; profile yang ada hanya flow web/session.
16. Response menampilkan model relasi secara langsung sehingga field response dapat berubah tanpa kontrak Resource.
17. `README.md` masih README bawaan Laravel dan belum menjelaskan SCP, setup, akun demo, atau alur UKK.
18. Stack CSS aktif memakai Tailwind 3 melalui PostCSS, tetapi `@tailwindcss/vite` versi 4 juga tercantum dan tidak digunakan oleh `vite.config.js`; dependency ini sebaiknya dirapikan agar tidak membingungkan.
19. Registrasi bawaan Breeze web masih tersedia selain registrasi API; untuk sistem akun sekolah, keduanya harus dinonaktifkan atau diberi alasan bisnis yang jelas.

## Pemetaan lima aturan bisnis kunci

| Aturan | Status | Bukti |
|---|---|---|
| BR-001 Conflict Prevention | Belum | Tidak ada POST/PUT schedule dan query overlap |
| BR-002 Cycle Toggle | Belum | Hanya tabel/model konfigurasi |
| BR-003 Lock Schedule | Belum | Tidak ada endpoint Waka atau guard mutation |
| BR-004 Leave Synchronization | Belum | Approval hanya mengubah status |
| BR-005 Journal Reminder | Belum | Tidak ada query deadline/dashboard |

## Matriks requirement per modul

| Requirement SRS | Status aktual | Catatan audit |
|---|---|---|
| FR-AUTH-001 Login | Sebagian besar ada | Bearer token Sanctum bekerja secara struktur; belum ada test API |
| FR-AUTH-002 RBAC | Sebagian | Middleware role ada, tetapi vocabulary role tidak sesuai enam role |
| FR-AUTH-003 Logout | Ada | Menghapus current access token |
| FR-AUTH-004 Update profil | Sebagian | Ada untuk session web, belum ada endpoint token API |
| FR-WAKA-001 Master data | Belum | Baru migration/seeder, tidak ada CRUD Waka |
| FR-WAKA-002–003 Active cycle | Belum | Tabel/model ada, tidak pernah dibaca oleh query operasional |
| FR-WAKA-004–005 Lock | Belum | Tabel/model ada, tidak ada aksi Waka atau guard backend |
| FR-WAKA-006 Rekap terlambat | Belum | Tidak ada dashboard query |
| FR-SCH-001–002 Input/cycle jadwal | Sebagian | Schema ada; API guru hanya GET |
| FR-SCH-003–005 Conflict/lock | Belum | Tidak ada mutation dan validasi overlap |
| FR-JRN-001–002 Jurnal | Sebagian besar | Create/list/detail dan ownership schedule ada |
| FR-JRN-003–004 Foto | Sebagian | Upload dan mimes ada, tetapi masih nullable |
| FR-ATT-001 Input presensi | Belum | Hanya read endpoint |
| FR-ATT-002–004 Data presensi | Sebagian | Schema, model, seeder, read tersedia |
| FR-STD-001 Portal Siswa/Orang Tua | Sebagian | Siswa ada; route Orang Tua belum ada |
| FR-STD-002 Rekap | Sebagian | Siswa mendapat list mentah, Orang Tua belum |
| FR-STD-003–008 Pengajuan/upload | Sebagian | Siswa dapat create, tetapi path masih string dan jenis izin tidak eksplisit |
| FR-KES-001 Rekap | Belum | Tidak ada rekap khusus Wali/BK |
| FR-KES-002–004 Verifikasi | Sebagian | List/detail/approve/reject ada, tetapi scope dan role salah |
| FR-KES-005 Timing sync | Sebagian | Approval belum membuat attendance (benar), tetapi tahap sync belum ada |
| FR-ALT-001–005 Reminder | Belum | Tidak ada implementasi |
| UI-001–006 | Belum | Dashboard dan navigasi masih bawaan Breeze |
| API-001 Auth/RBAC | Sebagian | Sanctum + role middleware ada |
| API-002–005 aturan backend | Belum | Lock, conflict, cycle, dan sync belum ada |
| AC-001–018 | Belum terbukti | Tidak ada Feature test domain SCP |

## Posisi fase

Jika memakai urutan rekomendasi SRS, proyek telah mengerjakan sebagian langkah 1–4 dan potongan langkah 10–13, tetapi melompati langkah 5–9. Karena dependency terlewati, strategi paling aman adalah kembali ke kontrak data dan menyelesaikan urutan mulai Fase 01.

## Larangan sementara

Sebelum Fase 06 selesai, jangan:

- menambah UI jadwal final;
- menganggap endpoint jurnal sudah final;
- mengisi attendance otomatis ketika approval;
- membuat reminder dengan cron;
- mengandalkan tombol tersembunyi sebagai schedule lock;
- menulis fitur baru berdasarkan string role `Kesiswaan`.

## Checklist audit

- [x] SRS dibaca dan requirement inti dipetakan.
- [x] Sanctum terkonfirmasi terpasang dan digunakan.
- [x] Route, controller API, model, migration, seeder, view, test, dan riwayat Git diperiksa statis.
- [x] Tidak ada source code aplikasi yang diubah.
- [x] Tidak ada aplikasi, migration, seeder, formatter, atau test yang dijalankan.
