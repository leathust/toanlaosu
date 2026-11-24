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
        Schema::create('lesson_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->restrictOnDelete();
            $table->string('block_type', 30);
            $table->unsignedInteger('sort_order');
            $table->boolean('is_blocking')->default(true);
            $table->longText('body')->nullable();
            $table->foreignId('video_id')->nullable()->constrained('lesson_videos')->restrictOnDelete();
            $table->unsignedBigInteger('material_id')->nullable();
            $table->unsignedBigInteger('question_id')->nullable();
            $table->unsignedBigInteger('visual_scene_id')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['lesson_id', 'sort_order'], 'lesson_blocks_lesson_sort_unique');
            $table->index('video_id', 'lesson_blocks_video_index');
            $table->index('material_id', 'lesson_blocks_material_index');
            $table->index('question_id', 'lesson_blocks_question_index');
            $table->index('visual_scene_id', 'lesson_blocks_scene_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_blocks');
    }
};
