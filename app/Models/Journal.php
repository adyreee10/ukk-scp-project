<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $fillable = [
        'schedule_id',
        'date',
        'topic_description',
        'photo_path',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }   

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
