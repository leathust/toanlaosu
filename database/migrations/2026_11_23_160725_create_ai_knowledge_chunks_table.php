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
        Schema::create('ai_knowledge_chunks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedInteger('chunk_index');
            $table->longText('content');
            $table->unsignedInteger('token_count')->nullable();
            $table->char('content_hash', 64);
            $table->string('external_vector_id', 191)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'chunk_index'], 'ai_knowledge_chunks_document_index_unique');
            $table->index('content_hash', 'ai_knowledge_chunks_hash_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_chunks');
    }
};
