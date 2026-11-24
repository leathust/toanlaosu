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
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('email');
            $table->string('timezone', 64)->default('Asia/Ho_Chi_Minh')->after('status');
            $table->json('preferences')->nullable()->after('timezone');
            $table->timestamp('locked_at')->nullable()->after('preferences');
            $table->timestamp('last_login_at')->nullable()->after('locked_at');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->index(['status', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status', 'id']);
            $table->dropColumn([
                'status',
                'timezone',
                'preferences',
                'locked_at',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
