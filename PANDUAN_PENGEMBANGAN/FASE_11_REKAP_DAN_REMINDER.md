# Fase 11 — Rekap Kehadiran dan Reminder Jurnal

## Tujuan

Menyelesaikan rekap lintas role dan BR-005 tanpa cron job.

## 1. Query reminder reusable

Buat `app/Services/JournalReminderService.php`:

```php
<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Support\IndonesianDay;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class JournalReminderService
{
    public function overdueFor(?User $teacher = null): Collection
    {
        $now = now();
        $today = $now->toDateString();
        $day = IndonesianDay::from($now);

        if ($day === 'Minggu') {
            return collect();
        }

        return Schedule::query()
            ->with(['teacher:id,name', 'schoolClass:id,class_name', 'room:id,room_name'])
            ->where('cycle_type', SystemConfiguration::current()->active_cycle)
            ->where('day_of_week', $day)
            ->when($teacher, fn ($query) => $query->where('teacher_id', $teacher->id))
            ->whereDoesntHave('journals', fn ($query) => $query->whereDate('date', $today))
            ->get()
            ->filter(function (Schedule $schedule) use ($now, $today) {
                $deadline = Carbon::parse(
                    $today.' '.$schedule->end_time,
                    config('app.timezone'),
                )->addMinutes(30);

                return $now->greaterThan($deadline);
            })
            ->values();
    }
}
```

`Carbon::parse` menerima format `HH:MM` maupun `HH:MM:SS`, sehingga snippet tidak bergantung pada cara driver database mengembalikan kolom time.

## 2. Dashboard API

Buat `app/Http/Controllers/Api/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JournalReminderService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function teacher(Request $request, JournalReminderService $service)
    {
        return response()->json([
            'data' => ['overdue_journals' => $service->overdueFor($request->user())],
        ]);
    }

    public function waka(JournalReminderService $service)
    {
        return response()->json([
            'data' => ['overdue_journals' => $service->overdueFor()],
        ]);
    }
}
```

Endpoint dihitung ketika dashboard dimuat. Tidak ada command, scheduler, queue, atau cron.

## 3. Rekap siswa/orang tua

Di response attendance siswa, tambahkan summary dari query yang sudah di-scope:

```php
$base = Attendance::where('student_id', $student->id);

$summary = (clone $base)
    ->selectRaw('status, COUNT(*) as total')
    ->groupBy('status')
    ->pluck('total', 'status');

$data = (clone $base)
    ->with('journal.schedule.teacher:id,name', 'journal.schedule.schoolClass:id,class_name')
    ->latest('date')
    ->paginate(20);
```

Kembalikan `data` dan `summary`. Untuk Orang Tua, gunakan `student_id` path parameter tetapi resolve dengan `$request->user()->children()->findOrFail($studentId)` sebelum query.

Buat `app/Http/Controllers/Api/ParentAttendanceController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class ParentAttendanceController extends Controller
{
    public function index(Request $request, int $student)
    {
        $child = $request->user()->children()
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
            ->findOrFail($student);

        $validated = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $base = Attendance::query()
            ->where('student_id', $child->id)
            ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date));

        $summary = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $data = (clone $base)
            ->with(['journal.schedule.teacher:id,name', 'journal.schedule.schoolClass:id,class_name'])
            ->latest('date')
            ->paginate(20);

        return response()->json([
            'data' => $data,
            'summary' => $summary,
            'student' => $child->only('id', 'name', 'class_id'),
        ]);
    }
}
```

## 4. Rekap Tim Kesiswaan

Scope Wali Kelas ke `homeroom_class_id`; Guru BK boleh memilih `class_id`. Buat `app/Http/Controllers/Api/StudentAffairsAttendanceController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class StudentAffairsAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $user = $request->user();
        $classId = $user->hasRole('Wali Kelas')
            ? $user->homeroom_class_id
            : ($validated['class_id'] ?? null);

        abort_if($user->hasRole('Wali Kelas') && $classId === null, 403, 'Kelas perwalian belum ditetapkan.');

        $base = Attendance::query()
            ->when($classId, fn ($query, $id) =>
                $query->whereHas('student', fn ($student) => $student->where('class_id', $id))
            )
            ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date));

        $summary = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $data = (clone $base)
            ->with(['student:id,name,class_id', 'journal.schedule.teacher:id,name'])
            ->latest('date')
            ->paginate(20);

        return response()->json(['data' => $data, 'summary' => $summary]);
    }
}
```

`class_id` dari request diabaikan untuk Wali Kelas dan selalu diganti kelas perwaliannya. Guru BK boleh mengosongkan filter untuk rekap seluruh kelas.

## Gerbang selesai

- [ ] Reminder sebelum atau tepat 30 menit tidak muncul.
- [ ] Reminder setelah 30 menit muncul bila jurnal belum ada.
- [ ] Reminder hilang bila jurnal tanggal tersebut ada.
- [ ] Reminder hanya hari ini dan active cycle.
- [ ] Waka melihat rekap guru terlambat; guru hanya miliknya.
- [ ] Tidak ada cron/scheduler untuk reminder.
- [ ] Rekap parent dan Wali Kelas tidak membocorkan siswa lain.

Commit:

```bash
git add app/Services app/Http/Controllers/Api
git commit -m "feat: tambah rekap dan reminder jurnal saat dashboard dimuat"
```
