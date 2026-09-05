<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    public const CYCLES = ['Putaran 1', 'Putaran 2'];

    protected $fillable = [
        'active_cycle',
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

    public static function current():self
    {
        return static::query()->findOrFail(1);
    }
}
