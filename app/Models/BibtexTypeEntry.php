<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BibtexTypeEntry extends Model
{
    protected $fillable = [
        'name_type_entry',
    ];

    public function fields(): BelongsToMany
    {
        // Для сопоставления ключей bibtex_type_entry_id / bibtex_field_id
        return $this->belongsToMany(BibtexField::class)->withPivot('sort_order');
    }

}
