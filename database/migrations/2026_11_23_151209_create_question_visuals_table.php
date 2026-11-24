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
        Schema::create('question_visuals', function (Blueprint $table): void {
            $table->foreignId('question_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('visual_scene_version_id')->constrained()->restrictOnDelete();
            $table->string('purpose', 50);
            $table->unsignedInteger('sort_order');
            $table->primary(['question_version_id', 'visual_scene_version_id', 'purpose']);
            $table->unique(['question_version_id', 'sort_order'], 'question_visuals_version_sort_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_visuals');
    }
};
