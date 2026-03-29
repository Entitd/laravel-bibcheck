<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>BibCheck — Blade Mode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .line-numbers {
            counter-reset: line;
        }
        .line-numbers > div {
            counter-increment: line;
        }
        .line-numbers > div::before {
            content: counter(line);
            display: inline-block;
            width: 2.5rem;
            text-align: right;
            padding-right: 0.75rem;
            color: #6b7280;
            user-select: none;
        }
        textarea.line-editor {
            tab-size: 4;
        }
    </style>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-6xl mx-auto bg-white p-8 rounded-xl shadow-lg">
        <h1 class="text-2xl font-bold mb-6">Проверка BIB-файла (Blade)</h1>

        <form action="{{ route('bib.upload.blade') }}" method="POST" enctype="multipart/form-data" class="mb-10">
        @csrf
        <div class="flex items-center gap-4">
            <input type="file" name="bib_file" required class="border p-2 rounded w-full">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Отправить</button>
        </div>
        </form>

        @if(session('analysis'))
            <div class="mt-6 flex gap-3 mb-6 items-center">
                <form action="{{ route('bib.upload.blade') }}" method="POST" enctype="multipart/form-data" class="inline">
                    @csrf
                    <label class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-medium cursor-pointer">
                        📁 Загрузить другой файл
                        <input type="file" name="bib_file" required class="hidden" onchange="this.form.submit()">
                    </label>
                </form>
                <button type="submit" form="editForm" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 font-medium">
                    💾 Сохранить и проверить
                </button>
            </div>

            <form id="editForm" action="{{ route('bib.update') }}" method="POST">
                @csrf
                <input type="hidden" name="original_filename" value="{{ session('analysis')['original_filename'] ?? session('analysis')['filename'] ?? '' }}">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Левая часть: Редактор с номерами строк --}}
                    <div class="lg:col-span-2">
                        <h2 class="text-xl font-bold mb-4">✏️ Редактируемый файл:</h2>
                        <div class="relative bg-gray-900 rounded-lg overflow-hidden">
                            <div class="flex">
                                {{-- Номера строк --}}
                                <div class="line-numbers bg-gray-800 text-gray-500 text-sm font-mono py-4 select-none" id="lineNumbers"></div>
                                {{-- Текстовое поле --}}
                                <textarea name="bib_content" id="bibEditor" class="line-editor flex-1 h-[600px] p-4 font-mono text-sm bg-gray-900 text-gray-100 focus:outline-none leading-6 resize-y border-l border-gray-700">{{ old('bib_content', session('analysis')['raw_content'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Правая часть: Отчет --}}
                    <div class="space-y-4">
                        {{-- Вердикт --}}
                        <div class="p-4 {{ str_contains(session('analysis')['course_comparison_result'], 'соответствует') ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500' }}">
                            <h2 class="font-bold text-lg mb-2">Вердикт:</h2>
                            <p class="text-sm">{{ session('analysis')['course_comparison_result'] }}</p>
                        </div>

                        {{-- Метрики --}}
                        <div class="bg-white p-4 rounded-lg shadow">
                            <h3 class="font-bold text-lg mb-3">Показатели:</h3>
                            <div class="space-y-3">
                                @foreach(session('analysis')['aggregated_metrics'] as $label => $value)
                                    <div class="flex justify-between items-center py-2 border-b last:border-0">
                                        <span class="text-sm text-gray-600">
                                            @php
                                                $labels = [
                                                    'totalQuantity' => 'Всего источников',
                                                    'amountOfLiteratureInForeignLanguages' => 'Иностранные языки',
                                                    'numberOfCurrentScientificPeriodicals' => 'Периодика',
                                                    'Literature21Century' => 'XXI век'
                                                ];
                                            @endphp
                                            {{ $labels[$label] ?? $label }}
                                        </span>
                                        <span class="text-xl font-bold {{ $value > 0 ? 'text-blue-600' : 'text-gray-400' }}">{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Ошибки --}}
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                            <h3 class="font-bold text-red-700 mb-2">
                                Ошибки: {{ count(session('analysis')['errors']) }}
                            </h3>
                            @if(count(session('analysis')['errors']) > 0)
                                <div class="max-h-64 overflow-y-auto text-sm space-y-2">
                                    @foreach(session('analysis')['errors'] as $error)
                                        <div class="bg-white p-2 rounded shadow-sm">
                                            <div class="flex items-start gap-2">
                                                <span class="
                                                    @if($error['severity'] === 'error') text-red-600 font-bold
                                                    @elseif($error['severity'] === 'warning') text-yellow-600 font-bold
                                                    @else text-blue-600
                                                    @endif
                                                ">
                                                    @if($error['severity'] === 'error') ✖
                                                    @elseif($error['severity'] === 'warning') ⚠
                                                    @else ℹ
                                                    @endif
                                                </span>
                                                <div class="flex-1">
                                                    <p class="text-gray-800">{{ $error['message'] }}</p>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        Строка {{ $error['line'] }}, колонка {{ $error['column'] }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-green-600 text-sm">Ошибок не найдено ✓</p>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            <script>
                const textarea = document.getElementById('bibEditor');
                const lineNumbers = document.getElementById('lineNumbers');

                function updateLineNumbers() {
                    const lines = textarea.value.split('\n');
                    lineNumbers.innerHTML = lines.map((_, i) =>
                        '<div>' + (i + 1) + '</div>'
                    ).join('');
                }

                // Синхронизация прокрутки
                textarea.addEventListener('scroll', function() {
                    lineNumbers.scrollTop = textarea.scrollTop;
                });

                // Обновление при вводе
                textarea.addEventListener('input', updateLineNumbers);

                // Инициализация
                updateLineNumbers();
            </script>
        @endif


    </div>
</body>
</html>
