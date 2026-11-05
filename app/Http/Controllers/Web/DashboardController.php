<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
            return match ($request->user()->role?->role_name) {
            'Waka Kurikulum'                 => redirect()->route('waka.dashboard'),
            'Guru', 'Wali Kelas', 'Guru BK'  => redirect()->route('teacher.dashboard'),
            'Siswa', 'Orang Tua'             => redirect()->route('family.dashboard'),
            default                          => abort(403, 'Role akun belum valid.'),
        };

    }

}
