<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseRequirement extends Model
{
    use HasFactory;

    /**
     * Поля, которые разрешено массово заполнять (через Seeder или форму).
     */
    protected $fillable = [
        'course_number',             // Номер курса (1, 2, 3, 4)
        'min_total_quantity',        // Общее кол-во источников
        'min_foreign_lang',          // Мин. иностранных (hyphenation = english)
        'min_current_periodicals',   // Мин. периодики (article и т.д.)
        'min_21st_century',          // Мин. литературы 21 века (year >= 2001)
    ];

    /**
     * Чтобы поля всегда возвращались как числа (integer),
     * можно добавить кастинг, хотя Laravel обычно делает это сам для id и интов.
     */
    protected $casts = [
        'course_number' => 'integer',
        'min_total_quantity' => 'integer',
        'min_foreign_lang' => 'integer',
        'min_current_periodicals' => 'integer',
        'min_21st_century' => 'integer',
    ];
}
