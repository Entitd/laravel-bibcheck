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
        Schema::create('check_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->text('content')->nullable(); // содержимое BibTeX файла
            $table->string('status')->default('completed'); // completed, pending, failed
            $table->json('stats')->nullable(); // статистика проверки
            $table->json('analysis_data')->nullable(); // полные результаты проверки (errors, entries, metrics и т.д.)
            $table->text('verdict')->nullable(); // текстовый вердикт
            $table->integer('total_entries')->default(0);
            $table->integer('error_count')->default(0);
            $table->integer('warning_count')->default(0);
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_history');
    }
};
