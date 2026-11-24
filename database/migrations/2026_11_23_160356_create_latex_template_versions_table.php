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
        Schema::create('latex_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('latex_template_id');
            $table->unsignedInteger('version');
            $table->longText('preamble')->nullable();
            $table->longText('header')->nullable();
            $table->longText('question_format');
            $table->longText('solution_format')->nullable();
            $table->longText('footer')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['latex_template_id', 'version'], 'latex_template_versions_template_version_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('latex_template_versions');
    }
};
