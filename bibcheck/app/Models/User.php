<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    // Роли пользователей
    const ROLE_ADMIN = 'admin';
    const ROLE_USER = 'user';
    const ROLE_GUEST = 'guest';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'openalex_api_key',
        'is_guest',
        'guest_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_guest' => 'boolean',
            'guest_expires_at' => 'datetime',
        ];
    }

    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Проверка, является ли пользователь обычным пользователем
     */
    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Проверка, является ли пользователь гостем
     */
    public function isGuest(): bool
    {
        return $this->is_guest === true || $this->role === self::ROLE_GUEST;
    }

    /**
     * Проверка, не истекла ли сессия гостя
     */
    public function isGuestSessionExpired(): bool
    {
        if (!$this->is_guest) {
            return false;
        }

        return $this->guest_expires_at && Carbon::now()->greaterThan($this->guest_expires_at);
    }

    /**
     * Проверка, действителен ли гостевой аккаунт
     */
    public function isValidGuest(): bool
    {
        return $this->isGuest() && !$this->isGuestSessionExpired();
    }

    /**
     * Получить срок действия гостевой сессии
     */
    public function getGuestExpiresAtAttribute(): ?Carbon
    {
        return $this->attributes['guest_expires_at'] 
            ? Carbon::parse($this->attributes['guest_expires_at']) 
            : null;
    }

    /**
     * Scope для получения только гостевых пользователей
     */
    public function scopeGuests($query)
    {
        return $query->where('is_guest', true);
    }

    /**
     * Scope для получения только активных (не гостевых) пользователей
     */
    public function scopeActive($query)
    {
        return $query->where('is_guest', false);
    }

    /**
     * Создать гостевого пользователя
     */
    public static function createGuest(): self
    {
        return self::create([
            'name' => 'Гость_' . Str::random(8),
            'email' => 'guest_' . Str::random(16) . '@guest.local',
            'password' => bcrypt(Str::random(32)),
            'role' => self::ROLE_GUEST,
            'is_guest' => true,
            'guest_expires_at' => Carbon::now()->addHours(24), // Сессия на 24 часа
        ]);
    }

    /**
     * Связь: У пользователя много проверок
     */
    public function checkHistory(): HasMany
    {
        return $this->hasMany(CheckHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Получить последние N проверок пользователя
     */
    public function recentChecks(int $limit = 10)
    {
        return $this->checkHistory()->limit($limit)->get();
    }

    /**
     * Удалить просроченные гостевые аккаунты
     */
    public static function cleanupExpiredGuests(): int
    {
        return self::where('is_guest', true)
            ->where('guest_expires_at', '<', Carbon::now())
            ->delete();
    }
}
