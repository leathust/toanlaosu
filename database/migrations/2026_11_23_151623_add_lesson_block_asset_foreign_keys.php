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
        Schema::table('lesson_blocks', function (Blueprint $table): void {
            $table->foreign('material_id', 'lesson_blocks_material_id_foreign')
                ->references('id')->on('lesson_materials')->restrictOnDelete();
            $table->foreign('question_id', 'lesson_blocks_question_id_foreign')
                ->references('id')->on('questions')->restrictOnDelete();
            $table->foreign('visual_scene_id', 'lesson_blocks_visual_scene_id_foreign')
                ->references('id')->on('visual_scenes')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_blocks', function (Blueprint $table): void {
            $table->dropForeign('lesson_blocks_material_id_foreign');
            $table->dropForeign('lesson_blocks_question_id_foreign');
            $table->dropForeign('lesson_blocks_visual_scene_id_foreign');
        });
    }
};
