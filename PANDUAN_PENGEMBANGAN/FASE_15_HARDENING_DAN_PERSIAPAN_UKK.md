# Fase 15 — Hardening, Audit Akhir, dan Persiapan UKK

## Tujuan

Mengubah proyek yang “fiturnya ada” menjadi proyek yang dapat dibuktikan benar saat pengujian dan presentasi.

## 1. Audit keamanan

- pastikan `.env`, token, password nyata, dan dokumen asli tidak masuk Git;
- `APP_DEBUG=false` pada environment demo/produksi;
- rate limit login dan reset password;
- jangan mengembalikan stack trace atau reset token;
- semua upload memakai validasi server;
- semua query detail memakai ownership/scope sebelum `findOrFail`;
- semua mutation role-sensitive mempunyai middleware dan check domain;
- pertimbangkan token expiration untuk client yang bukan first-party.

## 2. Audit data

Tambahkan validasi agar siswa tidak mempunyai dua leave Pending/Approved yang overlap jika kebijakan sekolah menghendaki. Query overlap tanggal:

```php
LeaveRequest::where('student_id', $student->id)
    ->whereIn('status_approval', ['Pending', 'Approved'])
    ->whereDate('start_date', '<=', $validated['end_date'])
    ->whereDate('end_date', '>=', $validated['start_date'])
    ->exists();
```

Jika true, kembalikan 409. Ini keputusan hardening, bukan aturan eksplisit PDF.

## 3. Audit performa

- gunakan eager loading pada semua list;
- paginate data histori;
- jangan mengirim model User penuh dengan password/metadata tersembunyi;
- gunakan index yang dibuat Fase 02;
- periksa N+1 query pada dashboard dan recap;
- jangan memuat file binary ke JSON.

## 4. Urutan verifikasi lokal yang kamu jalankan

```bash
composer install
npm install
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan storage:link
php artisan route:list --path=api
php artisan test
npm run build
```

`migrate:fresh` hanya untuk database disposable yang sudah dibackup. Untuk database berdata, gunakan migration koreksi forward-only.

## 5. Skenario demo UKK

Siapkan data yang membuat lima aturan mudah ditunjukkan:

1. Login Guru, buat jadwal valid.
2. Coba jadwal overlap pada ruang/kelas sama; tunjukkan 422 dan pesan bentrok.
3. Login Waka, ubah Putaran; tunjukkan jadwal operasional berubah.
4. Waka lock; tunjukkan tombol hilang dan request API langsung ditolak 403.
5. Siswa/Orang Tua upload izin Sakit; status Pending.
6. Wali Kelas approve; tunjukkan attendance masih belum ada.
7. Guru membuat jurnal pada tanggal izin; tunjukkan attendance Sakit baru terbentuk.
8. Bekukan/siapkan jadwal yang lewat >30 menit tanpa jurnal; buka dashboard dan tunjukkan reminder.
9. Buat jurnal; reload dashboard dan tunjukkan reminder hilang.

## 6. Checklist SRS final

### Autentikasi

- [ ] Enam role login/logout.
- [ ] Profil REST dan web bekerja.
- [ ] RBAC dan ownership diuji.

### Lima logika kelulusan

- [ ] Conflict prevention memakai rumus overlap yang benar.
- [ ] Active cycle memengaruhi semua data operasional.
- [ ] Lock melindungi UI dan backend.
- [ ] Approved leave baru sync ketika jurnal dibuat.
- [ ] Reminder dihitung saat dashboard load, tanpa cron.

### File dan data

- [ ] Foto jurnal wajib dan tervalidasi.
- [ ] Dokumen izin wajib dan tervalidasi.
- [ ] Constraint duplikasi jurnal/presensi aktif.
- [ ] Seeder menghasilkan data demo konsisten.

### Kualitas

- [ ] Seluruh test lulus.
- [ ] Build frontend lulus.
- [ ] Tidak ada route debug/test terbuka.
- [ ] Tidak ada secret di Git.
- [ ] Commit kecil dan deskriptif.
- [ ] README proyek menjelaskan setup dan akun demo.

## 7. Ganti README bawaan

Isi minimum `README.md` proyek:

```markdown
# School Class Progress (SCP)

Aplikasi presensi, jurnal mengajar, jadwal blok, perizinan siswa, dan reminder jurnal untuk UKK RPL 2026/2027.

## Stack

- PHP 8.3 dan Laravel 13
- Laravel Sanctum untuk REST API
- Blade, Alpine.js, Tailwind CSS, dan Vite
- Pest untuk automated testing

## Setup lokal

1. Salin `.env.example` menjadi `.env` dan isi koneksi database.
2. Jalankan `composer install` dan `npm install`.
3. Jalankan `php artisan key:generate`.
4. Jalankan `php artisan migrate --seed`.
5. Jalankan `php artisan storage:link`.
6. Jalankan server Laravel dan Vite.

## Pengujian

Jalankan `php artisan test` dan `npm run build` sebelum demo.

## Role

Waka Kurikulum, Guru, Wali Kelas, Guru BK, Siswa, dan Orang Tua.

## Aturan bisnis utama

Conflict prevention, active cycle Putaran 1/2, schedule lock, sinkronisasi izin saat jurnal dibuat, dan reminder jurnal setelah 30 menit.

## Dokumentasi

SRS berada di `SRS_School_Class_Progress_SCP.md`. Panduan bertahap berada di `PANDUAN_PENGEMBANGAN/`.
```

Jangan menaruh password produksi di README. Akun demo boleh ditulis hanya bila passwordnya khusus data dummy lokal.

## 8. Batas jaminan bug

Panduan ini diaudit secara statis terhadap struktur repository dan SRS. Tidak ada pihak yang dapat menjamin “nol bug” tanpa menjalankan kode pada environment, database driver, dan dependency aktual. Karena kamu meminta saya tidak mengeksekusi kode, jaminan yang realistis adalah:

- kontrak antar fase dibuat konsisten;
- lima aturan bisnis ditempatkan di backend/service;
- constraint dan ownership disebut eksplisit;
- test untuk membuktikan perilaku disediakan;
- proyek baru dinyatakan siap setelah seluruh gerbang dan test kamu jalankan sendiri.

## Commit penutup

```bash
git add .
git commit -m "chore: harden dan siapkan SCP untuk UKK"
```
