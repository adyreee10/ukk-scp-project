<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    public const TYPES = ['Izin', 'Sakit'];
    public const APPROVALS = ['Pending', 'Approved', 'Rejected'];

    protected $fillable = [
        'student_id',
        'applicant_id',
        'verified_by',
        'start_date',
        'end_date',
        'leave_type',
        'reason',
        'document_path',
        'status_approval',
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
}
