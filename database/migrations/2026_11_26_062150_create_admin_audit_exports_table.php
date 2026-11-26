<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_exports', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('idempotency_key', 64);
            $table->string('status', 30)->default('queued');
            $table->json('filters')->nullable();
            $table->string('disk', 100)->nullable();
            $table->string('path', 500)->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['requested_by', 'status', 'requested_at'], 'admin_audit_exports_request_status_index');
            $table->unique(['requested_by', 'idempotency_key'], 'admin_audit_exports_request_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_exports');
    }
};
