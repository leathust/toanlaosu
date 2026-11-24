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
        Schema::create('export_jobs', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('exam_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('latex_template_version_id')->constrained()->restrictOnDelete();
            $table->string('mode', 30);
            $table->string('status', 30)->default('queued');
            $table->json('options')->nullable();
            $table->string('idempotency_key', 191)->unique();
            $table->string('disk', 100)->nullable();
            $table->string('path', 500)->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['requested_by', 'status', 'requested_at'], 'export_jobs_request_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
    }
};
