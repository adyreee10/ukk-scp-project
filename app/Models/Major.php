<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    protected $fillable = [
        'code',
        'name',
        'rombel_x',
        'rombel_xi',
        'rombel_x_xi',
        'rombel_xii',
        'description',
    ];

    /**
     * Mengambil seluruh kelas yang sesuai dengan kode jurusan ini
     */
    public function getAssociatedClasses()
    {
        return SchoolClass::where('class_name', 'like', "% {$this->code} %")
            ->orWhere('class_name', 'like', "% {$this->code}")
            ->withCount('users')
            ->get();
    }
}
