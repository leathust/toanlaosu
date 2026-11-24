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
        Schema::create('exam_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('question_version_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order');
            $table->decimal('points', 10, 4)->default(0);
            $table->string('section_key', 100)->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['exam_version_id', 'sort_order'], 'exam_questions_version_sort_unique');
            $table->index(['exam_version_id', 'section_key'], 'exam_questions_version_section_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
