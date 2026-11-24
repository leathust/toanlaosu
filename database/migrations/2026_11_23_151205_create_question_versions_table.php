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
        Schema::create('question_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->unsignedInteger('version');
            $table->longText('stem');
            $table->longText('solution')->nullable();
            $table->json('answer_config')->nullable();
            $table->json('scoring_policy')->nullable();
            $table->boolean('allow_negative')->default(false);
            $table->char('content_hash', 64)->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['question_id', 'version'], 'question_versions_question_version_unique');
            $table->index('content_hash', 'question_versions_hash_index');
            $table->index(['status', 'published_at'], 'question_versions_status_published_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_versions');
    }
};
