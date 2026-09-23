<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\SystemConfiguration;
use App\Http\Controllers\Controller;
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
            'active_cycle' => ['sometimes', 'required', 
            Rule::in(SystemConfiguration::CYCLES)],
            'is_schedule_locked' => ['sometimes', 'required', 'boolean'], 
        ]);

        if ($validated === [])
        {
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
