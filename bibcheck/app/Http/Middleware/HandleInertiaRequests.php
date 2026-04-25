<?php

namespace App\Http\Middleware;

use App\Services\GuestUserService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $recentChecks = $user?->recentChecks(10)->map(fn ($check) => [
            'id' => $check->id,
            'filename' => $check->filename,
            'formatted_date' => $check->formatted_date,
            'relative_time' => $check->relative_time,
            'total_entries' => $check->total_entries,
            'error_count' => $check->error_count,
            'warning_count' => $check->warning_count,
        ])->values();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'recentChecks' => $recentChecks,
            'guestSession' => $user && $user->is_guest
                ? app(GuestUserService::class)->getGuestSessionTimeRemaining($user)
                : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
