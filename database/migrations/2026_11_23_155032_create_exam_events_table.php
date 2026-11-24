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
        Schema::create('exam_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->restrictOnDelete();
            $table->string('event_type', 80);
            $table->timestamp('client_occurred_at')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->index(['exam_attempt_id', 'occurred_at'], 'exam_events_attempt_occurred_index');
            $table->index(['event_type', 'occurred_at'], 'exam_events_type_occurred_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_events');
    }
};
