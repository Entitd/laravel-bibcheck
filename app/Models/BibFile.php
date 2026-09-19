<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibFile extends Model
{
    protected $fillable = ['user_id', 'filename', 'path', 'status', 'stats'];

    // Автоматически преобразуем JSON из базы в удобный PHP-массив
    protected $casts = [
        'stats' => 'array',
    ];

    /**
     * Связь: Один файл содержит много записей
     */
    public function entries(): HasMany
    {
        return $this->hasMany(BibEntry::class);
    }
}
