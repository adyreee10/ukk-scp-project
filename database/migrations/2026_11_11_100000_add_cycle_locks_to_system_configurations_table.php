<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->boolean('is_cycle_1_locked')->default(false)->after('is_schedule_locked');
            $table->boolean('is_cycle_2_locked')->default(false)->after('is_cycle_1_locked');
        });

        // Inisialisasi nilai awal dari is_schedule_locked yang sudah ada
        try {
            DB::table('system_configurations')->update([
                'is_cycle_1_locked' => DB::raw('is_schedule_locked'),
                'is_cycle_2_locked' => DB::raw('is_schedule_locked'),
            ]);
        } catch (\Throwable $e) {
            // Abaikan jika tabel kosong
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->dropColumn(['is_cycle_1_locked', 'is_cycle_2_locked']);
        });
    }
};

