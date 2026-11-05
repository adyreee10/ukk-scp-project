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
    /**
     * Pemetaan Jam Selesai Pelajaran Sekolah Nyata:
     * Digunakan untuk menghitung batas toleransi keterlambatan jurnal (+30 menit).
     */
    public const BELL_END_TIMES = [
        1  => '08:00',
        2  => '08:45',
        3  => '09:30',
        4  => '10:30',
        5  => '11:15',
        6  => '12:00',
        7  => '13:15',
        8  => '14:00',
        9  => '14:45',
        10 => '15:30',
    ];

    public function overdueFor(?User $teacher = null): Collection
    {
        $now = now();
        $today = $now->toDateString();
        $day = IndonesianDay::from($now);

        // Hari Sabtu & Minggu Libur (Tidak ada pengingat KBM)
        if (in_array($day, ['Sabtu', 'Minggu'])) {
            return collect();
        }

        return Schedule::query()
            ->with([
                'teacher:id,name',
                'schoolClass:id,class_name',
                'room:id,room_name'
            ])
            ->where('cycle_type', SystemConfiguration::current()->active_cycle)
            ->where('day_of_week', $day)
            ->when($teacher, fn ($query) => $query->where('teacher_id', $teacher->id))
            ->whereDoesntHave('journals', fn ($query) => $query->whereDate('date', $today))
            ->get()
            ->filter(function(Schedule $schedule) use ($now, $today) {
                // Ambil nomor jam pelajaran selesai (misal '04:00' -> 4)
                $endPeriod = (int) substr($schedule->end_time, 0, 2);
                $clockTime = self::BELL_END_TIMES[$endPeriod] ?? '15:30';

                // Batas toleransi: Jam selesai pelajaran + 30 menit
                $deadline = Carbon::parse(
                    $today . ' ' . $clockTime,
                    config('app.timezone')
                )->addMinutes(30);

                return $now->greaterThan($deadline);
            })
            ->values();
    }
}
