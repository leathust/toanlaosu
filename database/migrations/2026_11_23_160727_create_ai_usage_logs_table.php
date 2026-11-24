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
        Schema::create('ai_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('chat_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 80);
            $table->string('model', 100);
            $table->string('operation', 80);
            $table->string('status', 30);
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->decimal('cost', 14, 6)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('retention_expires_at')->nullable();
            $table->index(['user_id', 'occurred_at'], 'ai_usage_logs_user_occurred_index');
            $table->index(['provider', 'model', 'occurred_at'], 'ai_usage_logs_provider_model_index');
            $table->index(['status', 'occurred_at'], 'ai_usage_logs_status_occurred_index');
            $table->index('retention_expires_at', 'ai_usage_logs_retention_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
