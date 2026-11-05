<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    protected $fillable = [
        'teacher_id',
        'class_id',
        'room_id',
        'subject',
        'day_of_week',
        'start_time',
        'end_time',
        'cycle_type',
    ];

            public function getPeriodLabelAttribute(): string
    {
        $start = (int) substr($this->start_time, 0, 2);
        $end = (int) substr($this->end_time, 0, 2);

        if ($start === 0 && $end === 0) {
            return '-';
        }

        // Cache data jam pelajaran di memori (cukup 1x query, bukan 200x)
        $timeRange = '';
        static $lessonHoursCache = null;
        if ($lessonHoursCache === null) {
            $lessonHoursCache = \Illuminate\Support\Facades\Schema::hasTable('lesson_hours')
                ? \App\Models\LessonHour::all()
                : collect();
        }

        if ($lessonHoursCache->isNotEmpty()) {
            $day = $this->day_of_week ?? 'Senin';
            $startHour = $lessonHoursCache->first(fn($h) => $h->day_of_week === $day && (int)$h->session_number === $start)
                ?? $lessonHoursCache->first(fn($h) => (int)$h->session_number === $start);
            $endHour   = $lessonHoursCache->first(fn($h) => $h->day_of_week === $day && (int)$h->session_number === $end)
                ?? $lessonHoursCache->first(fn($h) => (int)$h->session_number === $end);

            if ($startHour && $endHour) {
                $timeRange = " (" . substr($startHour->start_time, 0, 5) . " - " . substr($endHour->end_time, 0, 5) . ")";
            } elseif ($startHour) {
                $timeRange = " (" . substr($startHour->start_time, 0, 5) . " - " . substr($startHour->end_time, 0, 5) . ")";
            }
        }

        if ($start === $end || $end === 0) {
            return "Jam ke - {$start}{$timeRange}";
        }

        return "Jam ke - {$start} s/d Jam ke - {$end}{$timeRange}";
    }

    public function scopeActiveCycle($query)
    {
        return $query->where('cycle_type', SystemConfiguration::current()->active_cycle,
        );
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class);
    }

}
