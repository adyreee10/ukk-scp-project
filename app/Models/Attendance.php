<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    public const STATUSES = ['Hadir', 'Izin', 'Sakit', 'Alpa'];

    protected $fillable = [
        'journal_id',
        'student_id',
        'status',
        'note',
        'date',
    ];

    protected function casts():array
    {
        return ['date' => 'date'];
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
    
