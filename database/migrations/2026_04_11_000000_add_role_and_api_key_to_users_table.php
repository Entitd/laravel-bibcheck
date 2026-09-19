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
            $table->string('role')->default('user')->after('email');
            $table->string('openalex_api_key')->nullable()->after('role');
            $table->boolean('is_guest')->default(false)->after('openalex_api_key');
            $table->timestamp('guest_expires_at')->nullable()->after('is_guest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'openalex_api_key', 'is_guest', 'guest_expires_at']);
        });
    }
};
