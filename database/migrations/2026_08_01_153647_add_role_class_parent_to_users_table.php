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
        Schema::table('users', function (Blueprint $table) {
            //
            $table->foreignId('role_id')
                ->after('id')
                ->constrained('roles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('class_id')
                ->nullable()
                ->after('role_id')
                ->constrained('classes')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('homeroom_class_id')
                ->nullable()
                ->after('class_id')
                ->constrained('classes')
                ->cascadeOnUpdate()
                ->nullOnUpdate();

            $table->foreignId('parent_id')
                ->nullable()
                ->after('homeroom_class_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('username', 50)
                ->unique()
                ->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //

            $table->dropForeign(['role_id']);
            $table->dropForeign(['class_id']);
            $table->dropForeign(['homeroom_class_id']);
            $table->dropForeign(['parent_id']);

            $table->dropColumn(['username']);
        });
    }
};
