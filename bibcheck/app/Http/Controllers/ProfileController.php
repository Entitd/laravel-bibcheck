<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GuestUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        protected GuestUserService $guestService
    ) {}

    /**
     * Показать страницу профиля
     */
    public function show(): Response
    {
        $user = auth()->user();
        
        return Inertia::render('profile/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'openalex_api_key' => $user->openalex_api_key,
                'is_guest' => $user->is_guest,
                'guest_expires_at' => $user->guest_expires_at?->toDateTimeString(),
                'guest_session' => $user->is_guest ? $this->guestService->getGuestSessionTimeRemaining($user) : null,
            ],
        ]);
    }

    /**
     * Обновить API ключ OpenAlex
     */
    public function updateApiKey(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'openalex_api_key' => 'nullable|string|max:255',
        ]);

        $user->update([
            'openalex_api_key' => $validated['openalex_api_key'],
        ]);

        return back()->with('success', 'API ключ успешно обновлен');
    }

    /**
     * Удалить API ключ
     */
    public function deleteApiKey()
    {
        $user = auth()->user();
        $user->update(['openalex_api_key' => null]);

        return back()->with('success', 'API ключ удален');
    }

    /**
     * Обновить имя пользователя
     */
    public function updateName(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user->update($validated);

        return back()->with('success', 'Имя успешно обновлено');
    }

    /**
     * Обновить пароль (только для не-гостей)
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        if ($user->is_guest) {
            return back()->withErrors([
                'password' => 'Гости не могут изменять пароль. Зарегистрируйтесь для этого.',
            ]);
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Текущий пароль указан неверно',
            ]);
        }

        $user->update([
            'password' => bcrypt($validated['password']),
        ]);

        return back()->with('success', 'Пароль успешно обновлен');
    }

    /**
     * Войти как гость
     */
    public function loginAsGuest()
    {
        $guest = $this->guestService->createAndLoginGuest();

        return redirect()->intended('/')
            ->with('success', 'Вы вошли как гость. Сессия истекает через 24 часа.');
    }

    /**
     * Продлить гостевую сессию
     */
    public function extendGuestSession(Request $request)
    {
        $user = auth()->user();

        if (!$user->is_guest) {
            return back()->withErrors([
                'session' => 'Только гости могут продлевать сессию',
            ]);
        }

        $this->guestService->extendGuestSession($user, 24);

        return back()->with('success', 'Сессия продлена на 24 часа');
    }

    /**
     * Зарегистрироваться вместо гостя
     */
    public function registerFromGuest(Request $request)
    {
        $user = auth()->user();

        if (!$user->is_guest) {
            return back()->withErrors([
                'register' => 'Только гости могут использовать эту функцию',
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $this->guestService->convertGuestToUser(
                $user,
                $validated['name'],
                $validated['email'],
                $validated['password']
            );

            return redirect('/dashboard')
                ->with('success', 'Поздравляем! Вы успешно зарегистрировались.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }
    }
}
