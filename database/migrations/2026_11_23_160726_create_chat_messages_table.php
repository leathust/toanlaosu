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
        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('chat_thread_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('role', 30);
            $table->string('status', 30)->default('completed');
            $table->longText('content');
            $table->string('model', 100)->nullable();
            $table->string('provider_message_id', 191)->nullable()->unique();
            $table->json('tool_calls')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['chat_thread_id', 'created_at', 'id'], 'chat_messages_thread_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
