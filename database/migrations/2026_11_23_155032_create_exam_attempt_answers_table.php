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
        Schema::create('exam_attempt_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_attempt_question_id')->constrained()->restrictOnDelete();
            $table->json('answer')->nullable();
            $table->unsignedInteger('save_version')->default(0);
            $table->timestamp('first_saved_at')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('score', 10, 4)->nullable();
            $table->json('grading_details')->nullable();
            $table->timestamps();
            $table->unique('exam_attempt_question_id', 'exam_attempt_answers_attempt_question_unique');
            $table->index('last_saved_at', 'exam_attempt_answers_last_saved_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_answers');
    }
};
