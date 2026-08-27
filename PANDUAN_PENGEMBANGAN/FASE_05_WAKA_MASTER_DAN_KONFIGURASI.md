# Fase 05 — API Waka, Master Data, Active Cycle, dan Lock

## Tujuan

Memberi Waka kendali terhadap konfigurasi global dan data dasar. Ini wajib selesai sebelum CRUD jadwal.

## 1. Controller konfigurasi

Buat `app/Http/Controllers/Api/WakaConfigurationController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WakaConfigurationController extends Controller
{
    public function show()
    {
        return response()->json([
            'data' => SystemConfiguration::current()->load('updater:id,name'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'active_cycle' => ['sometimes', 'required', Rule::in(SystemConfiguration::CYCLES)],
            'is_schedule_locked' => ['sometimes', 'required', 'boolean'],
        ]);

        if ($validated === []) {
            return response()->json([
                'message' => 'Tidak ada konfigurasi yang dikirim.',
            ], 422);
        }

        $configuration = SystemConfiguration::current();
        $configuration->update([
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Konfigurasi sistem berhasil diperbarui.',
            'data' => $configuration->fresh()->load('updater:id,name'),
        ]);
    }
}
```

Satu endpoint update boleh mengubah cycle, lock, atau keduanya. `sometimes|required` mencegah nilai null.

## 2. CRUD kelas

Buat `app/Http/Controllers/Api/WakaClassController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WakaClassController extends Controller
{
    public function index()
    {
        return response()->json(['data' => SchoolClass::orderBy('class_name')->paginate(20)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_name' => ['required', 'string', 'max:50', 'unique:classes,class_name'],
        ]);

        return response()->json([
            'message' => 'Kelas berhasil dibuat.',
            'data' => SchoolClass::create($data),
        ], 201);
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        $data = $request->validate([
            'class_name' => [
                'required', 'string', 'max:50',
                Rule::unique('classes', 'class_name')->ignore($schoolClass->id),
            ],
        ]);
        $schoolClass->update($data);

        return response()->json(['message' => 'Kelas berhasil diperbarui.', 'data' => $schoolClass]);
    }

    public function destroy(SchoolClass $schoolClass)
    {
        if (
            $schoolClass->users()->exists()
            || $schoolClass->homeroomTeachers()->exists()
            || $schoolClass->schedules()->exists()
        ) {
            return response()->json(['message' => 'Kelas masih dipakai dan tidak dapat dihapus.'], 409);
        }

        $schoolClass->delete();
        return response()->noContent();
    }
}
```

## 3. CRUD ruang

Buat `app/Http/Controllers/Api/WakaRoomController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WakaRoomController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Room::orderBy('room_name')->paginate(20)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_name' => ['required', 'string', 'max:50', 'unique:rooms,room_name'],
        ]);

        return response()->json([
            'message' => 'Ruang berhasil dibuat.',
            'data' => Room::create($data),
        ], 201);
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'room_name' => [
                'required', 'string', 'max:50',
                Rule::unique('rooms', 'room_name')->ignore($room->id),
            ],
        ]);
        $room->update($data);

        return response()->json(['message' => 'Ruang berhasil diperbarui.', 'data' => $room]);
    }

    public function destroy(Room $room)
    {
        if ($room->schedules()->exists()) {
            return response()->json(['message' => 'Ruang masih dipakai dan tidak dapat dihapus.'], 409);
        }

        $room->delete();
        return response()->noContent();
    }
}
```

## 4. CRUD user

Buat `app/Http/Controllers/Api/WakaUserController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WakaUserController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => User::with(['role:id,role_name', 'schoolClass:id,class_name', 'homeroomClass:id,class_name'])
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->assertRoleFields($data);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        return response()->json([
            'message' => 'User berhasil dibuat.',
            'data' => $user->load('role:id,role_name'),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        $this->assertRoleFields($data);

        if (empty($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return response()->json(['message' => 'User berhasil diperbarui.', 'data' => $user->fresh('role')]);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return response()->json(['message' => 'Akun yang sedang digunakan tidak dapat dihapus.'], 409);
        }

        $hasHistory = $user->schedules()->exists()
            || $user->attendances()->exists()
            || $user->leaveRequests()->exists()
            || $user->submittedLeaveRequests()->exists()
            || $user->verifiedLeaveRequests()->exists()
            || $user->updatedConfigurations()->exists()
            || $user->children()->exists();

        if ($hasHistory) {
            return response()->json(['message' => 'User memiliki relasi histori dan tidak dapat dihapus.'], 409);
        }

        $user->delete();
        return response()->noContent();
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'class_id' => ['present', 'nullable', 'exists:classes,id'],
            'homeroom_class_id' => ['present', 'nullable', 'exists:classes,id'],
            'parent_id' => ['present', 'nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
        ]);
    }

    private function assertRoleFields(array $data): void
    {
        $role = Role::findOrFail($data['role_id'])->role_name;

        if ($role === 'Siswa' && empty($data['class_id'])) {
            throw ValidationException::withMessages(['class_id' => 'Kelas wajib untuk siswa.']);
        }
        if ($role !== 'Siswa' && ! empty($data['class_id'])) {
            throw ValidationException::withMessages(['class_id' => 'class_id hanya untuk siswa.']);
        }
        if ($role === 'Wali Kelas' && empty($data['homeroom_class_id'])) {
            throw ValidationException::withMessages(['homeroom_class_id' => 'Kelas perwalian wajib diisi.']);
        }
        if ($role !== 'Wali Kelas' && ! empty($data['homeroom_class_id'])) {
            throw ValidationException::withMessages(['homeroom_class_id' => 'Field ini hanya untuk Wali Kelas.']);
        }
        if ($role !== 'Siswa' && ! empty($data['parent_id'])) {
            throw ValidationException::withMessages(['parent_id' => 'parent_id hanya untuk siswa.']);
        }
        if ($role === 'Siswa' && ! empty($data['parent_id'])) {
            if (! User::findOrFail($data['parent_id'])->hasRole('Orang Tua')) {
                throw ValidationException::withMessages(['parent_id' => 'parent_id harus akun Orang Tua.']);
            }
        }
    }
}
```

Untuk UKK, lebih aman menolak delete user berhistori. Pada pengembangan lanjutan, tambahkan `is_active` daripada menghapus histori.

## Gerbang selesai

- [ ] Hanya Waka Kurikulum yang dapat membuka endpoint fase ini.
- [ ] Cycle hanya menerima Putaran 1/2.
- [ ] Lock hanya boolean.
- [ ] Update selalu mencatat `updated_by`.
- [ ] Data master yang masih direferensikan tidak bisa dihapus.
- [ ] User dengan kombinasi role-field tidak valid ditolak.

Commit:

```bash
git add app/Http/Controllers/Api routes/api.php
git commit -m "feat: tambah master data dan konfigurasi global Waka"
```
