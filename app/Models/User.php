<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

        protected $fillable = [
        'role_id',
        'class_id',
        'student_status',
        'homeroom_class_id',
        'parent_id',
        'name',
        'username',
        'nip',
        'nis',
        'email',
        'phone',
        'address',
        'birth_date',
        'password',
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    

}
    // =========================
    // RELATIONSHIP
    // =========================

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function homeroomClass()
    {
        return $this->belongsTo(SchoolClass::class, 'homeroom_class_id');
    }

    public function counselorClasses()
    {
        return $this->belongsToMany(SchoolClass::class, 'counselor_classes', 'user_id', 'class_id')->withTimestamps();
    }

    public function getHandledClassIds(): array
    {
        $roleName = $this->role?->role_name;
        if ($roleName === 'Wali Kelas') {
            return $this->homeroom_class_id ? [$this->homeroom_class_id] : [];
        }
        if ($roleName === 'Guru BK') {
            return $this->counselorClasses()->pluck('classes.id')->toArray();
        }
        return [];
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'student_id');
    }

    public function verifiedLeaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'verified_by');
    }

    public function updatedConfigurations()
    {
        return $this->hasMany(SystemConfiguration::class, 'updated_by');
    }

    public function submittedLeaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'applicant_id');
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role?->role_name, $roles, true);
    }

        public function getStudentStatusAttribute($value)
    {
        return $value ?? 'Aktif';
    }

}