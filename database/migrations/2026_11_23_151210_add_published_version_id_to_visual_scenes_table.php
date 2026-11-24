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
        Schema::table('visual_scenes', function (Blueprint $table): void {
            $table->foreign('published_version_id', 'visual_scenes_published_version_fk')
                ->references('id')->on('visual_scene_versions')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visual_scenes', function (Blueprint $table): void {
            $table->dropForeign('visual_scenes_published_version_fk');
        });
    }
};
