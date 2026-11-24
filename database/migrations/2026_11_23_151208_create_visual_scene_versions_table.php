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
        Schema::create('visual_scene_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('visual_scene_id');
            $table->unsignedInteger('version');
            $table->string('schema_version', 50);
            $table->json('config');
            $table->json('steps')->nullable();
            $table->json('allowed_commands')->nullable();
            $table->string('fallback_disk', 100)->nullable();
            $table->string('fallback_path', 500)->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['visual_scene_id', 'version'], 'visual_scene_versions_scene_version_unique');
            $table->index(['status', 'published_at'], 'visual_scene_versions_status_published_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visual_scene_versions');
    }
};
