<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

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
