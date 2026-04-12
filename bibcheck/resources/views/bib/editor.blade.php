<x-layouts.app>
    @section('breadcrumb', isset($checkHistory) ? 'Проверка: ' . ($analysis['original_filename'] ?? 'Без названия') : 'Редактор BibTeX')

    {{-- Информация о просмотре из истории --}}
    @if(isset($checkHistory))
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-blue-600 text-xl">📋</span>
                    <div>
                        <div class="font-bold text-sm text-blue-900">
                            Просмотр сохранённой проверки
                        </div>
                        <div class="text-xs text-blue-600 mt-0.5">
                            Дата проверки: {{ $checkHistory->created_at->format('d.m.Y H:i') }} | 
                            Файл: {{ $checkHistory->filename }}
                        </div>
                    </div>
                </div>
                <a href="{{ route('bib.blade') }}" class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 transition">
                    ← Новая проверка
                </a>
            </div>
        </div>
    @endif

    {{-- Навигационная панель пользователя --}}
    @auth
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    {{-- Аватар и имя --}}
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-bold text-sm">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-500">
                                @if(auth()->user()->isAdmin())
                                    <span class="inline-block px-2 py-0.5 rounded bg-red-100 text-red-800">Администратор</span>
                                @elseif(auth()->user()->isGuest())
                                    <span class="inline-block px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">Гость</span>
                                    @if(auth()->user()->guest_expires_at)
                                        <span class="text-yellow-600 ml-1">
                                            ({{ \Carbon\Carbon::now()->diffForHumans(auth()->user()->guest_expires_at, true) }})
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded bg-green-100 text-green-800">Пользователь</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- API Key индикатор --}}
                    @if(auth()->user()->openalex_api_key)
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 border border-blue-200">
                            <span class="text-blue-600">🔑</span>
                            <span class="text-xs text-blue-800">API ключ подключен</span>
                        </div>
                    @endif
                </div>

                {{-- Кнопки навигации --}}
                <div class="flex items-center gap-2">
                    @if(auth()->user()->isGuest())
                        {{-- Кнопки для гостя --}}
                        <form action="{{ route('profile.guest.extend') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 transition">
                                Продлить сессию
                            </button>
                        </form>
                        <a href="{{ route('profile.guest.register.form') }}" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
                            Зарегистрироваться
                        </a>
                    @endif

                    {{-- Ссылка на профиль --}}
                    <a href="{{ route('profile.show') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        Профиль
                    </a>

                    {{-- Кнопка выхода --}}
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                            Выйти
                        </button>
                    </form>
                </div>
            </div>

            {{-- Предупреждение для гостей о скором истечении --}}
            @if(auth()->user()->isGuest() && auth()->user()->guest_expires_at)
                @php
                    $remaining = \Carbon\Carbon::now()->diffInSeconds(auth()->user()->guest_expires_at);
                    $isExpiringSoon = $remaining < 3600; // Меньше часа
                @endphp
                @if($isExpiringSoon)
                    <div class="mt-3 p-3 rounded-lg bg-yellow-50 border border-yellow-200">
                        <p class="text-sm text-yellow-800">
                            ⚠️ Ваша гостевая сессия истекает менее чем через час. 
                            <a href="{{ route('register') }}" class="font-bold underline hover:text-yellow-900">Зарегистрируйтесь</a>, 
                            чтобы сохранить данные.
                        </p>
                    </div>
                @endif
            @endif
        </div>
    @endauth

    {{-- Кнопки загрузки --}}
    <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
        <div class="border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center hover:border-green-500 hover:bg-green-50 cursor-pointer transition">
            + Создать пустой проект
        </div>
        <form action="{{ route('bib.upload.blade') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label class="border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center hover:border-blue-500 hover:bg-blue-50 cursor-pointer transition block">
                <input type="file" name="bib_file" class="hidden" onchange="this.form.submit()">
                <span>↑ Загрузить файл для проверки</span>
            </label>
        </form>
    </div>

    @php
        // Используем либо данные из сессии, либо переданные из контроллера
        $analysisData = session('analysis', $analysis ?? null);
    @endphp

    @if($analysisData)
        <form id="editForm" action="{{ route('bib.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Левая часть: Редактор --}}
                <div class="lg:col-span-2">
                    <x-bib-editor
                        :content="old('bib_content', $analysisData['raw_content'] ?? '')"
                        :errors="$analysisData['errors'] ?? []"
                        :filename="$analysisData['original_filename'] ?? 'Без названия'"
                    />
                </div>

                {{-- Правая часть: Отчет --}}
                <div class="space-y-6">
                    <div class="p-5 rounded-2xl {{ str_contains($analysisData['course_comparison_result'], 'соответствует') ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
                        <h3 class="font-bold mb-2">Вердикт:</h3>
                        <p class="text-sm">{{ $analysisData['course_comparison_result'] }}</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <h3 class="font-bold mb-4">Поиск в OpenAlex:</h3>
                        @if(!empty($analysisData['entries']))
                        <div class="space-y-3">
                            @foreach($analysisData['entries'] as $key => $entry)
                                @php
                                    $api = $entry['api_check'] ?? [];
                                    $found = $api['found'] ?? false;
                                    $similarity = isset($api['similarity']) ? round($api['similarity']) : null;
                                    $title = $entry['fields']['title'] ?? 'Без названия';
                                @endphp
                                <div class="flex items-start gap-3 p-3 rounded-xl {{ $found ? 'bg-green-50' : 'bg-red-50' }}">
                                    <div class="mt-0.5 text-lg">{{ $found ? '✅' : '❌' }}</div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-sm truncate">{{ $key }}</div>
                                        <div class="text-xs text-gray-500 truncate" title="{{ $title }}">{{ $title }}</div>
                                        @if($similarity !== null)
                                            <span class="inline-block mt-1 text-xs font-bold px-2 py-0.5 rounded {{ $similarity >= 85 ? 'bg-green-200 text-green-800' : ($similarity >= 60 ? 'bg-yellow-200 text-yellow-800' : 'bg-red-200 text-red-800') }}">
                                                {{ $similarity }}%
                                            </span>
                                        @else
                                            <div class="text-xs text-gray-400 mt-1">
                                                {{ $api['message'] ?? 'Не найдено' }}
                                                @if(!empty($api['external_title']))
                                                    <br>Ближайшее совпадение: <em>{{ $api['external_title'] }}</em>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-sm text-gray-500">Результаты поиска в OpenAlex недоступны для этой проверки.</p>
                        @endif
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <h3 class="font-bold mb-4">Метрики:</h3>
                        @if(!empty($analysisData['aggregated_metrics']))
                        @php
                            $metricLabels = [
                                'totalQuantity' => 'Всего источников',
                                'amountOfLiteratureInForeignLanguages' => 'Иностранные языки',
                                'numberOfCurrentScientificPeriodicals' => 'Научная периодика',
                                'Literature21Century' => 'Источники XXI века',
                                'api_found' => 'Найдено в OpenAlex',
                                'api_not_found' => 'Не найдено в OpenAlex',
                                'api_average_similarity' => 'Средний процент совпадения',
                            ];
                            $metricSuffixes = [
                                'api_average_similarity' => '%',
                            ];
                        @endphp
                        @foreach($analysisData['aggregated_metrics'] as $key => $value)
                            <div class="flex justify-between text-sm py-2 border-b border-gray-50 last:border-0">
                                <span class="text-gray-500">{{ $metricLabels[$key] ?? $key }}</span>
                                <span class="font-bold text-blue-600">{{ $value }}{{ $metricSuffixes[$key] ?? '' }}</span>
                            </div>
                        @endforeach
                        @else
                        <p class="text-sm text-gray-500">Метрики недоступны для этой проверки.</p>
                        @endif
                    </div>

                    <button type="submit" class="w-full bg-[#00B368] hover:bg-[#009957] text-white py-4 rounded-2xl font-bold shadow-lg transition-all">
                        Сохранить изменения
                    </button>
                </div>
            </div>
        </form>
    @else
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">📄</p>
            <p>Ожидание файла для анализа...</p>
        </div>
    @endif
</x-layouts.app>
