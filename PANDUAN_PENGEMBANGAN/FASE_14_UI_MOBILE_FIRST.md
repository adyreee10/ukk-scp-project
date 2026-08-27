# Fase 14 — UI Mobile-First dan Empat Kelompok Tampilan

## Tujuan

Mengganti dashboard bawaan Breeze dengan UI operasional. REST API tetap memakai Sanctum. Untuk Blade di aplikasi yang sama, gunakan session guard Breeze dan panggil service/domain yang sama melalui web controller; jangan menyimpan personal access token di `localStorage` hanya agar Blade bisa memanggil API sendiri.

## Arsitektur UI

```text
Mobile/REST client -> routes/api.php -> auth:sanctum -> controller API -> service
Blade browser      -> routes/web.php -> auth session   -> controller Web -> service
                                                          |
                                                     model/database
```

Dengan ini aturan conflict, lock, sync, dan reminder hanya mempunyai satu implementasi di service.

## Rapikan versi Tailwind

Konfigurasi saat ini adalah Tailwind 3 (`@tailwind base/components/utilities`, `tailwind.config.js`, dan PostCSS). Pertahankan jalur itu untuk menghindari migrasi UI yang tidak perlu, lalu hapus dependency plugin Vite Tailwind 4 yang tidak dipakai:

```bash
npm uninstall -D @tailwindcss/vite
```

Jalankan sendiri setelah memastikan `vite.config.js` memang belum mengimpor plugin tersebut. Jangan mencampur petunjuk Tailwind 4 dengan config Tailwind 3 dalam fase ini.

## Dashboard redirect sesuai role

Buat controller web:

```php
public function __invoke(Request $request)
{
    return match ($request->user()->role?->role_name) {
        'Waka Kurikulum' => redirect()->route('waka.dashboard'),
        'Guru' => redirect()->route('teacher.dashboard'),
        'Wali Kelas', 'Guru BK' => redirect()->route('student-affairs.dashboard'),
        'Siswa', 'Orang Tua' => redirect()->route('family.dashboard'),
        default => abort(403, 'Role akun belum valid.'),
    };
}
```

## Layout mobile-first

Pola dasar Tailwind untuk `resources/views/layouts/app.blade.php`:

```blade
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="mx-auto min-h-screen max-w-7xl pb-20 sm:pb-0">
        <header class="sticky top-0 z-30 border-b bg-white/95 px-4 py-3 backdrop-blur sm:px-6">
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="font-bold text-indigo-700">SCP</a>
                <span class="text-sm">{{ auth()->user()->name }}</span>
            </div>
        </header>

        <main class="space-y-4 p-4 sm:p-6">
            @if (session('success'))
                <div role="status" class="rounded-lg bg-emerald-50 p-3 text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            {{ $slot }}
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t bg-white p-2 sm:static sm:border-0">
            {{-- Link disusun berdasarkan role; target sentuh minimal h-11 --}}
        </nav>
    </div>
</body>
```

## Pola card dan tabel responsif

```blade
<section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="font-semibold">Jadwal Hari Ini</h2>
        @unless ($configuration->is_schedule_locked)
            <a class="inline-flex min-h-11 items-center rounded-lg bg-indigo-600 px-4 text-white"
               href="{{ route('teacher.schedules.create') }}">Tambah</a>
        @endunless
    </div>

    <div class="space-y-3 md:hidden">
        @foreach ($schedules as $schedule)
            <article class="rounded-lg border p-3">
                <p class="font-semibold">{{ $schedule->subject }}</p>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $schedule->day_of_week }},
                    {{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}
                </p>
                <p class="text-sm text-slate-600">
                    {{ $schedule->schoolClass->class_name }} · {{ $schedule->room->room_name }}
                </p>
            </article>
        @endforeach
    </div>

    <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead><tr class="text-left"><th class="p-3">Mapel</th><th class="p-3">Hari/Jam</th><th class="p-3">Kelas</th><th class="p-3">Ruang</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($schedules as $schedule)
                    <tr>
                        <td class="p-3 font-medium">{{ $schedule->subject }}</td>
                        <td class="p-3">{{ $schedule->day_of_week }}, {{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}</td>
                        <td class="p-3">{{ $schedule->schoolClass->class_name }}</td>
                        <td class="p-3">{{ $schedule->room->room_name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
```

Menyembunyikan tombol saat locked hanya proteksi UX. Service Fase 06 tetap menjadi proteksi keamanan sebenarnya.

## Empat kelompok UI

### Waka Kurikulum

- kartu Putaran aktif dan toggle;
- switch lock dengan konfirmasi;
- master user/kelas/ruang;
- daftar guru terlambat jurnal.

### Guru

- reminder merah paling atas;
- jadwal active cycle hari ini;
- CRUD jadwal hanya ketika unlocked;
- form jurnal dengan `accept="image/jpeg,image/png,image/webp"` dan `capture="environment"`;
- form presensi touch-friendly.

### Wali Kelas/Guru BK

- daftar Pending sebagai prioritas;
- preview/download dokumen;
- tombol Approve/Reject dengan konfirmasi;
- filter rekap tanggal/kelas sesuai scope.

### Siswa/Orang Tua

- ringkasan Hadir/Izin/Sakit/Alpa;
- riwayat presensi;
- status pengajuan;
- form upload dokumen;
- Orang Tua memilih anak dari relasi server, bukan mengetik user id bebas.

## Aksesibilitas dan feedback

- label selalu terhubung ke input;
- error tampil di dekat field;
- tombol mempunyai state disabled saat submit;
- warna bukan satu-satunya indikator status;
- dialog konfirmasi untuk lock, reject, delete;
- preview nama/ukuran file sebelum upload;
- empty state menjelaskan aksi berikutnya;
- ukuran target sentuh minimal sekitar 44 px.

## Gerbang selesai

- [ ] Seluruh fitur utama nyaman pada lebar 360 px.
- [ ] Tidak ada tabel yang memaksa halaman overflow tanpa wrapper.
- [ ] Empat kelompok UI tersedia.
- [ ] Aksi schedule hilang saat lock tetapi API juga tetap menolak.
- [ ] Reminder mudah terlihat.
- [ ] Upload memberi feedback sukses/gagal.
- [ ] Navigasi hanya menampilkan fitur role terkait.

Commit:

```bash
git add resources/views app/Http/Controllers routes/web.php
git commit -m "feat: bangun antarmuka mobile-first SCP"
```
