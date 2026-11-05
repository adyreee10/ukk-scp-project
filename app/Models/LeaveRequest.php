<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    public const TYPES = ['Sakit', 'Izin', 'Potong Jam Pelajaran'];
    public const APPROVALS = ['Pending', 'Approved', 'Rejected'];

    protected $fillable = [
        'student_id',
        'applicant_id',
        'verified_by',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'leave_type',
        'reason',
        'document_path',
        'status_approval',
        'rejection_note',
    ];

    protected function casts():array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function scopeApprovedOn($query, string $date)
    {
        return $query
            ->where('status_approval', 'Approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

        public function getPeriodLabelAttribute(): ?string
    {
        if (!$this->start_time) {
            return null;
        }

        $start = (int) substr($this->start_time, 0, 2);
        $end   = $this->end_time ? (int) substr($this->end_time, 0, 2) : $start;

        if ($start === 0) {
            return $this->start_time;
        }

        if ($start === $end || $end === 0) {
            return "Jam ke - {$start}";
        }

        return "Jam ke - {$start} s/d {$end}";
    }

}
