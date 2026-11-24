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
        Schema::table('question_versions', function (Blueprint $table): void {
            $table->foreign('question_id', 'question_versions_question_id_foreign')
                ->references('id')->on('questions')->restrictOnDelete();
        });

        Schema::table('question_steps', function (Blueprint $table): void {
            $table->foreign('visual_scene_version_id', 'question_steps_scene_version_fk')
                ->references('id')->on('visual_scene_versions')->restrictOnDelete();
        });

        Schema::table('visual_scene_versions', function (Blueprint $table): void {
            $table->foreign('visual_scene_id', 'visual_scene_versions_scene_id_foreign')
                ->references('id')->on('visual_scenes')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_steps', function (Blueprint $table): void {
            $table->dropForeign('question_steps_scene_version_fk');
        });

        Schema::table('visual_scene_versions', function (Blueprint $table): void {
            $table->dropForeign('visual_scene_versions_scene_id_foreign');
        });

        Schema::table('question_versions', function (Blueprint $table): void {
            $table->dropForeign('question_versions_question_id_foreign');
        });
    }
};
