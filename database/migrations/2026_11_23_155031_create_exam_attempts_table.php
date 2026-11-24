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
        Schema::create('exam_attempts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('exam_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('status', 30)->default('in_progress');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->decimal('score', 10, 4)->nullable();
            $table->decimal('max_score', 10, 4)->nullable();
            $table->unsignedInteger('save_version')->default(0);
            $table->timestamp('retention_expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['exam_version_id', 'user_id', 'attempt_number'], 'exam_attempts_version_user_number_unique');
            $table->index(['status', 'deadline_at', 'id'], 'exam_attempts_status_deadline_index');
            $table->index('retention_expires_at', 'exam_attempts_retention_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
