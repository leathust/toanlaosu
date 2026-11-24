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
        Schema::create('user_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('lesson_block_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('status', 30)->default('in_progress');
            $table->json('answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('score', 10, 4)->nullable();
            $table->decimal('max_score', 10, 4)->nullable();
            $table->unsignedInteger('hints_used')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->json('feedback')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'question_version_id', 'submitted_at'], 'user_attempts_user_question_submitted_index');
            $table->index(['lesson_block_id', 'user_id'], 'user_attempts_block_user_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_attempts');
    }
};
