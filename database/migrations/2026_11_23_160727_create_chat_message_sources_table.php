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
        Schema::create('chat_message_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chat_message_id')->constrained()->restrictOnDelete();
            $table->foreignId('ai_knowledge_chunk_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('citation_order');
            $table->string('label', 255);
            $table->text('quoted_excerpt')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['chat_message_id', 'citation_order'], 'chat_message_sources_message_order_unique');
            $table->unique(['chat_message_id', 'ai_knowledge_chunk_id'], 'chat_message_sources_message_chunk_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_message_sources');
    }
};
