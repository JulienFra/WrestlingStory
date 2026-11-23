<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * role: "user" or "admin" (App\Enums\UserRole); tier: "free" or "pro" (App\Enums\Tier).
     * Existing users become simple players on the free tier.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user');
            $table->string('tier')->default('free');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'tier']);
        });
    }
};
