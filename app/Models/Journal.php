<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Journal extends Model
{
    protected $fillable = [
        'schedule_id',
        'date',
        'topic_description',
        'photo_path',
    ];

    protected function casts():array
    {
        return ['date' => 'date'];
    }

    protected $appends = ['photo_url'];
    
// Ini adalah Accessor (Pembentuk 'photo_url')
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
            return $this->photo_path;
        }

        return url('media/' . ltrim($this->photo_path, '/'));
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }   

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
