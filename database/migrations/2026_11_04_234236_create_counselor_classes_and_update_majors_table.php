<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('counselor_classes')) {
            Schema::create('counselor_classes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'class_id']);
            });
        }

        Schema::table('majors', function (Blueprint $table) {
            if (!Schema::hasColumn('majors', 'rombel_x')) {
                $table->integer('rombel_x')->default(2)->after('name');
            }
            if (!Schema::hasColumn('majors', 'rombel_xi')) {
                $table->integer('rombel_xi')->default(2)->after('rombel_x');
            }
        });

        // Migrate existing rombel_x_xi values if column exists
        if (Schema::hasColumn('majors', 'rombel_x_xi')) {
            \Illuminate\Support\Facades\DB::table('majors')->update([
                'rombel_x'  => \Illuminate\Support\Facades\DB::raw('COALESCE(rombel_x_xi, 2)'),
                'rombel_xi' => \Illuminate\Support\Facades\DB::raw('COALESCE(rombel_x_xi, 2)'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('counselor_classes');

        Schema::table('majors', function (Blueprint $table) {
            if (Schema::hasColumn('majors', 'rombel_x')) {
                $table->dropColumn('rombel_x');
            }
            if (Schema::hasColumn('majors', 'rombel_xi')) {
                $table->dropColumn('rombel_xi');
            }
        });
    }
};
