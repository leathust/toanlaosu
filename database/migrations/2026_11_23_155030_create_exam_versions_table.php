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
        Schema::create('exam_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->unsignedInteger('version');
            $table->json('settings_snapshot')->nullable();
            $table->decimal('total_points', 10, 4)->default(0);
            $table->string('status', 30)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['exam_id', 'version'], 'exam_versions_exam_version_unique');
            $table->index(['status', 'published_at'], 'exam_versions_status_published_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_versions');
    }
};
