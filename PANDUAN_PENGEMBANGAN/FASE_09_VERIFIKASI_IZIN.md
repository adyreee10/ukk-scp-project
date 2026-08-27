# Fase 09 — Verifikasi Izin oleh Wali Kelas dan Guru BK

## Tujuan

Memisahkan dua role kesiswaan, membatasi cakupan data, dan menjaga transisi status. Approval pada fase ini **tidak membuat attendance**.

## Controller

Ganti controller verifikasi dengan:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveRequestVerificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $this->visibleTo($request->user())
                ->with(['student:id,name,class_id', 'student.schoolClass:id,class_name', 'applicant:id,name', 'verifier:id,name'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(Request $request, int $id)
    {
        return response()->json([
            'data' => $this->visibleTo($request->user())
                ->with(['student.schoolClass', 'applicant:id,name', 'verifier:id,name'])
                ->findOrFail($id),
        ]);
    }

    public function approve(Request $request, int $id)
    {
        return $this->transition($request, $id, 'Approved');
    }

    public function reject(Request $request, int $id)
    {
        return $this->transition($request, $id, 'Rejected');
    }

    private function transition(Request $request, int $id, string $target)
    {
        $leave = DB::transaction(function () use ($request, $id, $target) {
            $leave = $this->visibleTo($request->user())->lockForUpdate()->findOrFail($id);

            if ($leave->status_approval !== 'Pending') {
                abort(409, 'Pengajuan sudah pernah diverifikasi.');
            }

            $leave->update([
                'status_approval' => $target,
                'verified_by' => $request->user()->id,
            ]);

            return $leave->fresh();
        });

        return response()->json([
            'message' => $target === 'Approved' ? 'Pengajuan disetujui.' : 'Pengajuan ditolak.',
            'data' => $leave->load('student:id,name', 'verifier:id,name'),
        ]);
    }

    private function visibleTo(User $user): Builder
    {
        $query = LeaveRequest::query();

        if ($user->hasRole('Wali Kelas')) {
            abort_if($user->homeroom_class_id === null, 403, 'Kelas perwalian belum ditetapkan.');
            $query->whereHas('student', fn ($student) =>
                $student->where('class_id', $user->homeroom_class_id)
            );
        }

        return $query;
    }
}
```

Guru BK melihat seluruh pengajuan sesuai keputusan kontrak. Middleware route memastikan selain Wali Kelas/Guru BK tidak masuk controller.

## Dokumen berotorisasi

Untuk public disk, response dapat memberi `Storage::disk('public')->url(...)`. Jangan menyediakan endpoint download tanpa memastikan record berada dalam `visibleTo()`.

## Gerbang selesai

- [ ] Wali Kelas hanya melihat siswa kelas perwaliannya.
- [ ] Guru BK melihat data yang diperlukan lintas kelas.
- [ ] Hanya Pending dapat berubah.
- [ ] Dua request bersamaan tidak dapat memproses dua kali karena row lock.
- [ ] Verifier tercatat.
- [ ] Jumlah attendance tetap tidak berubah sesaat setelah approve.

Commit:

```bash
git add app/Http/Controllers/Api/LeaveRequestVerificationController.php
git commit -m "feat: batasi dan amankan verifikasi izin"
```
