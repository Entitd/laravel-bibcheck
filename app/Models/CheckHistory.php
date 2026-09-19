<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckHistory extends Model
{
    protected $table = 'check_history';

    protected $fillable = [
        'user_id',
        'filename',
        'content',
        'status',
        'stats',
        'analysis_data',
        'verdict',
        'total_entries',
        'error_count',
        'warning_count',
    ];

    protected $casts = [
        'stats' => 'array',
        'analysis_data' => 'array',
        'total_entries' => 'integer',
        'error_count' => 'integer',
        'warning_count' => 'integer',
    ];

    /**
     * Связь: Проверка принадлежит пользователю
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Последние проверки пользователя
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Только последние N записей
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Получить форматированную дату
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('d.m.Y H:i');
    }

    /**
     * Получить относительное время ("2 часа назад")
     */
    public function getRelativeTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
