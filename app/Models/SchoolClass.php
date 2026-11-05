<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'class_name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    public function homeroomTeachers(): HasMany
    {
        return $this->hasMany(User::class, 'homeroom_class_id');
    }

    public function counselors()
    {
        return $this->belongsToMany(User::class, 'counselor_classes', 'class_id', 'user_id')->withTimestamps();
    }

    // Helper: Mengambil Tingkat Kelas (X, XI, XII)
    public function getGradeAttribute(): string
    {
        $parts = explode(' ', trim($this->class_name));
        return $parts[0] ?? '';
    }

    // Helper: Mengambil Kode Jurusan (RPL, TKJ, dll)
    public function getMajorCodeAttribute(): string
    {
        $parts = explode(' ', trim($this->class_name));
        return $parts[1] ?? '';
    }

    // Helper: Mengambil Nomor Rombel (1, 2, dll)
    public function getRombelAttribute(): string
    {
        $parts = explode(' ', trim($this->class_name));
        return $parts[2] ?? '';
    }
}
