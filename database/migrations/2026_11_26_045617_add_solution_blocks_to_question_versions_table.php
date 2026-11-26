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
            $table->json('solution_blocks')->nullable()->after('solution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_versions', function (Blueprint $table): void {
            $table->dropColumn('solution_blocks');
        });
    }
};
