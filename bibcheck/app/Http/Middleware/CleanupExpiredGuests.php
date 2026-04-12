<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class CleanupExpiredGuests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Запускаем очистку просроченных гостевых аккаунтов (в продакшене лучше использовать задачи по расписанию)
        // Выполняем только один раз в час
        if (!cache()->has('guest_cleanup_run')) {
            User::cleanupExpiredGuests();
            cache()->put('guest_cleanup_run', true, now()->addHour());
        }

        return $next($request);
    }
}
