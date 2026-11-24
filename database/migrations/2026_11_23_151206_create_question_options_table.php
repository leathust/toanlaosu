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
        Schema::create('question_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_version_id')->constrained()->cascadeOnDelete();
            $table->string('option_key', 30);
            $table->longText('content');
            $table->boolean('is_correct')->default(false);
            $table->decimal('score', 10, 4)->nullable();
            $table->text('feedback')->nullable();
            $table->unsignedInteger('sort_order');
            $table->timestamps();
            $table->unique(['question_version_id', 'option_key'], 'question_options_version_key_unique');
            $table->unique(['question_version_id', 'sort_order'], 'question_options_version_sort_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
