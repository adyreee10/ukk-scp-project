# Fase 01 — Kontrak Teknis dan Keputusan Domain

## Tujuan

Mencegah bug akibat istilah yang berbeda antar migration, controller, query, test, dan UI. Fase ini belum menambah fitur; hasilnya adalah kontrak yang wajib dipakai semua fase berikutnya.

## Keputusan domain

Gunakan nilai berikut secara konsisten:

```text
Role:
- Waka Kurikulum
- Guru
- Wali Kelas
- Guru BK
- Siswa
- Orang Tua

Cycle:
- Putaran 1
- Putaran 2

Leave type:
- Izin
- Sakit

Approval:
- Pending
- Approved
- Rejected

Attendance:
- Hadir
- Izin
- Sakit
- Alpa
```

SRS menyebut enam role. Empat kelompok UI tetap dapat dibuat dengan menggabungkan tampilan Wali Kelas/Guru BK dan Siswa/Orang Tua, tetapi role backend tidak digabung.

## Kontrak HTTP

| Kondisi | Status |
|---|---:|
| GET/PUT sukses | 200 |
| POST sukses | 201 |
| DELETE sukses tanpa body | 204 |
| Input tidak valid | 422 |
| Token tidak ada/tidak sah | 401 |
| Role/ownership salah atau jadwal locked | 403 |
| Data tidak ditemukan dalam scope user | 404 |
| Konflik state atau duplikasi resource | 409 |
| Konflik overlap jadwal yang dilaporkan sebagai validasi domain | 422 |

Format sukses:

```json
{
  "message": "Jadwal berhasil dibuat.",
  "data": {}
}
```

Format error bisnis:

```json
{
  "message": "Jadwal bentrok dengan penggunaan kelas atau ruang yang sudah ada.",
  "errors": {
    "schedule": ["Jadwal bentrok dengan ..."]
  }
}
```

Error validasi Laravel tetap memakai format standar 422. Jangan menangkap `ValidationException` hanya untuk membungkusnya kembali.

## Kontrak autentikasi Sanctum

Client token selalu mengirim:

```http
Authorization: Bearer {plain_text_token}
Accept: application/json
```

Aturan token:

- login membuat token dengan nama perangkat dari request;
- logout menghapus token yang sedang dipakai, bukan semua token;
- endpoint privat selalu berada di `auth:sanctum`;
- role middleware tidak menggantikan ownership check;
- token tidak pernah ditulis ke log atau Git.

## Kontrak tanggal dan waktu

- tanggal request: `Y-m-d`, contoh `2026-08-25`;
- waktu jadwal: `H:i`, contoh `07:30`;
- timezone aplikasi: `Asia/Jakarta`;
- `day_of_week` memakai Bahasa Indonesia;
- jurnal hanya boleh dibuat untuk tanggal yang harinya sama dengan jadwal;
- reminder memakai `now()` dalam timezone aplikasi dan batas ketat `now > end_time + 30 menit`.

Tambahkan atau pastikan `.env` milikmu berisi:

```dotenv
APP_TIMEZONE=Asia/Jakarta
```

Lalu pada `config/app.php`, pastikan:

```php
'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
```

## Kontrak file

Keputusan implementasi yang aman untuk UKK:

```text
Foto jurnal:
- wajib
- image
- mimes: jpg,jpeg,png,webp
- maksimum 2 MB
- disk: public
- folder: journals

Dokumen izin:
- wajib
- mimes: pdf,jpg,jpeg,png
- maksimum 4 MB
- disk: public (cukup untuk demo UKK)
- folder: leave-documents
```

Untuk produksi nyata, dokumen izin sebaiknya private dan diakses lewat endpoint berotorisasi. Panduan ini memakai public disk agar implementasi UKK tetap sederhana.

## Kontrak keamanan data

- Guru hanya mengubah jadwal dan jurnal miliknya.
- Siswa hanya membaca data dirinya/kelasnya dan mengajukan untuk dirinya.
- Orang tua hanya mengajukan/membaca data anak yang memiliki `parent_id` sama dengan id orang tua.
- Wali Kelas hanya memverifikasi siswa pada kelas yang menjadi tanggung jawabnya.
- Guru BK boleh memverifikasi lintas kelas sesuai keputusan implementasi ini.
- Waka adalah satu-satunya role yang mengubah master data, cycle, dan lock.

Karena schema awal belum mempunyai penanda kelas perwalian bagi Wali Kelas, Fase 02 menambah `homeroom_class_id` pada users.

## Kontrak transaksi

Gunakan database transaction untuk:

- membuat jurnal dan menyinkronkan approved leave;
- menyimpan batch presensi;
- approve/reject leave jika ada side effect tambahan;
- operasi yang menyimpan file dan record harus menghapus file kembali jika penyimpanan database gagal.

## Gerbang selesai

- [ ] Semua anggota proyek memakai istilah di dokumen ini.
- [ ] Tidak ada lagi kontrak `Kesiswaan`, `Waka`, `ganjil/genap`, atau `Disetujui` pada kode final.
- [ ] Keputusan file, timezone, role, dan status dipahami sebelum migration diperbaiki.

Commit yang disarankan setelah menerapkan fase ini nanti:

```bash
git commit -m "docs: tetapkan kontrak domain dan REST API SCP"
```
