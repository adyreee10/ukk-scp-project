<p align="center">
  <img src="public/images/scp-logo.jpg" alt="SCP Logo" width="100" style="border-radius: 20px;" />
</p>

<h1 align="center">School Class Progress (SCP)</h1>

<p align="center">
  <strong>Sistem Manajemen Presensi, Jurnal Mengajar KBM Real-Time, Jadwal Sistem Blok, dan Perizinan Siswa Terintegrasi</strong>
</p>

<p align="center">
  <em>Dibuat untuk Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak (RPL) Tahun Ajaran 2026/2027 &mdash; Paket 3</em>
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%2F%2013-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel" /></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" /></a>
  <a href="https://flutter.dev"><img src="https://img.shields.io/badge/Flutter-Mobile%20App-02569B?style=for-the-badge&logo=flutter&logoColor=white" alt="Flutter" /></a>
  <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS" /></a>
  <a href="https://sqlite.org"><img src="https://img.shields.io/badge/SQLite-003B57?style=for-the-badge&logo=sqlite&logoColor=white" alt="SQLite" /></a>
</p>

---

## 🌐 Tautan Live Demo
Aplikasi telah di-deploy dan dapat diakses langsung secara daring melalui:
* **Production Web Portal:** [https://ukk-scp-rezky.web.id](https://ukk-scp-rezky.web.id)

---

## 📌 Gambaran Umum Proyek

**School Class Progress (SCP)** adalah platform sekolah terpadu berbasis *hybrid multi-platform* (Web Dashboard & Mobile Native Flutter) yang dirancang untuk mendukung operasional pembelajaran kejuruan berbasis **Teaching Factory** dengan **Siklus Jadwal Blok Dua Mingguan (Putaran 1 & Putaran 2)**.

Sistem ini memfasilitasi monitoring kehadiran, transparansi administrasi guru, validitas KBM berbasis foto kamera *real-time*, serta digitalisasi perizinan siswa yang langsung tersinkronisasi dengan presensi harian.

---

## ⚙️ 5 Aturan Bisnis Inti (Core Business Rules UKK)

Sistem dibangun dengan mematuhi dan mengimplementasikan secara ketat 5 pilar aturan bisnis utama yang diuji dalam UKK RPL Paket 3:

1. **Pencegahan Bentrok Jadwal Otomatis (*Conflict Prevention*):**
   Sistem memvalidasi jadwal baru terhadap jadwal yang ada secara otomatis berdasarkan irisan waktu (`Start_A < End_B AND End_A > Start_B`), kesamaan ruang, dan kelas guna memastikan tidak ada guru atau kelas yang menempati ruang yang sama pada sesi bersamaan.
2. **Kendali Siklus Blok Global (*Active Cycle Toggle*):**
   Waka Kurikulum memiliki kendali global untuk mengaktifkan **Putaran 1** atau **Putaran 2**. Seluruh jadwal mengajar, presensi harian, dan dashboard guru otomatis menyesuaikan dengan putaran yang sedang aktif.
3. **Penguncian Jadwal (*Schedule Lock Mechanism*):**
   Waka Kurikulum dapat mengunci seluruh jadwal pelajaran (*Lock Schedule*). Ketika terkunci, guru dibatasi secara ketat dan tidak dapat menambah, mengubah, atau menghapus jadwal, baik melalui antarmuka web maupun REST API.
4. **Sinkronisasi Izin Otomatis ke Presensi (*Leave Sync on Journal Create*):**
   Pengajuan izin atau sakit siswa yang telah diverifikasi dan disetujui (*Approved*) oleh Wali Kelas atau Guru BK akan secara otomatis tersinkronisasi menjadi status presensi (*Izin* / *Sakit*) saat guru membuka jurnal mengajar pada hari dan sesi terkait.
5. **Peringatan Keterlambatan Pengisian Jurnal (*Dashboard Reminder*):**
   Sistem secara cerdas mendeteksi jadwal KBM yang telah selesai lebih dari 30 menit namun belum dibuatkan jurnal mengajarnya oleh guru pengampu, lalu menampilkan kartu peringatan (*alert reminder*) pada dashboard guru.

---

## 👥 Matriks Hak Akses & Peran Pengguna (Multi-Role)

Sistem mendukung **6 Peran Pengguna** dengan batasan hak akses yang terisolasi dengan *Role Middleware*:

| Peran | Platform | Fitur Utama |
|---|---|---|
| **Waka Kurikulum** | Web Dashboard | Master Data (Tahun Ajaran, Jurusan, Kelas, Ruang, Jam Sesi), Import Berkas Jadwal FET, Pengaturan Siklus Global (Putaran 1/2), Penguncian Jadwal (*Lock*), Monitoring Jurnal Seluruh Guru. |
| **Guru Mata Pelajaran** | Web & Mobile | Melihat Jadwal Mengajar Harian, Mengisi Presensi Siswa, Membuat Jurnal KBM dengan Bukti Foto Real-time Kamera, Dashboard Reminder Keterlambatan. |
| **Wali Kelas** | Web & Mobile | Memantau Rekapitulasi Presensi & Jurnal Kelas Binaan, Verifikasi & Persetujuan/Penolakan Izin Siswa beserta Catatan Penolakan (*Rejection Note*). |
| **Guru BK / Kesiswaan** | Web & Mobile | Monitoring Rekap Presensi Siswa Bermasalah Lintas Kelas, Pemantauan Siswa Sering Alpa/Izin untuk Tindak Lanjut Pembinaan. |
| **Siswa** | Web & Mobile | Melihat Jadwal Pelajaran Kelas, Melihat Riwayat Presensi Sendiri, Mengajukan Izin/Sakit (Harian atau Potong Jam) dengan Lampiran Foto/Surat Dokter. |
| **Orang Tua / Wali** | Web & Mobile | Monitoring Kehadiran Putra/Putri secara Real-time, Riwayat Perizinan dan Pengajuan Izin dari Rumah. |

---

## 🔐 Kredensial Akun untuk Pengujian (Demo Accounts)

Untuk mempermudah pengujian oleh dewan juri dan guru penguji, sistem menyediakan akun siap pakai dengan kata sandi default:

> **Kata Sandi Seluruh Akun Demo:** `password123`

| Peran | Username | Email | Keterangan |
|---|---|---|---|
| **Waka Kurikulum** | `waka` | `waka@sekolah.test` | Akses penuh master data & jadwal |
| **Guru** | `guru` | `guru@sekolah.test` | Guru pengajar demo |
| **Wali Kelas** | `walikelas` | `walikelas@sekolah.test` | Wali Kelas X RPL 1 (Verifikasi Izin) |
| **Guru BK** | `gurubk` | `gurubk@sekolah.test` | Monitoring kesiswaan & pembinaan |
| **Siswa** | `siswa` | `siswa@sekolah.test` | Siswa kelas X RPL 1 |
| **Orang Tua** | `orangtua` | `orangtua@sekolah.test` | Orang tua siswa demo |

---

## 🛠️ Arsitektur Teknologi & Dependensi

* **Backend:** PHP 8.2+, Laravel Framework 11 / 13
* **Autentikasi & API:** Laravel Breeze (Web Session) & Laravel Sanctum (Bearer Token REST API)
* **Basis Data:** SQLite (Server Produksi Ringan & Cepat) / PostgreSQL (Supabase Compatible)
* **Frontend Web:** Laravel Blade, Tailwind CSS, Alpine.js, Lucide Icons, Vite
* **Mobile Client:** Flutter SDK 3.x, Dart, `image_picker` (Real-time camera mode)
* **CI/CD & Deployment:** GitHub Actions Automations via FTPS ke cPanel Cloud Hosting
* **Automated Testing:** Pest PHP & PHPUnit (Feature & Unit Test Coverage)

---

## 🚀 Panduan Setup & Instalasi Lokal

Ikuti langkah-langkah berikut untuk menjalankan proyek di komputer lokal:

### 1. Kloning Repositori
```bash
git clone https://github.com/adyreee10/ukk-scp-project.git
cd ukk-scp-project
```

### 2. Pasang Dependensi PHP & JavaScript
```bash
composer install
npm install
```

### 3. Konfigurasi Lingkungan (.env)
Salin berkas contoh konfigurasi:
```bash
cp .env.example .env
php artisan key:generate
```

Secara default, aplikasi telah dikonfigurasi menggunakan SQLite untuk kemudahan pengujian lokal:
```env
DB_CONNECTION=sqlite
SESSION_DRIVER=file
```

### 4. Migrasi Database & Seeder
Jalankan migrasi struktur tabel dan data master awal:
```bash
php artisan migrate --seed
```

### 5. Hubungkan Direktori Berkas (Storage Link)
```bash
php artisan storage:link
```

### 6. Jalankan Server Pengembangan
Buka dua terminal terpisah:
```bash
# Terminal 1: Backend Laravel
php artisan serve

# Terminal 2: Vite Asset Compiler
npm run dev
```
Akses aplikasi melalui peramban web pada alamat: `http://localhost:8000`

---

## 🧪 Pengujian Otomatis (*Automated Testing*)

Proyek ini dilengkapi dengan skenario pengujian unit dan fitur untuk menjamin stabilitas aturan bisnis:
```bash
# Menjalankan seluruh rangkaian tes
php artisan test

# Menjalankan pengujian spesifik aturan bisnis jadwal
php artisan test --filter=ScheduleBusinessRulesTest

# Menjalankan pengujian sinkronisasi perizinan
php artisan test --filter=LeaveSynchronizationTest
```

---

## 📱 Modul Mobile Client (Flutter)

Aplikasi mobile untuk Guru dan Siswa terletak pada direktori `mobile/`.
```bash
cd mobile
flutter pub get
flutter run
```

Fitur utama aplikasi mobile:
- **Kamera Real-Time Only:** Mengambil foto bukti KBM guru dan surat dokter siswa secara langsung melalui perangkat keras kamera tanpa melalui galeri lama untuk mencegah manipulasi data.
- **Offline Resilient UI:** Antarmuka responsif ramah layar ponsel dengan token persistensi aman via *Flutter Secure Storage*.

---

## 📄 Lisensi & Hak Cipta
Dikembangkan dengan dedikasi penuh untuk **Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak 2026/2027**.  
Hak cipta dilindungi undang-undang &copy; 2026. Bebas digunakan untuk keperluan edukasi dan penilaian sekolah.

