<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->foreignId('assigned_reviewer_id')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->index(['assigned_reviewer_id', 'status', 'updated_at'], 'courses_reviewer_status_updated_index');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->foreignId('assigned_reviewer_id')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->index(['assigned_reviewer_id', 'status', 'updated_at'], 'questions_reviewer_status_updated_index');
        });

        Schema::table('visual_scenes', function (Blueprint $table): void {
            $table->foreignId('assigned_reviewer_id')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->index(['assigned_reviewer_id', 'status', 'updated_at'], 'visual_scenes_reviewer_status_updated_index');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropForeign(['assigned_reviewer_id']);
            $table->dropIndex('courses_reviewer_status_updated_index');
            $table->dropColumn('assigned_reviewer_id');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropForeign(['assigned_reviewer_id']);
            $table->dropIndex('questions_reviewer_status_updated_index');
            $table->dropColumn('assigned_reviewer_id');
        });

        Schema::table('visual_scenes', function (Blueprint $table): void {
            $table->dropForeign(['assigned_reviewer_id']);
            $table->dropIndex('visual_scenes_reviewer_status_updated_index');
            $table->dropColumn('assigned_reviewer_id');
        });
    }
};
