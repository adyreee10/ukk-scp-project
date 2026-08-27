# Peta Panduan Pengembangan SCP

Dokumen ini adalah jalur pengerjaan **School Class Progress (SCP)** berdasarkan kondisi repository pada 25 Agustus 2026 dan `SRS_School_Class_Progress_SCP.md`.

## Aturan penggunaan

1. Baca `FASE_00` terlebih dahulu untuk memahami posisi proyek saat ini.
2. Kerjakan fase secara berurutan. Jangan melompat karena setiap fase memiliki dependensi.
3. Salin kode hanya dari fase yang sedang dikerjakan.
4. Setelah satu fase selesai, jalankan checklist dan test yang ditulis pada fase tersebut.
5. Commit setelah checklist fase lulus. Contoh pesan commit tersedia di setiap fase.
6. Perintah terminal di dokumen adalah perintah yang **kamu jalankan sendiri**. Tidak ada perintah aplikasi, migration, test, atau formatter yang dijalankan saat panduan ini dibuat.

## Urutan fase

| Fase | Fokus | Hasil akhir |
|---:|---|---|
| 00 | Audit proyek | Mengetahui progres nyata, gap, dan risiko |
| 01 | Kontrak teknis | Menetapkan istilah, status, respons API, dan aturan implementasi |
| 02 | Database | Membetulkan schema, constraint, dan data awal |
| 03 | Model domain | Relasi, cast, konstanta, dan query scope konsisten |
| 04 | Sanctum & profil API | Login/logout/me/update profile aman untuk semua role |
| 05 | Waka & master data | CRUD master, active cycle, dan schedule lock |
| 06 | Jadwal guru | CRUD, ownership, conflict prevention, lock, active cycle |
| 07 | Jurnal guru | Upload foto, validasi jadwal/tanggal, jurnal unik |
| 08 | Pengajuan izin | Siswa dan orang tua upload dokumen dengan ownership benar |
| 09 | Verifikasi izin | Wali Kelas/Guru BK memproses izin sesuai cakupan |
| 10 | Presensi & sinkronisasi | Input massal dan sync izin saat jurnal dibuka |
| 11 | Rekap & reminder | Rekap kehadiran dan keterlambatan jurnal >30 menit |
| 12 | Routing & kontrak API | Endpoint final konsisten dan terlindungi |
| 13 | Automated testing | Acceptance criteria SRS dibuktikan dengan Pest |
| 14 | UI mobile-first | Empat kelompok UI dan proteksi aksi di tampilan |
| 15 | Hardening & UKK | Audit akhir, keamanan, demo, dan checklist kelulusan |

## Titik mulai yang direkomendasikan

Proyek berada di penghujung **fondasi backend / awal implementasi API**, kira-kira setelah autentikasi dan sebagian endpoint baca. Mulai pengerjaan dari **FASE 01**, bukan meneruskan controller secara acak.

## Definisi selesai

Sebuah fase belum selesai hanya karena endpoint bisa memberi respons. Fase dianggap selesai bila:

- validasi sukses dan gagal sudah benar;
- autentikasi, role, dan ownership diuji;
- aturan bisnis tidak dapat dilewati lewat request langsung;
- response code sesuai kontrak;
- test fase lulus;
- tidak merusak test fase sebelumnya.

