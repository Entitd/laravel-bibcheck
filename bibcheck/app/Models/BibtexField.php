<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibtexField extends Model
{
    protected $fillable = [
        'name_field',
        ];

    public function typeEntries(): BelongsToMany
    {
        return $this->belongsToMany(BibtexTypeEntry::class);
    }
}
