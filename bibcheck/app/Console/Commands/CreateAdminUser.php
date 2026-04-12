<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin 
                            {--name= : Admin user name} 
                            {--email= : Admin email} 
                            {--password= : Admin password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('👤 Создание аккаунта администратора...');

        $name = $this->option('name') ?? $this->ask('Введите имя администратора');
        $email = $this->option('email') ?? $this->ask('Введите email администратора');
        $password = $this->option('password') ?? $this->secret('Введите пароль');

        // Проверка валидации email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Некорректный email адрес.');
            return Command::FAILURE;
        }

        // Проверка длины пароля
        if (strlen($password) < 8) {
            $this->error('❌ Пароль должен содержать минимум 8 символов.');
            return Command::FAILURE;
        }

        // Проверка существования пользователя
        if (User::where('email', $email)->exists()) {
            $this->error('❌ Пользователь с таким email уже существует.');
            return Command::FAILURE;
        }

        // Создание администратора
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
            'is_guest' => false,
            'email_verified_at' => now(),
        ]);

        $this->info('✅ Администратор успешно создан!');
        $this->table(
            ['ID', 'Имя', 'Email', 'Роль'],
            [[
                $user->id,
                $user->name,
                $user->email,
                'admin',
            ]]
        );

        return Command::SUCCESS;
    }
}
