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
        Schema::create('video_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_video_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('last_position_seconds')->default(0);
            $table->json('watched_ranges')->nullable();
            $table->unsignedInteger('watched_seconds')->default(0);
            $table->decimal('completion_percent', 5, 2)->default(0);
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lesson_video_id'], 'video_progress_user_video_unique');
            $table->index(['user_id', 'completed_at'], 'video_progress_user_completed_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_progress');
    }
};
