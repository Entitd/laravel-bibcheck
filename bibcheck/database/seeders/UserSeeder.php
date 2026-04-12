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
        User::factory()->create([
            'name' => 'Администратор',
            'email' => 'admin@bibcheck.local',
            'password' => bcrypt('admin123'),
            'role' => User::ROLE_ADMIN,
            'is_guest' => false,
            'openalex_api_key' => null,
        ]);

        // Создаем обычного пользователя
        User::factory()->create([
            'name' => 'Пользователь',
            'email' => 'user@bibcheck.local',
            'password' => bcrypt('user123'),
            'role' => User::ROLE_USER,
            'is_guest' => false,
            'openalex_api_key' => 'test_api_key_12345',
        ]);

        // Создаем еще одного пользователя
        User::factory()->create([
            'name' => 'Иван Петров',
            'email' => 'ivan@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_USER,
            'is_guest' => false,
            'openalex_api_key' => null,
        ]);

        // Создаем тестового гостя (не просроченного)
        User::factory()->create([
            'name' => 'Гость_Тест',
            'email' => 'guest_test@guest.local',
            'password' => bcrypt(Str::random(32)),
            'role' => User::ROLE_GUEST,
            'is_guest' => true,
            'guest_expires_at' => now()->addHours(24),
        ]);

        // Создаем просроченного гостя (для тестирования очистки)
        User::factory()->create([
            'name' => 'Гость_Просрочен',
            'email' => 'guest_expired@guest.local',
            'password' => bcrypt(Str::random(32)),
            'role' => User::ROLE_GUEST,
            'is_guest' => true,
            'guest_expires_at' => now()->subHours(1),
        ]);
    }
}
