<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class GuestUserService
{
    /**
     * Создать гостевого пользователя и авторизовать его
     */
    public function createAndLoginGuest(): User
    {
        // Если уже авторизован как гость, вернем его
        $currentUser = Auth::user();
        if ($currentUser && $currentUser->isValidGuest()) {
            return $currentUser;
        }

        // Создаем нового гостя
        $guest = User::createGuest();
        
        // Авторизуем
        Auth::login($guest);

        return $guest;
    }

    /**
     * Получить информацию о оставшемся времени гостевой сессии
     */
    public function getGuestSessionTimeRemaining(?User $user): ?array
    {
        if (!$user || !$user->isValidGuest()) {
            return null;
        }

        $expiresAt = $user->guest_expires_at;
        if (!$expiresAt) {
            return null;
        }

        $now = Carbon::now();
        $remaining = $expiresAt->getTimestamp() - $now->getTimestamp();

        if ($remaining <= 0) {
            return null;
        }

        return [
            'expires_at' => $expiresAt->toDateTimeString(),
            'remaining_seconds' => $remaining,
            'remaining_formatted' => $this->formatDuration($remaining),
            'is_expiring_soon' => $remaining < 3600, // Меньше часа
        ];
    }

    /**
     * Продлить гостевую сессию
     */
    public function extendGuestSession(User $user, int $hours = 24): User
    {
        if (!$user->is_guest) {
            throw new \InvalidArgumentException('Пользователь не является гостем');
        }

        $user->guest_expires_at = Carbon::now()->addHours($hours);
        $user->save();

        return $user;
    }

    /**
     * Конвертировать гостя в зарегистрированного пользователя
     */
    public function convertGuestToUser(User $guest, string $name, string $email, string $password): User
    {
        if (!$guest->is_guest) {
            throw new \InvalidArgumentException('Пользователь не является гостем');
        }

        // Проверяем, не занят ли email
        if (User::where('email', $email)->where('id', '!=', $guest->id)->exists()) {
            throw new \InvalidArgumentException('Email уже занят');
        }

        $guest->update([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'role' => User::ROLE_USER,
            'is_guest' => false,
            'guest_expires_at' => null,
        ]);

        return $guest;
    }

    /**
     * Форматировать длительность
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d ч %d мин', $hours, $minutes);
        } elseif ($minutes > 0) {
            return sprintf('%d мин %d сек', $minutes, $secs);
        }

        return sprintf('%d сек', $secs);
    }
}
