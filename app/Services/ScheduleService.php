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
            ->where('start_time', '<=', $data['end_time'])
            ->where('end_time', '>=', $data['start_time'])
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