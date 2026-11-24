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
        Schema::create('chat_threads', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('question_version_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('lesson_block_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('mode', 30)->default('tutor');
            $table->string('status', 30)->default('active');
            $table->string('title', 255)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('retention_expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status', 'last_message_at'], 'chat_threads_user_status_message_index');
            $table->index('retention_expires_at', 'chat_threads_retention_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
    }
};
