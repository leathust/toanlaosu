<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 120);
            $table->string('auditable_type', 120)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('retention_expires_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id', 'occurred_at'], 'audit_logs_auditable_occurred_index');
            $table->index(['actor_id', 'occurred_at'], 'audit_logs_actor_occurred_index');
            $table->index(['event', 'occurred_at'], 'audit_logs_event_occurred_index');
            $table->index('retention_expires_at', 'audit_logs_retention_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
