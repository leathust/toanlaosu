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
        Schema::create('question_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_version_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order');
            $table->string('step_type', 50);
            $table->longText('prompt');
            $table->json('answer_config')->nullable();
            $table->longText('hint')->nullable();
            $table->longText('explanation')->nullable();
            $table->unsignedBigInteger('visual_scene_version_id')->nullable();
            $table->timestamps();
            $table->unique(['question_version_id', 'sort_order'], 'question_steps_version_sort_unique');
            $table->index('visual_scene_version_id', 'question_steps_scene_version_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_steps');
    }
};
