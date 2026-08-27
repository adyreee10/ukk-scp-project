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
        Schema::create('leave_requests', function (Blueprint $table) {
    $table->id();

    $table->foreignId('student_id')
        ->constrained('users')
        ->cascadeOnUpdate()
        ->cascadeOnDelete();

    $table->foreignId('applicant_id')
        ->constrained('users')
        ->cascadeOnUpdate()
        ->cascadeOnDelete();

    $table->foreignId('verified_by')
        ->nullable()
        ->constrained('users')
        ->cascadeOnUpdate()
        ->nullOnDelete();

    $table->date('start_date');

    $table->date('end_date');
    $table->string('leave_type', 10);

    $table->text('reason');

    $table->string('document_path');

    $table->string('status_approval', 20)->default('Pending');
    $table->index(['student_id', 'status_approval', 'start_date', 'end_date']);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
