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

        if ($day === 'Minggu')
        {
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
            $deadline = Carbon::parse(
                $today.' '.$schedule->end_time, config('app.timezone'),
            )->addMinutes(30);

            return $now->greaterThan($deadline);
        })
        ->values();
    }
}