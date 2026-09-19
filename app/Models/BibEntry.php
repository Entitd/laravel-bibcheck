<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibEntry extends Model
{
    protected $fillable = [
        'bib_file_id',
        'type',
        'cite_key',
        'raw_content',
        'parsed_data',
        'is_valid'
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'is_valid' => 'boolean',
    ];

    public function bibFile(): BelongsTo
    {
        return $this->belongsTo(BibFile::class);
    }

    /**
     * Связь: У одной записи может быть много ошибок валидации
     */
    public function validationErrors(): HasMany
    {
        return $this->hasMany(ValidationError::class);
    }
}
