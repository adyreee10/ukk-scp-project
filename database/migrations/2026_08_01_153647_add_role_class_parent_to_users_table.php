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
                ->nullable()
                ->after('id')
                ->constrained('roles')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('class_id')
                ->nullable()
                ->after('role_id')
                ->constrained('school_classes')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->after('class_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('username', 50)
                ->unique()
                ->nullable()
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
            $table->dropForeign(['parent_id']);

            $table->dropColumn([
                'role_id',
                'class_id',
                'parent_id',
                'username'
            ]);
        });
    }
};
