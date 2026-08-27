# Fase 10 — Presensi dan Sinkronisasi Izin

## Tujuan

Menyelesaikan BR-004: Approved leave tidak membuat attendance saat approval, tetapi disinkronkan ketika jurnal dibuat pada tanggal terkait.

## 1. Service presensi

Buat `app/Services/AttendanceService.php`:

```php
<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function syncApprovedLeaves(Journal $journal): void
    {
        $journal->loadMissing('schedule');
        $date = $journal->date->toDateString();

        $studentIds = User::query()
            ->where('class_id', $journal->schedule->class_id)
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
            ->pluck('id');

        $leaves = LeaveRequest::query()
            ->approvedOn($date)
            ->whereIn('student_id', $studentIds)
            ->latest('updated_at')
            ->get()
            ->unique('student_id');

        foreach ($leaves as $leave) {
            Attendance::updateOrCreate(
                ['journal_id' => $journal->id, 'student_id' => $leave->student_id],
                ['status' => $leave->leave_type, 'date' => $date],
            );
        }
    }

    public function saveBatch(Journal $journal, array $rows): Collection
    {
        return DB::transaction(function () use ($journal, $rows) {
            $journal->loadMissing('schedule');
            $studentIds = collect($rows)->pluck('student_id');

            $validCount = User::query()
                ->whereIn('id', $studentIds)
                ->where('class_id', $journal->schedule->class_id)
                ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
                ->count();

            if ($validCount !== $studentIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'attendances' => 'Semua student_id harus siswa dari kelas jadwal.',
                ]);
            }

            $date = $journal->date->toDateString();
            $approved = LeaveRequest::query()
                ->approvedOn($date)
                ->whereIn('student_id', $studentIds)
                ->latest('updated_at')
                ->get()
                ->unique('student_id')
                ->keyBy('student_id');

            foreach ($rows as $row) {
                $forcedStatus = $approved->get($row['student_id'])?->leave_type;

                Attendance::updateOrCreate(
                    ['journal_id' => $journal->id, 'student_id' => $row['student_id']],
                    ['status' => $forcedStatus ?? $row['status'], 'date' => $date],
                );
            }

            return $journal->attendances()->with('student:id,name')->get();
        });
    }
}
```

Jika terdapat dua approved leave overlap untuk siswa/tanggal sama, record terbaru menjadi sumber status. Lebih baik Waka/Kesiswaan mencegah overlap pada fase hardening, tetapi service ini tetap deterministik.

## 2. Sinkronkan saat jurnal dibuat

Inject `AttendanceService` ke `TeacherJournalController`:

```php
public function __construct(private readonly AttendanceService $attendanceService) {}
```

Setelah foto disimpan dan seluruh validasi Fase 07 lulus, ganti blok create menjadi:

```php
$photoPath = $request->file('photo')->store('journals', 'public');

try {
    $journal = DB::transaction(function () use ($schedule, $date, $validated, $photoPath) {
        Schedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();

        if (Journal::where('schedule_id', $schedule->id)->whereDate('date', $date)->exists()) {
            abort(409, 'Jurnal untuk jadwal dan tanggal ini sudah ada.');
        }

        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'date' => $date->toDateString(),
            'topic_description' => $validated['topic_description'],
            'photo_path' => $photoPath,
        ]);

        $this->attendanceService->syncApprovedLeaves($journal);

        return $journal;
    });
} catch (Throwable $exception) {
    Storage::disk('public')->delete($photoPath);
    throw $exception;
}
```

Import `DB`, `AttendanceService`, dan pastikan `Schedule`/`Journal` tetap di-import. Transaction dan row lock memastikan jurnal unik serta attendance sinkron berhasil bersama.

## 3. Input presensi massal

Tambahkan method berikut pada `TeacherAttendanceController`:

```php
public function store(Request $request, AttendanceService $service)
{
    $validated = $request->validate([
        'journal_id' => ['required', 'exists:journals,id'],
        'attendances' => ['required', 'array', 'min:1'],
        'attendances.*.student_id' => ['required', 'integer', 'distinct', 'exists:users,id'],
        'attendances.*.status' => ['required', Rule::in(Attendance::STATUSES)],
    ]);

    $journal = Journal::whereHas('schedule', fn ($query) =>
        $query->where('teacher_id', $request->user()->id)
    )->findOrFail($validated['journal_id']);

    $data = $service->saveBatch($journal, $validated['attendances']);

    return response()->json([
        'message' => 'Presensi berhasil disimpan.',
        'data' => $data,
    ]);
}
```

Import `AttendanceService`, `Journal`, dan `Rule`.

## 4. Urutan pembuktian bisnis

```text
1. Siswa membuat leave -> Pending -> attendance 0
2. Kesiswaan approve -> Approved -> attendance tetap 0
3. Guru membuat jurnal untuk kelas/tanggal tersebut
4. Transaction membuat journal
5. Service mencari leave Approved yang mencakup tanggal
6. Attendance Izin/Sakit dibuat untuk journal tersebut
7. Input batch guru tidak boleh menimpa status approved leave
```

## Gerbang selesai

- [ ] Approval saja tidak membuat attendance.
- [ ] Leave di luar rentang tanggal tidak disinkronkan.
- [ ] Leave siswa kelas lain tidak disinkronkan.
- [ ] Pending/Rejected tidak disinkronkan.
- [ ] Approved Izin menjadi Izin; Approved Sakit menjadi Sakit.
- [ ] Presensi siswa di luar kelas ditolak.
- [ ] Duplikasi journal/student dicegah.
- [ ] Kegagalan sync membatalkan jurnal dan menghapus foto.

Commit:

```bash
git add app/Services app/Http/Controllers/Api
git commit -m "feat: sinkronkan izin dan presensi saat jurnal dibuka"
```
