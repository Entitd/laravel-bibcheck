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
        Schema::create('bib_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('path');
            $table->string('status')->default('pending');
            $table->json('stats')->nullable();
            $table->timestamps();
        });

        Schema::create('bib_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bib_file_id')->constrained()->onDelete('cascade');
            $table->string('type'); // article, book и т.д.
            $table->string('cite_key');
            $table->text('raw_content'); // исходная строка из bib файла
            $table->json('parsed_data'); // распарсенные поля
            $table->boolean('is_valid')->default(true);
            $table->timestamps();
        });

        Schema::create('validation_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bib_entry_id')->constrained()->onDelete('cascade');
            $table->string('field')->nullable();
            $table->string('error_type');
            $table->text('message');
            $table->string('severity')->default('error'); // error, warning
            $table->timestamps();
        });

        Schema::create('course_requirements', function (Blueprint $table) {
            $table->id();
            $table->integer('course_number');
            $table->integer('min_total_quantity');
            $table->integer('min_foreign_lang');
            $table->integer('min_current_periodicals');
            $table->integer('min_21st_century');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bib_tables');
    }
};
