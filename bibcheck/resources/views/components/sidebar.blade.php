@php
    $user = auth()->user();
    $recentChecks = $user ? $user->recentChecks(10) : collect();
@endphp

<aside class="w-[280px] border-r border-gray-200 bg-white flex flex-col p-4 shrink-0">
    {{-- Логотип --}}
    <div class="flex items-center gap-2 mb-6 px-2">
        <div class="w-8 h-8 bg-[#004D33] rounded-md flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <span class="font-bold text-xl tracking-tight text-[#004D33]">BIBCHECK.RU</span>
    </div>

    {{-- Поиск файлов --}}
    <div class="relative mb-6">
        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </span>
        <input
            type="text"
            id="fileSearch"
            placeholder="Найти файл"
            class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
        >
    </div>

    {{-- История проверок --}}
    <nav class="flex-1 overflow-y-auto">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-2">
            История проверок
            @if($recentChecks->count() > 0)
                <span class="ml-1 text-gray-300">({{ $recentChecks->count() }})</span>
            @endif
        </h3>

        @if($user)
            <div class="space-y-1" id="checkHistoryList">
                @forelse($recentChecks as $check)
                    <div class="check-history-item p-2 px-3 text-sm rounded-lg cursor-pointer hover:bg-gray-50 transition-colors group relative"
                         data-filename="{{ $check->filename }}"
                         data-created="{{ $check->created_at->format('d.m.Y H:i') }}"
                         onclick="window.location.href='{{ route('check-history.show', $check->id) }}'"
                         style="cursor: pointer;">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-700 truncate">
                                    {{ $check->filename }}
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    {{ $check->relative_time }}
                                </div>
                                @if($check->total_entries > 0)
                                    <div class="flex items-center gap-2 mt-1 text-xs">
                                        <span class="text-blue-600" title="Записей">
                                            📄 {{ $check->total_entries }}
                                        </span>
                                        @if($check->error_count > 0)
                                            <span class="text-red-600" title="Ошибок">
                                                ✕ {{ $check->error_count }}
                                            </span>
                                        @endif
                                        @if($check->warning_count > 0)
                                            <span class="text-yellow-600" title="Предупреждений">
                                                ⚠ {{ $check->warning_count }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
{{--                            <button--}}
{{--                                class="delete-check-btn opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all p-1"--}}
{{--                                data-check-id="{{ $check->id }}"--}}
{{--                                title="Удалить из истории"--}}
{{--                            >--}}
{{--                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">--}}
{{--                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>--}}
{{--                                </svg>--}}
{{--                            </button>--}}

                            <form action="{{ route('check-history.destroy', $check->id) }}" method="POST" class="inline" onsubmit="return confirm('Удалить эту запись из истории?');">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all p-1"
                                    title="Удалить из истории"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </form>

                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 px-2">
                        <div class="text-gray-300 text-3xl mb-2">📋</div>
                        <p class="text-xs text-gray-400">
                            История проверок пуста
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            Загрузите .bib файл для начала работы
                        </p>
                    </div>
                @endforelse
            </div>
        @else
            <div class="text-center py-8 px-2">
                <div class="text-gray-300 text-3xl mb-2">🔒</div>
                <p class="text-xs text-gray-400">
                    Войдите для сохранения истории
                </p>
                <a href="{{ route('guest.login') }}" class="inline-block mt-2 text-xs text-green-600 hover:text-green-700 font-medium">
                    Войти →
                </a>
            </div>
        @endif
    </nav>

    {{-- Информация о пользователе --}}
    <div class="pt-4 border-t border-gray-100 mt-auto">
        @if($user)
            <div class="flex items-center gap-3 p-2 cursor-pointer hover:bg-gray-50 rounded-lg transition-colors"
                 onclick="window.location.href='{{ route('profile.show') }}'">
                {{-- Аватар --}}
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>

                {{-- Информация --}}
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-700 truncate">{{ $user->name }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ $user->email }}</div>
                    <div class="mt-0.5">
                        @if($user->isAdmin())
                            <span class="inline-block px-2 py-0.5 rounded text-xs bg-red-100 text-red-700 font-medium">
                                Администратор
                            </span>
                        @elseif($user->isGuest())
                            <span class="inline-block px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700 font-medium">
                                Гость
                                @if($user->guest_expires_at)
                                    <span class="ml-1 text-yellow-600">
                                        ({{ \Carbon\Carbon::now()->diffForHumans($user->guest_expires_at, true) }})
                                    </span>
                                @endif
                            </span>
                        @else
                            <span class="inline-block px-2 py-0.5 rounded text-xs bg-green-100 text-green-700 font-medium">
                                Пользователь
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Меню --}}
                <div class="dropdown relative">
                    <button class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>

                    {{-- Выпадающее меню --}}
                    <div class="dropdown-menu hidden absolute right-0 bottom-full mb-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                        <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <span class="mr-2">👤</span> Профиль
                        </a>
                        @if($user->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <span class="mr-2">⚙️</span> Админ-панель
                            </a>
                        @endif
                        <div class="border-t border-gray-100 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <span class="mr-2">🚪</span> Выйти
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <a href="{{ route('guest.login') }}" class="flex items-center gap-3 p-2 cursor-pointer hover:bg-gray-50 rounded-lg transition-colors">
                <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-600">Гость</div>
                    <div class="text-xs text-green-600 mt-0.5">Войти →</div>
                </div>
            </a>
        @endif
    </div>
</aside>

{{-- Скрипты для интерактивности --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Поиск по истории проверок
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

    // Удаление записи из истории
    document.addEventListener('click', async function(e) {
        const deleteBtn = e.target.closest('.delete-check-btn');
        if (!deleteBtn) return;

        // Останавливаем всплытие, чтобы не сработал onclick на div
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const checkId = deleteBtn.dataset.checkId;
        console.log('Удаление записи:', checkId);

        if (!checkId) {
            console.error('checkId не найден');
            return;
        }

        if (!confirm('Удалить эту запись из истории?')) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        console.log('CSRF Token:', csrfToken ? 'найден' : 'НЕ найден');

        try {
            const response = await fetch(`/api/check-history/${checkId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            console.log('Response status:', response.status);

            if (response.ok) {
                window.location.href = '/';
            } else {
                const data = await response.json().catch(() => ({}));
                console.error('Ошибка удаления:', data);
                alert(data.message || `Ошибка при удалении (код: ${response.status})`);
            }
        } catch (error) {
            console.error('Ошибка при удалении:', error);
            alert('Произошла ошибка при удалении: ' + error.message);
        }
    });

    // Dropdown меню пользователя
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
.check-history-item {
    transition: all 0.2s ease;
}

.check-history-item:hover {
    transform: translateX(2px);
}

.delete-check-btn {
    transition: all 0.2s ease;
}

.delete-check-btn:hover {
    transform: scale(1.2);
}

/* Скроллбар для истории */
nav.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}

nav.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

nav.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

nav.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
