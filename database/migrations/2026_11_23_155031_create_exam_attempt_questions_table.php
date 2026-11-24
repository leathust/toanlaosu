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
        Schema::create('exam_attempt_questions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('exam_attempt_id');
            $table->unsignedBigInteger('exam_question_id');
            $table->foreignId('question_version_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order');
            $table->json('option_order')->nullable();
            $table->decimal('points', 10, 4)->default(0);
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->unique(['exam_attempt_id', 'sort_order'], 'exam_attempt_questions_attempt_sort_unique');
            $table->unique(['exam_attempt_id', 'exam_question_id'], 'exam_attempt_questions_attempt_question_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_questions');
    }
};
