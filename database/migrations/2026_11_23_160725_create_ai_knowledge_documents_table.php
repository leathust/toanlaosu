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
        Schema::create('ai_knowledge_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->unsignedInteger('source_version');
            $table->string('title', 255);
            $table->char('checksum', 64);
            $table->string('status', 30)->default('pending');
            $table->string('provider', 80)->nullable();
            $table->string('provider_document_id', 191)->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id', 'source_version'], 'ai_knowledge_documents_source_version_unique');
            $table->index(['status', 'updated_at'], 'ai_knowledge_documents_status_updated_index');
            $table->index('checksum', 'ai_knowledge_documents_checksum_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_documents');
    }
};
