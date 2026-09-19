<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем администратора
        User::updateOrCreate(['email' => 'admin@bibcheck.local'], [
            'name' => 'Администратор',
            'password' => bcrypt('admin123'),
            'role' => User::ROLE_ADMIN,
            'is_guest' => false,
            'openalex_api_key' => null,
        ]);

        // Создаем обычного пользователя
        User::updateOrCreate(['email' => 'user@bibcheck.local'], [
            'name' => 'Пользователь',
            'password' => bcrypt('user123'),
            'role' => User::ROLE_USER,
            'is_guest' => false,
            'openalex_api_key' => 'test_api_key_12345',
        ]);

        // Создаем еще одного пользователя
        User::updateOrCreate(['email' => 'ivan@example.com'], [
            'name' => 'Иван Петров',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_USER,
            'is_guest' => false,
            'openalex_api_key' => null,
        ]);

        // Создаем тестового гостя (не просроченного)
        User::updateOrCreate(['email' => 'guest_test@guest.local'], [
            'name' => 'Гость_Тест',
            'password' => bcrypt(Str::random(32)),
            'role' => User::ROLE_GUEST,
            'is_guest' => true,
            'guest_expires_at' => now()->addHours(24),
        ]);

        // Создаем просроченного гостя (для тестирования очистки)
        User::updateOrCreate(['email' => 'guest_expired@guest.local'], [
            'name' => 'Гость_Просрочен',
            'password' => bcrypt(Str::random(32)),
            'role' => User::ROLE_GUEST,
            'is_guest' => true,
            'guest_expires_at' => now()->subHours(1),
        ]);
    }
}
