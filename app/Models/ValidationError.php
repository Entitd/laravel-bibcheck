<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationError extends Model
{
    protected $fillable = [
        'bib_entry_id',
        'field',
        'error_type',
        'message',
        'severity'
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(BibEntry::class, 'bib_entry_id');
    }
}
