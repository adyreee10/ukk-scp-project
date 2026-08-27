# Fase 07 — Jurnal Guru dan Upload Foto

## Tujuan

Membuat jurnal yang terikat jadwal milik guru, tanggal/hari/cycle valid, foto wajib, dan tidak dapat diduplikasi.

## 1. Helper hari

Buat `app/Support/IndonesianDay.php`:

```php
<?php

namespace App\Support;

use Carbon\CarbonInterface;

class IndonesianDay
{
    public static function from(CarbonInterface $date): string
    {
        return match ($date->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        };
    }
}
```

Helper eksplisit lebih aman daripada bergantung locale sistem operasi.

## 2. Ganti method `store()` jurnal

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'schedule_id' => ['required', 'exists:schedules,id'],
        'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        'topic_description' => ['required', 'string', 'max:5000'],
        'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
    ]);

    $schedule = Schedule::where('teacher_id', $request->user()->id)
        ->findOrFail($validated['schedule_id']);

    $date = Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay();
    $configuration = SystemConfiguration::current();

    if ($schedule->cycle_type !== $configuration->active_cycle) {
        throw ValidationException::withMessages([
            'schedule_id' => 'Jadwal bukan bagian dari putaran yang sedang aktif.',
        ]);
    }

    if (IndonesianDay::from($date) !== $schedule->day_of_week) {
        throw ValidationException::withMessages([
            'date' => 'Hari pada tanggal jurnal tidak sesuai dengan hari jadwal.',
        ]);
    }

    if (Journal::where('schedule_id', $schedule->id)->whereDate('date', $date)->exists()) {
        return response()->json(['message' => 'Jurnal untuk jadwal dan tanggal ini sudah ada.'], 409);
    }

    $photoPath = $request->file('photo')->store('journals', 'public');

    try {
        $journal = DB::transaction(function () use ($schedule, $date, $validated, $photoPath) {
            Schedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();

            if (Journal::where('schedule_id', $schedule->id)->whereDate('date', $date)->exists()) {
                abort(409, 'Jurnal untuk jadwal dan tanggal ini sudah ada.');
            }

            return Journal::create([
                'schedule_id' => $schedule->id,
                'date' => $date->toDateString(),
                'topic_description' => $validated['topic_description'],
                'photo_path' => $photoPath,
            ]);
        });
    } catch (Throwable $exception) {
        Storage::disk('public')->delete($photoPath);
        throw $exception;
    }

    return response()->json([
        'message' => 'Jurnal berhasil dibuat.',
        'data' => $journal->load('schedule.schoolClass', 'schedule.room'),
    ], 201);
}
```

Import:

```php
use App\Models\SystemConfiguration;
use App\Support\IndonesianDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;
```

Pada Fase 10, blok create ini akan dipindahkan ke transaction bersama sinkronisasi izin. Jangan menambahkan attendance pada fase ini.

## 3. Index/show tetap scoped

Query index dan show yang sekarang sudah memakai `whereHas(schedule teacher_id)` adalah arah yang benar. Tambahkan filter opsional:

```php
$request->validate([
    'date_from' => ['nullable', 'date_format:Y-m-d'],
    'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
]);
```

Lalu `when()` untuk rentang tanggal dan gunakan pagination, bukan `get()` tanpa batas.

## 4. URL file

Jangan simpan URL penuh di database. Simpan path relatif dan bentuk URL response:

```php
'photo_url' => Storage::disk('public')->url($journal->photo_path),
```

Pastikan kamu sendiri menjalankan sekali:

```bash
php artisan storage:link
```

## Gerbang selesai

- [ ] Foto wajib dan hanya format/ukuran yang diizinkan.
- [ ] Jadwal harus milik guru login.
- [ ] Cycle jurnal harus aktif.
- [ ] Hari tanggal harus sama dengan hari jadwal.
- [ ] Satu schedule hanya mempunyai satu jurnal per tanggal.
- [ ] File dihapus kembali bila insert database gagal.
- [ ] Approval izin belum membuat attendance pada fase ini.

Commit:

```bash
git add app/Support app/Http/Controllers/Api/TeacherJournalController.php
git commit -m "feat: validasi jurnal dan upload bukti foto"
```
