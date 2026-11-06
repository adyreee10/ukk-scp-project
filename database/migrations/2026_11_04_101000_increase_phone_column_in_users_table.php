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
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN phone TYPE VARCHAR(50);');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 50)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN phone TYPE VARCHAR(20);');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()->change();
            });
        }
    }
};
