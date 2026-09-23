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
            'data' => User::with([
                'role:id,role_name',
                'schoolClass:id,class_name',
                'homeroomClass:id,class_name'
            ])->orderBy('name')->paginate(20),
        ]);
    }

    public function store (Request $request)
    {
        $data = 
        $this->validated($request);
        $this->assertRoleFields($data);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        return response()->json([
            'message' => 'User berhasil dibuat.',
            'data' => $user->load('role:id,role_name'),
        ], 201);
    }

    public function update (Request $request, User $user)
    {
        $data = 
        $this->validated($request, $user);
        $this->assertRoleFields($data);

        if (empty($data['password'] ?? null))
            {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);
            return response()->json([
                'message' => 'User berhasil diperbarui.',
                'data' => $user->fresh('role')
            ]);
    }

    public function destroy (Request $request, User $user)
    {
        if ($request->user()->is($user)){
            return response()->json([
                'message' => 'Akun yang sedang digunakan tidak dapat dihapus.'
            ], 409);
        }

        $hasHistory = $user->schedules()->exists()
        ||$user->attendances()->exists()
        ||$user->leaveRequests()->exists()
        ||$user->submittedLeaveRequests()->exists()
        ||$user->verifiedLeaveRequests()->exists()
        ||$user->updatedConfigurations()->exists()
        ||$user->children()->exists();

        if ($hasHistory){
            return response()->json([
                'message' => 'User memiliki relasi histori dan tidak dapat dihapus.'
            ], 409);
        }

        $user->delete();
        return response()->noContent();
    }

    private function validated (Request $request, ?User $user = null): array
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
        throw ValidationException::withMessages([
            'class_id' => 'Kelas wajib untuk siswa.',
        ]);
    }

    if ($role !== 'Siswa' && ! empty($data['class_id'])) {
        throw ValidationException::withMessages([
            'class_id' => 'class_id hanya untuk siswa.',
        ]);
    }

    if ($role === 'Wali Kelas' && empty($data['homeroom_class_id'])) {
        throw ValidationException::withMessages([
            'homeroom_class_id' => 'Kelas perwalian wajib diisi.',
        ]);
    }

    if ($role !== 'Wali Kelas' && ! empty($data['homeroom_class_id'])) {
        throw ValidationException::withMessages([
            'homeroom_class_id' => 'Field ini hanya untuk Wali Kelas.',
        ]);
    }

    if ($role !== 'Siswa' && ! empty($data['parent_id'])) {
        throw ValidationException::withMessages([
            'parent_id' => 'parent_id hanya untuk siswa.',
        ]);
    }

    if ($role === 'Siswa' && ! empty($data['parent_id'])) {
        if (! User::findOrFail($data['parent_id'])->hasRole('Orang Tua')) {
            throw ValidationException::withMessages([
                'parent_id' => 'parent_id harus akun Orang Tua.',
            ]);
        }
    }
}
}
