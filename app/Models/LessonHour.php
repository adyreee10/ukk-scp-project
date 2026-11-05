<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonHour extends Model
{
    protected $fillable = [
        'day_of_week',
        'session_number',
        'name',
        'start_time',
        'end_time',
        'duration_minutes',
        'notes',
    ];
}
