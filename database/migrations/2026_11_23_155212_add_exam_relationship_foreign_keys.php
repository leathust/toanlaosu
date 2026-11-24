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
        Schema::table('exam_versions', function (Blueprint $table): void {
            $table->foreign('exam_id', 'exam_versions_exam_id_foreign')
                ->references('id')->on('exams')->restrictOnDelete();
        });

        Schema::table('exam_attempt_questions', function (Blueprint $table): void {
            $table->foreign('exam_attempt_id', 'exam_attempt_questions_attempt_id_foreign')
                ->references('id')->on('exam_attempts')->restrictOnDelete();
            $table->foreign('exam_question_id', 'exam_attempt_questions_exam_question_id_foreign')
                ->references('id')->on('exam_questions')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_attempt_questions', function (Blueprint $table): void {
            $table->dropForeign('exam_attempt_questions_attempt_id_foreign');
            $table->dropForeign('exam_attempt_questions_exam_question_id_foreign');
        });

        Schema::table('exam_versions', function (Blueprint $table): void {
            $table->dropForeign('exam_versions_exam_id_foreign');
        });
    }
};
