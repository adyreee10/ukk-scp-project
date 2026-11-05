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
        'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_schedule_locked' => 'boolean'];
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
        ]);
    }
}
