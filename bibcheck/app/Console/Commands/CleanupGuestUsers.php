<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CleanupGuestUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-guests {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired guest user accounts from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Очистка просроченных гостевых аккаунтов...');

        $query = User::where('is_guest', true)
            ->where('guest_expires_at', '<', now());

        $count = $query->count();

        if ($count === 0) {
            $this->info('✅ Просроченных гостевых аккаунтов не найдено.');
            return Command::SUCCESS;
        }

        $this->warn("Найдено просроченных аккаунтов: {$count}");

        if (!$this->option('force')) {
            if (!$this->confirm('Вы уверены, что хотите удалить эти аккаунты?')) {
                $this->info('❌ Операция отменена.');
                return Command::SUCCESS;
            }
        }

        $deleted = $query->delete();

        $this->info("✅ Удалено аккаунтов: {$deleted}");

        return Command::SUCCESS;
    }
}
