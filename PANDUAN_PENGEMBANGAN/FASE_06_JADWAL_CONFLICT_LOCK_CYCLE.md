# Fase 06 — CRUD Jadwal, Conflict Prevention, Lock, dan Cycle

## Tujuan

Menyelesaikan BR-001, BR-002, dan proteksi backend BR-003.

## 1. Service jadwal

Buat `app/Services/ScheduleService.php`:

```php
<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function create(User $teacher, array $data): Schedule
    {
        return DB::transaction(function () use ($teacher, $data) {
            $this->assertUnlocked();
            $this->assertNoConflict($teacher->id, $data);

            return Schedule::create([...$data, 'teacher_id' => $teacher->id]);
        });
    }

    public function update(Schedule $schedule, array $data): Schedule
    {
        return DB::transaction(function () use ($schedule, $data) {
            $this->assertUnlocked();
            $this->assertNoConflict($schedule->teacher_id, $data, $schedule->id);
            $schedule->update($data);

            return $schedule->fresh();
        });
    }

    public function delete(Schedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            $this->assertUnlocked();

            if ($schedule->journals()->exists()) {
                abort(409, 'Jadwal yang sudah memiliki jurnal tidak dapat dihapus.');
            }

            $schedule->delete();
        });
    }

    private function assertUnlocked(): void
    {
        $configuration = SystemConfiguration::query()->lockForUpdate()->findOrFail(1);

        if ($configuration->is_schedule_locked) {
            abort(403, 'Jadwal sedang dikunci oleh Waka Kurikulum.');
        }
    }

    private function assertNoConflict(int $teacherId, array $data, ?int $exceptId = null): void
    {
        $conflict = Schedule::query()
            ->lockForUpdate()
            ->when($exceptId !== null, fn ($query) => $query->where('id', '<>', $exceptId))
            ->where('cycle_type', $data['cycle_type'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->where(function ($query) use ($teacherId, $data) {
                $query->where('teacher_id', $teacherId)
                    ->orWhere('class_id', $data['class_id'])
                    ->orWhere('room_id', $data['room_id']);
            })
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'schedule' => "Bentrok dengan jadwal #{$conflict->id} pada guru, kelas, atau ruang yang sama.",
            ]);
        }
    }
}
```

Rumus yang dipakai tepat: `existing.start < new.end AND existing.end > new.start`. Jadwal 08:00–10:00 dan 10:00–12:00 tidak bentrok.

## 2. Controller final

Ganti `TeacherScheduleController`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Services\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherScheduleController extends Controller
{
    public function __construct(private readonly ScheduleService $service) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'cycle' => ['nullable', Rule::in(SystemConfiguration::CYCLES)],
        ]);
        $cycle = $validated['cycle'] ?? SystemConfiguration::current()->active_cycle;

        $data = Schedule::with(['schoolClass:id,class_name', 'room:id,room_name'])
            ->where('teacher_id', $request->user()->id)
            ->where('cycle_type', $cycle)
            ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 END")
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'data' => $data,
            'meta' => [
                'active_cycle' => SystemConfiguration::current()->active_cycle,
                'selected_cycle' => $cycle,
                'is_schedule_locked' => SystemConfiguration::current()->is_schedule_locked,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $schedule = $this->service->create($request->user(), $this->validated($request));
        return response()->json(['message' => 'Jadwal berhasil dibuat.', 'data' => $schedule], 201);
    }

    public function update(Request $request, int $id)
    {
        $schedule = $this->owned($request, $id);
        $schedule = $this->service->update($schedule, $this->validated($request));
        return response()->json(['message' => 'Jadwal berhasil diperbarui.', 'data' => $schedule]);
    }

    public function destroy(Request $request, int $id)
    {
        $this->service->delete($this->owned($request, $id));
        return response()->noContent();
    }

    private function owned(Request $request, int $id): Schedule
    {
        return Schedule::where('teacher_id', $request->user()->id)->findOrFail($id);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'subject' => ['required', 'string', 'max:100'],
            'day_of_week' => ['required', Rule::in(Schedule::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'cycle_type' => ['required', Rule::in(SystemConfiguration::CYCLES)],
        ]);
    }
}
```

Jika PostgreSQL menolak `orderByRaw` karena casing nama kolom, ekspresi di atas tetap valid karena `day_of_week` lowercase. Jangan interpolasikan input user ke raw query.

## 3. Jadwal siswa harus active cycle

Pada `StudentScheduleController`, tambahkan:

```php
->where('cycle_type', SystemConfiguration::current()->active_cycle)
```

Import `SystemConfiguration`. Jika `class_id` siswa null, kembalikan 422 dengan pesan akun belum ditempatkan ke kelas.

## Gerbang selesai

- [ ] Guru dapat CRUD jadwal miliknya saat unlocked.
- [ ] Guru tidak dapat mengubah jadwal guru lain; hasil 404.
- [ ] Bentrok teacher/class/room pada cycle, hari, dan waktu overlap ditolak.
- [ ] Jadwal yang menempel tepat pada jam akhir diterima.
- [ ] Semua mutation ditolak 403 ketika locked.
- [ ] Jadwal operasional siswa/guru default mengikuti active cycle.
- [ ] Jadwal dengan jurnal tidak dapat dihapus.

Commit:

```bash
git add app/Services app/Http/Controllers/Api
git commit -m "feat: implementasikan jadwal dengan conflict lock dan cycle"
```
