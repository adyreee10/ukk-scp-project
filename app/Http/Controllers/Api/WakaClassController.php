<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WakaClassController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => SchoolClass::orderBy('class_name')->paginate(20)
        ]);
    }

    public function store (Request $request)
    {
        $data = $request->validate([
            'class_name' => ['required', 'string', 'max:50', 'unique:classes,class_name'],
        ]);

        return response()->json([
            'message' => 'Kelas berhasil dibuat.',
            'data' => SchoolClass::create($data),
        ], 201);
    }

    public function update (Request $request, SchoolClass $schoolClass)
    {
        $data = $request->validate([
            'class_name' => ['required', 'string', 'max:50', Rule::unique('classes', 'class_name')->ignore($schoolClass->id),
            ],
        ]);
        $schoolClass->update($data);

        return response()->json([
            'message' => 'Kelas berhasil diperbarui.',
            'data' => $schoolClass
        ]);
    }

    public function destroy (SchoolClass $schoolClass)
    {
        if (
            $schoolClass->users()->exists()
            ||$schoolClass->homeroomTeachers()->exists()
            ||$schoolClass->schedules()->exists()
        ){
            return response()->json([
                'message' => 'Kelas masih dipakai dan tidak dapat dihapus.'
            ], 409);
        }
        $schoolClass->delete();
        return response()->noContent();
    }
}
