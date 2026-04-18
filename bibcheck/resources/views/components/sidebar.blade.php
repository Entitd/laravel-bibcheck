@php
    $user = auth()->user();
    $recentChecks = $user ? $user->recentChecks(10) : collect();
    $guestHoursRemaining = null;
    $guestExpiringSoon = false;

    if ($user && $user->isGuest() && $user->guest_expires_at) {
        $guestSecondsRemaining = now()->diffInSeconds($user->guest_expires_at, false);
        $guestHoursRemaining = max(1, (int) ceil(max($guestSecondsRemaining, 0) / 3600));
        $guestExpiringSoon = $guestSecondsRemaining < 3600;
    }
@endphp

<aside class="app-sidebar flex shrink-0 flex-col border-r border-[#dce5d6] bg-[#e4efdc] p-4">
    <div class="app-sidebar-inner flex h-full flex-col">
        <!-- <div > -->
            <a href="\" class="sidebar-brand mb-6 flex items-center gap-2 px-2 pt-1">
            <div class="sidebar-icon-slot flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#0b6b34] text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <span class="sidebar-brand-text text-lg font-extrabold tracking-tight text-[#15713d]">BIBCHECK.RU</span>
            </a>
        <!-- </div> -->

        <div class="sidebar-search relative mb-5">
            <span class="absolute inset-y-0 left-3 flex items-center text-[#a3aea5]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </span>
            <input
                type="text"
                id="fileSearch"
                placeholder="Найти проверку"
                class="w-full rounded-full border border-[#d8e2d4] bg-[#f8fbf5] py-2.5 pl-10 pr-4 text-sm text-slate-700 outline-none transition focus:border-[#b8c8b9] focus:bg-white"
            >
        </div>

        <nav class="flex-1 overflow-y-auto">
            <button type="button" class="sidebar-section mb-3 flex w-full items-center justify-between rounded-xl px-2 py-2 text-left text-sm font-semibold text-[#6d7c70]">
                <span class="flex items-center gap-2">
                    <span class="sidebar-section-text">Недавнее</span>
                </span>
                <svg class="sidebar-section-arrow h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            @if($user)
                <div class="space-y-1" id="checkHistoryList">
                    @forelse($recentChecks as $check)
                        <div
                            class="check-history-item group relative cursor-pointer rounded-xl px-2 py-2.5 text-sm transition hover:bg-[#edf5e7]"
                            data-filename="{{ $check->filename }}"
                            data-created="{{ $check->created_at->format('d.m.Y H:i') }}"
                            onclick="window.location.href='{{ route('check-history.show', $check->id) }}'"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="sidebar-item-title truncate text-sm font-medium text-[#738177]">
                                        {{ $check->filename }}
                                    </div>
                                    <div class="sidebar-item-meta mt-1 text-xs text-[#98a39a]">
                                        {{ $check->relative_time }}
                                    </div>
                                    @if($check->total_entries > 0)
                                        <div class="sidebar-item-meta mt-1.5 flex items-center gap-2 text-[11px] text-[#98a39a]">
                                            <span>{{ $check->total_entries }} записей</span>
                                            @if($check->error_count > 0)
                                                <span>{{ $check->error_count }} ошибок</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <form action="{{ route('check-history.destroy', $check->id) }}" method="POST" class="sidebar-item-delete inline" onsubmit="return confirm('Удалить эту запись из истории?');">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="opacity-0 transition group-hover:opacity-100 text-[#a2aca4] hover:text-red-600"
                                        title="Удалить из истории"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="sidebar-empty rounded-2xl bg-[#eef5e8] px-4 py-4 text-sm text-[#8a978d]">
                            История проверок пока пуста.
                        </div>
                    @endforelse
                </div>
            @else
                <div class="sidebar-empty rounded-2xl bg-[#eef5e8] px-4 py-4 text-sm text-[#8a978d]">
                    Войдите, чтобы сохранять историю проверок.
                </div>
            @endif
        </nav>

        <div class="mt-4 pt-4">
            @if($user)
                <div class="sidebar-profile rounded-[1.4rem] px-3 py-3">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            data-sidebar-expand-target="profileMenu"
                            class="sidebar-profile-trigger flex min-w-0 flex-1 items-center gap-3 rounded-2xl text-left"
                        >
                            <div class="sidebar-user-badge sidebar-icon-slot flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white">
                                @if($user->isGuest() && $guestHoursRemaining !== null)
                                    {{ $guestHoursRemaining }}ч
                                @else
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                @endif
                            </div>
                            <div class="sidebar-profile-text min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-[#526056]">{{ $user->name }}</div>
                                <div class="truncate text-xs text-[#8d988f]">
                                    @if($user->isAdmin())
                                        Администратор
                                    @elseif($user->isGuest())
                                        Гость
                                        @if($guestHoursRemaining !== null)
                                            · ещё {{ $guestHoursRemaining }}ч
                                        @endif
                                    @else
                                        {{ $user->email }}
                                    @endif
                                </div>
                            </div>
                        </button>
                        <div class="sidebar-profile-menu dropdown relative">
                            <button class="p-1 text-[#8d988f] transition hover:text-[#526056]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                </svg>
                            </button>       
                            <div id="profileMenu" class="dropdown-menu sidebar-profile-dropdown hidden absolute bottom-full left-0 mb-2 rounded-2xl border border-[#dce5d6] bg-white p-2 shadow-lg z-50">
                                <div class="sidebar-menu-card px-3 py-3">
                                    <div class="flex items-start gap-3">
                                        <div class="sidebar-user-badge flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white">
                                            @if($user->isGuest() && $guestHoursRemaining !== null)
                                                {{ $guestHoursRemaining }}ч
                                            @else
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="break-words text-sm font-semibold leading-5 text-slate-900">{{ $user->name }}</div>
                                            @if(!$user->isGuest())
                                                <div class="mt-0.5 break-all text-xs leading-5 text-slate-500">{{ $user->email }}</div>
                                            @endif
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @if($user->isAdmin())
                                                    <span class="sidebar-role-pill sidebar-role-pill-admin">Администратор</span>
                                                @elseif($user->isGuest())
                                                    <span class="sidebar-role-pill sidebar-role-pill-guest">Гость</span>
                                                @else
                                                    <span class="sidebar-role-pill sidebar-role-pill-user">Пользователь</span>
                                                @endif

                                                @if($user->openalex_api_key)
                                                    <span class="sidebar-inline-chip">🔑 API ключ подключен</span>
                                                @endif
                                            </div>

                                            @if($user->isGuest() && $user->guest_expires_at)
                                                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50/90 px-3 py-2 text-xs text-amber-900">
                                                    Сессия хранится ещё {{ $guestHoursRemaining }}ч.
                                                    @if($guestExpiringSoon)
                                                        <span class="mt-1 block font-medium">Истекает меньше чем через час.</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="sidebar-menu-divider my-2"></div>

                                @if($user->isGuest())
                                    <div class="space-y-2 px-1 pb-2">
                                        <form action="{{ route('profile.guest.extend') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="sidebar-menu-button sidebar-menu-button-warm w-full">
                                                Продлить сессию
                                            </button>
                                        </form>
                                        <a href="{{ route('profile.guest.register.form') }}" class="sidebar-menu-button sidebar-menu-button-dark w-full">
                                            Зарегистрироваться
                                        </a>
                                    </div>

                                    @if($guestExpiringSoon)
                                        <div class="mx-1 mb-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                            Зарегистрируйтесь, чтобы сохранить данные после окончания гостевой сессии.
                                        </div>
                                    @endif
                                @endif

                                <a href="{{ route('profile.show') }}" class="sidebar-menu-link block rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Профиль</a>
                                @if($user->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="sidebar-menu-link block rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Админ-панель</a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="sidebar-menu-link block w-full rounded-xl px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">Выйти</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <a href="{{ route('guest.login') }}" class="sidebar-profile flex items-center gap-2 rounded-full bg-[#edf5e7] px-2 py-2 text-sm text-[#526056] transition hover:bg-[#e7f0e1]">
                    <div class="sidebar-icon-slot flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#111111] text-xs font-semibold text-white">GC</div>
                    <span class="sidebar-profile-text">Войти</span>
                </a>
            @endif
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('fileSearch');
    const historyList = document.getElementById('checkHistoryList');

    if (searchInput && historyList) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const items = historyList.querySelectorAll('.check-history-item');

            items.forEach(item => {
                const filename = item.dataset.filename.toLowerCase();
                item.style.display = filename.includes(query) ? '' : 'none';
            });
        });
    }

    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        const button = dropdown.querySelector('button');
        const menu = dropdown.querySelector('.dropdown-menu');

        if (button && menu) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });

            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target)) {
                    menu.classList.add('hidden');
                }
            });
        }
    });
});
</script>

<style>
.app-sidebar {
    position: relative;
    z-index: 30;
    border-color: rgba(219, 227, 238, 0.92);
    background:
        linear-gradient(180deg, rgba(248, 250, 252, 0.86) 0%, rgba(255, 255, 255, 0.72) 100%);
    backdrop-filter: blur(24px);
}

nav.overflow-y-auto::-webkit-scrollbar {
    width: 5px;
}

nav.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

nav.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

.sidebar-brand {
    margin-bottom: 1.75rem;
}

.sidebar-brand .sidebar-icon-slot {
    background: linear-gradient(135deg, var(--accent) 0%, #14b8a6 100%);
    box-shadow: 0 18px 30px -24px rgba(15, 118, 110, 0.7);
}

.sidebar-brand-text {
    color: var(--page-ink);
    letter-spacing: -0.04em;
}

.sidebar-search input {
    border-color: #e2e8f0;
    background: rgba(255, 255, 255, 0.82);
    color: var(--page-ink);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
}

.sidebar-search input:focus {
    border-color: rgba(15, 118, 110, 0.24);
    box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.08);
}

.sidebar-search span {
    color: #94a3b8;
}

.sidebar-section {
    color: var(--page-muted);
}

.check-history-item {
    border: 1px solid transparent;
    background: transparent;
    transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.check-history-item:hover {
    background: rgba(255, 255, 255, 0.88);
    border-color: #e2e8f0;
    box-shadow: 0 18px 28px -30px rgba(15, 23, 42, 0.45);
    transform: translateY(-1px);
}

.sidebar-item-title {
    color: #334155;
}

.sidebar-item-meta {
    color: #94a3b8;
}

.sidebar-empty {
    border: 1px dashed #dbe3ee;
    background: rgba(255, 255, 255, 0.72);
    color: #64748b;
}

.sidebar-profile {
    border: 1px solid #e2e8f0;
    background: rgba(255, 255, 255, 0.82);
    box-shadow: 0 18px 30px -30px rgba(15, 23, 42, 0.45);
}

.sidebar-profile .sidebar-icon-slot {
    background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
}

.sidebar-user-badge {
    background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
    box-shadow: 0 14px 24px -18px rgba(15, 118, 110, 0.45);
}

.sidebar-profile-trigger {
    color: var(--page-ink);
}

.sidebar-profile-menu {
    position: relative;
    z-index: 40;
    border-color: #e2e8f0;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(18px);
    box-shadow: 0 24px 44px -34px rgba(15, 23, 42, 0.5);
}

.sidebar-profile-dropdown {
    width: min(28rem, calc(100vw - 2rem));
    max-width: calc(100vw - 2rem);
    z-index: 60;
}

.sidebar-profile-menu a:hover {
    background: #f8fafc;
}

.sidebar-profile-menu button:hover {
    background: #fff1f2;
}

.sidebar-menu-card {
    border-radius: 1rem;
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.88) 0%, rgba(255, 255, 255, 0.96) 100%);
}

.sidebar-menu-divider {
    height: 1px;
    background: rgba(226, 232, 240, 0.95);
}

.sidebar-role-pill {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 0.22rem 0.6rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.01em;
}

.sidebar-role-pill-admin {
    background: #fff1f2;
    color: #be123c;
}

.sidebar-role-pill-guest {
    background: #fffbeb;
    color: #b45309;
}

.sidebar-role-pill-user {
    background: #ecfdf5;
    color: #0f766e;
}

.sidebar-inline-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    background: #f0fdfa;
    color: #0f766e;
    padding: 0.22rem 0.6rem;
    font-size: 0.72rem;
    font-weight: 600;
}

.sidebar-menu-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.9rem;
    padding: 0.7rem 0.9rem;
    font-size: 0.84rem;
    font-weight: 700;
    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
}

.sidebar-menu-button:hover {
    transform: translateY(-1px);
}

.sidebar-menu-button-dark {
    background: linear-gradient(135deg, #0f172a 0%, #1f2937 100%);
    color: #ffffff;
}

.sidebar-menu-button-warm {
    border: 1px solid rgba(253, 230, 138, 0.72);
    background: rgba(255, 251, 235, 0.96);
    color: #b45309;
}

.sidebar-menu-link {
    transition: background-color 0.2s ease, color 0.2s ease;
}

@media (max-width: 1024px) {
    .sidebar-profile-dropdown {
        right: 0;
        width: min(24rem, calc(100vw - 1.5rem));
        max-width: calc(100vw - 1.5rem);
    }
}

.sidebar-icon-slot {
    flex: 0 0 2rem;
}

.sidebar-collapsed .app-sidebar {
    align-items: stretch;
}

.sidebar-collapsed .sidebar-brand {
    justify-content: center;
}

.sidebar-collapsed .sidebar-brand-text,
.sidebar-collapsed .sidebar-section-text,
.sidebar-collapsed .sidebar-section-arrow,
.sidebar-collapsed .sidebar-item-title,
.sidebar-collapsed .sidebar-item-meta,
.sidebar-collapsed .sidebar-empty,
.sidebar-collapsed .sidebar-profile-text,
.sidebar-collapsed .sidebar-profile-menu,
.sidebar-collapsed .sidebar-item-delete {
    display: none;
}

.sidebar-collapsed .sidebar-search {
    display: block;
    margin-bottom: 1rem;
}

.sidebar-collapsed .sidebar-search input {
    width: 2.5rem;
    padding-left: 0;
    padding-right: 0;
    color: transparent;
    caret-color: transparent;
    cursor: pointer;
}

.sidebar-collapsed .sidebar-search input::placeholder {
    color: transparent;
}

.sidebar-collapsed .sidebar-search span {
    left: 50%;
    transform: translateX(-50%);
    pointer-events: none;
}

.sidebar-collapsed .sidebar-brand,
.sidebar-collapsed .sidebar-section,
.sidebar-collapsed .check-history-item,
.sidebar-collapsed .sidebar-profile {
    padding-left: 0;
    padding-right: 0;
}

.sidebar-collapsed .sidebar-brand,
.sidebar-collapsed .sidebar-profile-trigger,
.sidebar-collapsed .sidebar-profile {
    justify-content: center;
}

.sidebar-collapsed .sidebar-profile-trigger {
    flex: 0 0 auto;
}

.sidebar-collapsed .sidebar-section,
.sidebar-collapsed .check-history-item {
    display: none;
}
</style>
