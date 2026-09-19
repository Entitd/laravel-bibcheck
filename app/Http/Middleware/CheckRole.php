<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Проверяем, не истекла ли сессия гостя
        if ($user->isGuest() && $user->isGuestSessionExpired()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->with('error', 'Гостевая сессия истекла. Пожалуйста, войдите снова.');
        }

        // Проверяем роль
        if ($user->role !== $role) {
            abort(403, 'Доступ запрещен');
        }

        return $next($request);
    }
}
