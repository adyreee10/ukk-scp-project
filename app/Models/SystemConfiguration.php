<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    public const CYCLES = ['Putaran 1', 'Putaran 2'];

    protected $fillable = [
        'active_cycle',
        'academic_year',
        'semester',
        'is_schedule_locked',
        'is_cycle_1_locked',
        'is_cycle_2_locked',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_schedule_locked' => 'boolean',
            'is_cycle_1_locked'  => 'boolean',
            'is_cycle_2_locked'  => 'boolean',
        ];
    }

    /**
     * Memeriksa apakah siklus tertentu (Putaran 1 / Putaran 2) sedang dikunci.
     * Jika parameter $cycle tidak disertakan, periksa siklus aktif saat ini.
     */
    public function isCycleLocked(?string $cycle = null): bool
    {
        $target = $cycle ?? $this->active_cycle;

        if ($target === 'Putaran 1') {
            return (bool) ($this->is_cycle_1_locked ?? false);
        }

        if ($target === 'Putaran 2') {
            return (bool) ($this->is_cycle_2_locked ?? false);
        }

        if ($target === 'all') {
            return (bool) ($this->is_cycle_1_locked && $this->is_cycle_2_locked);
        }

        return (bool) ($this->is_schedule_locked ?? false);
    }

    /**
     * Mengecek apakah semua putaran terkunci
     */
    public function isAllCyclesLocked(): bool
    {
        return (bool) ($this->is_cycle_1_locked && $this->is_cycle_2_locked);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

        public function getAcademicYearAttribute($value)
    {
        return $value ?? '2026/2027';
    }

    public function getSemesterAttribute($value)
    {
        return $value ?? 'Semester 1 (Ganjil)';
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'active_cycle'       => 'Putaran 1',
            'academic_year'      => '2026/2027',
            'semester'           => 'Semester 1 (Ganjil)',
            'is_schedule_locked' => false,
            'is_cycle_1_locked'  => false,
            'is_cycle_2_locked'  => false,
        ]);
    }
}
