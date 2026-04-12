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
        Schema::table('check_history', function (Blueprint $table) {
            if (!Schema::hasColumn('check_history', 'analysis_data')) {
                $table->json('analysis_data')->nullable()->after('stats');
            }
            if (!Schema::hasColumn('check_history', 'verdict')) {
                $table->text('verdict')->nullable()->after('analysis_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('check_history', function (Blueprint $table) {
            $table->dropColumn(['analysis_data', 'verdict']);
        });
    }
};
