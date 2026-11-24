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
        Schema::table('latex_template_versions', function (Blueprint $table): void {
            $table->foreign('latex_template_id', 'latex_template_versions_template_id_foreign')
                ->references('id')->on('latex_templates')->restrictOnDelete();
        });

        Schema::table('latex_templates', function (Blueprint $table): void {
            $table->foreign('active_version_id', 'latex_templates_active_version_fk')
                ->references('id')->on('latex_template_versions')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('latex_templates', function (Blueprint $table): void {
            $table->dropForeign('latex_templates_active_version_fk');
        });

        Schema::table('latex_template_versions', function (Blueprint $table): void {
            $table->dropForeign('latex_template_versions_template_id_foreign');
        });
    }
};
