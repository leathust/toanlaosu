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
        Schema::create('lesson_videos', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 200);
            $table->string('provider', 50);
            $table->string('provider_asset_id', 200)->nullable();
            $table->string('disk', 100)->nullable();
            $table->string('path', 500)->nullable();
            $table->string('status', 30)->default('uploading');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('processing_error')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['provider', 'provider_asset_id'], 'lesson_videos_provider_asset_unique');
            $table->index(['status', 'updated_at'], 'lesson_videos_status_updated_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_videos');
    }
};
