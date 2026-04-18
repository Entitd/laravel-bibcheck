<x-layouts.app>
    @section('breadcrumb', isset($checkHistory) ? 'Проверка: ' . ($analysis['original_filename'] ?? 'Без названия') : 'Редактор BibTeX')

    @php
        // Используем либо данные из сессии, либо переданные из контроллера
        $analysisData = session('analysis', $analysis ?? null);
    @endphp

    <div class="{{ $analysisData ? 'lg:h-[calc(100vh-120px)] lg:min-h-0 lg:flex lg:flex-col lg:overflow-hidden' : '' }}">
    {{-- Информация о просмотре из истории --}}
    @if(isset($checkHistory))
        <div class="info-surface rounded-2xl p-4 {{ $analysisData ? 'mb-4 lg:shrink-0' : 'mb-6' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="info-icon text-xl">📋</span>
                    <div>
                        <div class="font-bold text-sm text-slate-900">
                            Просмотр сохранённой проверки
                        </div>
                        <div class="mt-0.5 text-xs text-slate-500">
                            Дата проверки: {{ $checkHistory->created_at->format('d.m.Y H:i') }} | 
                            Файл: {{ $checkHistory->filename }}
                        </div>
                    </div>
                </div>
                <a href="{{ route('bib.blade') }}" class="action-button action-button-dark">
                    ← Новая проверка
                </a>
            </div>
        </div>
    @endif

    {{-- Кнопки загрузки --}}
    <div class="section-caption {{ $analysisData ? 'lg:shrink-0' : '' }}">
        Действия с проектом
    </div>
    <div class="grid grid-cols-2 gap-4 mb-5 text-sm {{ $analysisData ? 'lg:shrink-0' : '' }}">
        <div class="upload-card upload-card-create">
            + Создать пустой проект
        </div>
        <form action="{{ route('bib.upload.blade') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label class="upload-card upload-card-upload block">
                <input type="file" name="bib_file" class="hidden" onchange="this.form.submit()">
                <span>↑ Загрузить файл для проверки</span>
            </label>
        </form>
    </div>

    @if($analysisData)
        <form id="editForm" action="{{ route('bib.update') }}" method="POST" class="lg:flex-1 lg:min-h-0">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:h-full">
                {{-- Левая часть: Редактор --}}
                <div class="lg:col-span-2 flex flex-col lg:h-full lg:min-h-0">
                    <div class="section-caption mb-3">
                        Редактор BibTeX
                    </div>
                    <div class="flex-1 overflow-hidden lg:min-h-0">
                        <div class="h-full">
                            <x-bib-editor
                                :content="old('bib_content', $analysisData['raw_content'] ?? '')"
                                :errors="$analysisData['errors'] ?? []"
                                :filename="$analysisData['original_filename'] ?? 'Без названия'"
                            />
                        </div>
                    </div>
                </div>

                {{-- Правая часть: Отчет --}}
                <div class="flex flex-col space-y-4 lg:h-full lg:min-h-0">
                    <div class="report-panel-header lg:shrink-0">
                        <div class="section-caption section-caption-tight">
                            Аналитика и отчет
                        </div>
                    </div>
                    <div class="report-panel-shell custom-scrollbar lg:flex-1 lg:min-h-0 lg:overflow-y-auto lg:pr-2">
                        <div class="verdict-card p-5 rounded-2xl {{ str_contains($analysisData['course_comparison_result'], 'соответствует') ? 'verdict-card-positive' : 'verdict-card-negative' }}">
                            <div class="report-block-label">Итог проверки</div>
                            <h3 class="mb-2 text-sm font-bold">Вердикт</h3>
                            <p class="text-xs leading-5">{{ $analysisData['course_comparison_result'] }}</p>
                        </div>

                        <div class="report-card p-5 rounded-2xl">
                            <div class="report-block-label">Сводные данные</div>
                            <h3 class="mb-4 text-sm font-bold text-slate-900">Метрики</h3>
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
                                <div class="metric-row flex justify-between py-2 text-sm">
                                    <span class="text-slate-500">{{ $metricLabels[$key] ?? $key }}</span>
                                    <span class="font-bold text-slate-900">{{ $value }}{{ $metricSuffixes[$key] ?? '' }}</span>
                                </div>
                            @endforeach
                            @else
                            <p class="text-sm text-slate-500">Метрики недоступны для этой проверки.</p>
                            @endif
                        </div>

                        <div class="report-card p-5 rounded-2xl">
                            <div class="report-block-label">Внешняя валидация</div>
                            <h3 class="mb-4 text-sm font-bold text-slate-900">Поиск в OpenAlex</h3>
                            @if(!empty($analysisData['entries']))
                            <div class="space-y-3">
                                @foreach($analysisData['entries'] as $key => $entry)
                                    @php
                                        $api = $entry['api_check'] ?? [];
                                        $found = $api['found'] ?? false;
                                        $similarity = isset($api['similarity']) ? round($api['similarity']) : null;
                                        $title = $entry['fields']['title'] ?? 'Без названия';
                                    @endphp
                                    <div class="search-result-item flex items-start gap-3 rounded-xl p-3 {{ $found ? 'search-result-item-found' : 'search-result-item-missing' }}">
                                        <div class="mt-0.5 text-lg">{{ $found ? '✅' : '❌' }}</div>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-slate-900">{{ $key }}</div>
                                            <div class="truncate text-xs text-slate-500" title="{{ $title }}">{{ $title }}</div>
                                            @if($similarity !== null)
                                                <span class="similarity-chip mt-1 inline-block text-xs font-bold {{ $similarity >= 85 ? 'similarity-chip-high' : ($similarity >= 60 ? 'similarity-chip-medium' : 'similarity-chip-low') }}">
                                                    {{ $similarity }}%
                                                </span>
                                            @else
                                                <div class="mt-1 text-xs text-slate-400">
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
                            <p class="text-sm text-slate-500">Результаты поиска в OpenAlex недоступны для этой проверки.</p>
                            @endif
                        </div>
                    </div>

                    <div class="pt-2 lg:shrink-0">
                        <button type="submit" class="primary-submit w-full rounded-2xl py-4 font-bold text-white transition-all">
                            Сохранить изменения
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @else
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">📄</p>
            <p>Ожидание файла для анализа...</p>
        </div>
    @endif
    </div>

    <style>
        .section-caption {
            margin-bottom: 0.75rem;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .section-caption-tight {
            margin-bottom: 0.35rem;
        }

        .page-surface {
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 36px -34px rgba(15, 23, 42, 0.22);
            backdrop-filter: blur(14px);
        }

        .info-surface {
            border: 1px solid rgba(191, 219, 254, 0.62);
            background: linear-gradient(135deg, rgba(239, 246, 255, 0.88) 0%, rgba(236, 253, 245, 0.9) 100%);
            box-shadow: 0 24px 42px -34px rgba(14, 116, 144, 0.32);
            backdrop-filter: blur(14px);
        }

        .info-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.9rem;
            background: rgba(255, 255, 255, 0.72);
            color: #0f766e;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .user-avatar {
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
            box-shadow: 0 18px 30px -22px rgba(15, 118, 110, 0.42);
        }

        .role-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .role-pill-admin {
            background: #fff1f2;
            color: #be123c;
        }

        .role-pill-guest {
            background: #fffbeb;
            color: #b45309;
        }

        .role-pill-user {
            background: #ecfdf5;
            color: #0f766e;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(153, 246, 228, 0.5);
            border-radius: 0.9rem;
            background: rgba(236, 253, 245, 0.8);
            padding: 0.45rem 0.8rem;
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.8rem;
            padding: 0.6rem 0.95rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .action-button:hover {
            transform: translateY(-1px);
        }

        .action-button-dark {
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: linear-gradient(135deg, #0f172a 0%, #1f2937 100%);
            color: #ffffff;
            box-shadow: 0 18px 28px -24px rgba(15, 23, 42, 0.55);
        }

        .action-button-dark:hover {
            background: linear-gradient(135deg, #111827 0%, #0f766e 100%);
        }

        .action-button-soft {
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: rgba(255, 255, 255, 0.82);
            color: #334155;
        }

        .action-button-soft:hover {
            background: rgba(248, 250, 252, 0.95);
            border-color: rgba(203, 213, 225, 0.95);
        }

        .action-button-warm {
            border: 1px solid rgba(253, 230, 138, 0.72);
            background: rgba(255, 251, 235, 0.94);
            color: #b45309;
        }

        .action-button-warm:hover {
            background: rgba(254, 243, 199, 0.96);
        }

        .action-button-danger {
            border: 1px solid rgba(253, 205, 211, 0.9);
            background: rgba(255, 241, 242, 0.94);
            color: #e11d48;
        }

        .action-button-danger:hover {
            background: rgba(255, 228, 230, 0.98);
        }

        .warning-note {
            border: 1px solid rgba(253, 230, 138, 0.74);
            background: linear-gradient(135deg, rgba(255, 251, 235, 0.94) 0%, rgba(255, 247, 237, 0.98) 100%);
        }

        .upload-card {
            border: 1.5px dashed rgba(203, 213, 225, 0.95);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.62);
            padding: 1rem;
            text-align: center;
            color: #475569;
            cursor: pointer;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.74);
            transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            backdrop-filter: blur(10px);
        }

        .upload-card:hover {
            transform: translateY(-2px);
            color: #0f172a;
            box-shadow: 0 18px 34px -34px rgba(15, 23, 42, 0.28);
        }

        .upload-card-create:hover {
            border-color: rgba(15, 118, 110, 0.34);
            background: rgba(240, 253, 250, 0.92);
        }

        .upload-card-upload:hover {
            border-color: rgba(59, 130, 246, 0.28);
            background: rgba(239, 246, 255, 0.92);
        }

        .report-panel-header {
            padding: 1rem 1.1rem 0;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-bottom: 0;
            border-radius: 1.4rem 1.4rem 0 0;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.94) 0%, rgba(255, 255, 255, 0.84) 100%);
        }

        .report-panel-note {
            margin: 0 0 0.95rem;
            font-size: 0.82rem;
            line-height: 1.45;
            color: #64748b;
        }

        .report-panel-shell {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1rem 1.1rem 1.1rem;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-top: 0;
            border-radius: 0 0 1.4rem 1.4rem;
            background: rgba(248, 250, 252, 0.55);
        }

        .verdict-card {
            border: 1px solid transparent;
            box-shadow: 0 16px 28px -32px rgba(15, 23, 42, 0.18);
        }

        .verdict-card-positive {
            border-color: rgba(167, 243, 208, 0.54);
            background: linear-gradient(135deg, rgba(236, 253, 245, 0.94) 0%, rgba(240, 253, 250, 0.96) 100%);
            color: #065f46;
        }

        .verdict-card-negative {
            border-color: rgba(253, 164, 175, 0.5);
            background: linear-gradient(135deg, rgba(255, 241, 242, 0.96) 0%, rgba(255, 247, 237, 0.96) 100%);
            color: #9f1239;
        }

        .report-card {
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 14px 26px -30px rgba(15, 23, 42, 0.14);
            backdrop-filter: blur(12px);
        }

        .report-block-label {
            margin-bottom: 0.45rem;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .search-result-item {
            border: 1px solid rgba(226, 232, 240, 0.72);
            box-shadow: none;
        }

        .search-result-item-found {
            border-color: rgba(167, 243, 208, 0.5);
            background: linear-gradient(180deg, rgba(236, 253, 245, 0.82) 0%, rgba(255, 255, 255, 0.96) 100%);
        }

        .search-result-item-missing {
            border-color: rgba(253, 164, 175, 0.46);
            background: linear-gradient(180deg, rgba(255, 241, 242, 0.82) 0%, rgba(255, 255, 255, 0.96) 100%);
        }

        .similarity-chip {
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
        }

        .similarity-chip-high {
            background: #d1fae5;
            color: #065f46;
        }

        .similarity-chip-medium {
            background: #fef3c7;
            color: #92400e;
        }

        .similarity-chip-low {
            background: #ffe4e6;
            color: #be123c;
        }

        .metric-row {
            border-bottom: 1px solid rgba(241, 245, 249, 0.95);
            align-items: center;
            gap: 1rem;
        }

        .metric-row:last-child {
            border-bottom: 0;
        }

        .primary-submit {
            border: 1px solid rgba(15, 23, 42, 0.06);
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
            box-shadow: 0 24px 40px -28px rgba(15, 118, 110, 0.5);
        }

        .primary-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 28px 44px -28px rgba(15, 118, 110, 0.58);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        @media (max-width: 1024px) {
            .report-panel-header {
                padding: 0;
                border: 0;
                border-radius: 0;
                background: transparent;
            }

            .report-panel-shell {
                padding: 0;
                border: 0;
                border-radius: 0;
                background: transparent;
            }
        }
    </style>

</x-layouts.app>
