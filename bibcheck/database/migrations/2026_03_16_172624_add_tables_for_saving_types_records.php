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
        // Таблица типов
        Schema::create('bibtex_type_entries', function (Blueprint $table) {
            $table->id();
            $table->string('name_type_entry')->unique();
            $table->timestamps();
        });

        // Таблица уникальных полей
        Schema::create('bibtex_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name_field')->unique();
            $table->timestamps();
        });

        // Таблица связей
        Schema::create('bibtex_field_bibtex_type_entry', function (Blueprint $table) {
            $table->foreignId('bibtex_type_entry_id')
                ->constrained('bibtex_type_entries')
                ->onDelete('cascade');

            $table->foreignId('bibtex_field_id')
                ->constrained('bibtex_fields')
                ->onDelete('cascade');

            // Дополнительные свойства связи
            $table->integer('sort_order')->default(0);

            // Составной первичный ключ (чтобы нельзя было привязать одно поле к типу дважды)
            $table->primary(['bibtex_type_entry_id', 'bibtex_field_id'], 'type_field_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bibtex_field_bibtex_type_entry');
        Schema::dropIfExists('bibtex_fields');
        Schema::dropIfExists('bibtex_type_entries');
    }
};
