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

