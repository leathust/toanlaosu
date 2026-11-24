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
        Schema::create('lesson_block_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_block_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('locked');
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('seconds_spent')->default(0);
            $table->unsignedInteger('hints_used')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lesson_block_id'], 'lesson_block_progress_user_block_unique');
            $table->index(['user_id', 'status', 'last_interaction_at'], 'lesson_block_progress_user_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_block_progress');
    }
};
