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
        Schema::table('lesson_block_progress', function (Blueprint $table): void {
            $table->index(['status', 'completed_at'], 'lesson_block_progress_status_completed_index');
            $table->index('last_interaction_at', 'lesson_block_progress_interaction_index');
        });

        Schema::table('user_attempts', function (Blueprint $table): void {
            $table->index(['submitted_at', 'is_correct'], 'user_attempts_submitted_correct_index');
        });

        Schema::table('ai_usage_logs', function (Blueprint $table): void {
            $table->index(['occurred_at', 'status'], 'ai_usage_logs_occurred_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table): void {
            $table->dropIndex('ai_usage_logs_occurred_status_index');
        });

        Schema::table('user_attempts', function (Blueprint $table): void {
            $table->dropIndex('user_attempts_submitted_correct_index');
        });

        Schema::table('lesson_block_progress', function (Blueprint $table): void {
            $table->dropIndex('lesson_block_progress_status_completed_index');
            $table->dropIndex('lesson_block_progress_interaction_index');
        });
    }
};
