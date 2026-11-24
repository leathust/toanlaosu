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
        Schema::create('user_mastery', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->decimal('mastery_score', 5, 2)->default(0);
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->timestamp('last_practiced_at')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->unsignedInteger('calculation_version')->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'skill_id'], 'user_mastery_user_skill_unique');
            $table->index(['user_id', 'next_review_at'], 'user_mastery_user_review_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_mastery');
    }
};
