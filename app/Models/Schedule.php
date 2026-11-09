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

            /**
     * Cache internal master jam pelajaran agar hemat query database
     */
    private static function getLessonHours()
    {
        static $lessonHoursCache = null;
        if ($lessonHoursCache === null) {
            try {
                $lessonHoursCache = \App\Models\LessonHour::all();
            } catch (\Throwable $e) {
                $lessonHoursCache = collect();
            }
        }
        return $lessonHoursCache;
    }

    public function getPeriodLabelAttribute(): string
    {
        $start = (int) substr($this->start_time, 0, 2);
        $end = (int) substr($this->end_time, 0, 2);

        if ($start === 0 && $end === 0) {
            return '-';
        }

        $timeRange = '';
        $lessonHours = self::getLessonHours();

        if ($lessonHours->isNotEmpty()) {
            $day = $this->day_of_week ?? 'Senin';
            $startHour = $lessonHours->first(fn($h) => $h->day_of_week === $day && (int)$h->session_number === $start)
                ?? $lessonHours->first(fn($h) => (int)$h->session_number === $start);
            $endHour   = $lessonHours->first(fn($h) => $h->day_of_week === $day && (int)$h->session_number === $end)
                ?? $lessonHours->first(fn($h) => (int)$h->session_number === $end);

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

    /**
     * Mengambil jam mulai resmi KBM (format H:i:s, misal 07:15:00 atau 14:00:00)
     */
    public function getRealStartTimeAttribute(): string
    {
        $start = (int) substr($this->start_time, 0, 2);
        $lessonHours = self::getLessonHours();

        if ($lessonHours->isNotEmpty()) {
            $day = $this->day_of_week ?? 'Senin';
            $startHour = $lessonHours->first(fn($h) => $h->day_of_week === $day && (int)$h->session_number === $start)
                ?? $lessonHours->first(fn($h) => (int)$h->session_number === $start);

            if ($startHour && $startHour->start_time) {
                $val = trim($startHour->start_time);
                return strlen($val) === 5 ? $val . ':00' : substr($val, 0, 8);
            }
        }

        $defaults = [
            1 => '07:15:00',
            2 => '08:00:00',
            3 => '08:45:00',
            4 => '09:45:00',
            5 => '10:30:00',
            6 => '11:15:00',
            7 => '12:30:00',
            8 => '13:15:00',
            9 => '14:00:00',
            10 => '14:45:00',
        ];

        return $defaults[$start] ?? '07:15:00';
    }

    /**
     * Mengambil jam selesai resmi KBM (format H:i:s, misal 12:00:00 atau 15:30:00)
     */
    public function getRealEndTimeAttribute(): string
    {
        $end = (int) substr($this->end_time, 0, 2);
        $lessonHours = self::getLessonHours();

        if ($lessonHours->isNotEmpty()) {
            $day = $this->day_of_week ?? 'Senin';
            $endHour = $lessonHours->first(fn($h) => $h->day_of_week === $day && (int)$h->session_number === $end)
                ?? $lessonHours->first(fn($h) => (int)$h->session_number === $end);

            if ($endHour && $endHour->end_time) {
                $val = trim($endHour->end_time);
                return strlen($val) === 5 ? $val . ':00' : substr($val, 0, 8);
            }
        }

        $defaults = [
            1 => '08:00:00',
            2 => '08:45:00',
            3 => '09:30:00',
            4 => '10:30:00',
            5 => '11:15:00',
            6 => '12:00:00',
            7 => '13:15:00',
            8 => '14:00:00',
            9 => '14:45:00',
            10 => '15:30:00',
        ];

        return $defaults[$end] ?? '15:30:00';
    }

    /**
     * Cek apakah jam mengajar sesi KBM ini sudah dimulai pada hari ini.
     * Guru HANYA BISA mengisi jurnal jika waktu sekarang >= real_start_time.
     */
    public function isTeachingTimeStarted(): bool
    {
        $currentTime = now()->format('H:i:s');
        return $currentTime >= $this->real_start_time;
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
