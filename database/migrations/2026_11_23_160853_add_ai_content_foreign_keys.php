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
        Schema::table('ai_knowledge_chunks', function (Blueprint $table): void {
            $table->foreign('document_id', 'ai_knowledge_chunks_document_id_foreign')
                ->references('id')->on('ai_knowledge_documents')->restrictOnDelete();
        });

        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->foreign('chat_thread_id', 'chat_messages_thread_id_foreign')
                ->references('id')->on('chat_threads')->restrictOnDelete();
            $table->foreign('parent_id', 'chat_messages_parent_id_foreign')
                ->references('id')->on('chat_messages')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropForeign('chat_messages_parent_id_foreign');
            $table->dropForeign('chat_messages_thread_id_foreign');
        });

        Schema::table('ai_knowledge_chunks', function (Blueprint $table): void {
            $table->dropForeign('ai_knowledge_chunks_document_id_foreign');
        });
    }
};
