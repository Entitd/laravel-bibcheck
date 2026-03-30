<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BibCheck — Editor Mode</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Контейнер для фиксации размеров */
        .editor-container {
            display: flex;
            background-color: #111827; /* bg-gray-900 */
            border-radius: 0.5rem;
            border: 1px solid #374151;
            min-height: 500px;
            position: relative;
            overflow: hidden;
        }

        /* Номера строк — теперь они поверх всего и имеют свой z-index */
        .line-numbers {
            font-family: ui-monospace, monospace;
            font-size: 0.875rem;
            line-height: 1.5rem;
            padding-top: 1rem;
            padding-bottom: 1rem;
            width: 3.5rem;
            background-color: #1f2937; /* bg-gray-800 */
            border-right: 1px solid #374151;
            color: #9ca3af;
            user-select: none;
            z-index: 30; /* Выше чем textarea */
            text-align: right;
        }

        .line-numbers > div {
            padding-right: 0.75rem;
            height: 1.5rem;
            cursor: help;
            position: relative;
        }

        /* Тултип при наведении на номер строки */
        .line-numbers > div[data-error]:hover::after {
            content: attr(data-error);
            position: absolute;
            left: 100%;
            top: 0;
            margin-left: 10px;
            background: #111827;
            color: #f3f4f6;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            line-height: 1.2rem;
            width: 250px;
            white-space: normal;
            z-index: 50;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            border: 1px solid #4b5563;
            text-align: left;
        }

        .line-numbers .error-line { color: #ef4444; font-weight: bold; }
        .line-numbers .warning-line { color: #f59e0b; font-weight: bold; }

        /* Область контента */
        .editor-wrapper {
            position: relative;
            flex: 1;
            overflow: hidden;
        }

        /* Нижний слой: только подчеркивания */
        #contentViewer {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            font-family: ui-monospace, monospace;
            font-size: 0.875rem;
            line-height: 1.5rem;
            white-space: pre;
            color: transparent; /* Текст не виден, только span */
            z-index: 1;
            pointer-events: none;
        }

        /* Верхний слой: само редактирование */
        textarea.line-editor {
            position: relative;
            z-index: 2; /* Между номерами и фоном */
            width: 100%;
            padding: 1rem;
            background: transparent !important;
            color: #f3f4f6;
            font-family: ui-monospace, monospace;
            font-size: 0.875rem;
            line-height: 1.5rem;
            border: none;
            outline: none;
            resize: none;
            white-space: pre;
            overflow-y: hidden;
            overflow-x: auto;
        }

        /* Подсветка */
        .error-highlight { border-bottom: 2px wavy #ef4444; background: rgba(239, 68, 68, 0.1); }
        .warning-highlight { border-bottom: 2px wavy #f59e0b; background: rgba(245, 158, 11, 0.1); }
    </style>


</head>

<body class="bg-gray-100 p-10">
<div class="max-w-6xl mx-auto bg-white p-8 rounded-xl shadow-lg">
    <h1 class="text-2xl font-bold mb-6">Проверка BIB-файла (BibCheck)</h1>

    {{-- Форма загрузки --}}
    <form action="{{ route('bib.upload.blade') }}" method="POST" enctype="multipart/form-data" class="mb-10">
        @csrf
        <div class="flex items-center gap-4">
            <input type="file" name="bib_file" required class="border p-2 rounded flex-1">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Проверить</button>
        </div>
    </form>

    @if(session('analysis'))
        <div class="mt-6 flex gap-3 mb-6 items-center">
            <button type="submit" form="editForm" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 font-medium">
                💾 Сохранить и перепроверить
            </button>
        </div>

        <form id="editForm" action="{{ route('bib.update') }}" method="POST">
            @csrf
            <input type="hidden" name="original_filename" value="{{ session('analysis')['original_filename'] ?? '' }}">
            <input type="hidden" name="errors" id="errorsInput" value='@json(session('analysis')['errors'] ?? [])'>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2">
                    <h2 class="text-xl font-bold mb-4">✏️ Редактор BibTeX:</h2>
                    <div class="editor-container">
                        <div id="lineNumbers" class="line-numbers"></div>

                        <div class="editor-wrapper">
                            <div id="contentViewer"></div>
                            <textarea id="bibEditor" name="bib_content" class="line-editor" spellcheck="false">{{ old('bib_content', session('analysis')['raw_content'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <script>
                    const textarea = document.getElementById('bibEditor');
                    const lineNumbers = document.getElementById('lineNumbers');
                    const contentViewer = document.getElementById('contentViewer');

                    // Берем ошибки из скрытого инпута или сессии
                    let errorsByLine = {};
                    const initialErrors = @json(session('analysis')['errors'] ?? []);

                    function initErrorsMap(errorsData) {
                        errorsByLine = {};
                        errorsData.forEach(error => {
                            if (!errorsByLine[error.line]) errorsByLine[error.line] = [];
                            errorsByLine[error.line].push(error);
                        });
                    }

                    function escapeHtml(text) {
                        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
                    }

                    function syncEditor() {
                        const lines = textarea.value.split('\n');
                        const lineHeight = 24; // 1.5rem

                        // 1. Рисуем номера строк
                        lineNumbers.innerHTML = lines.map((_, i) => {
                            const lineNum = i + 1;
                            const errs = errorsByLine[lineNum] || [];
                            let cls = '';
                            if (errs.some(e => e.severity === 'error')) cls = 'error-line';
                            else if (errs.length > 0) cls = 'warning-line';

                            const msg = errs.map(e => e.message).join('\n');
                            const dataAttr = msg ? `data-error="${escapeHtml(msg)}"` : '';
                            return `<div class="${cls}" ${dataAttr}>${lineNum}</div>`;
                        }).join('');

                        // 2. Рисуем подсветку в фоне
                        contentViewer.innerHTML = lines.map((line, i) => {
                            const lineErrs = errorsByLine[i + 1] || [];
                            if (!lineErrs.length) return escapeHtml(line) || ' ';

                            let html = escapeHtml(line);
                            // Сортируем ошибки с конца, чтобы не съезжали индексы
                            [...lineErrs].sort((a, b) => b.column - a.column).forEach(err => {
                                const start = err.column - 1;
                                const len = err.length || 1;
                                const cls = err.severity === 'error' ? 'error-highlight' : 'warning-highlight';

                                const before = html.substring(0, start);
                                const target = html.substring(start, start + len);
                                const after = html.substring(start + len);
                                html = `${before}<span class="${cls}">${target}</span>${after}`;
                            });
                            return html;
                        }).join('\n');

                        // 3. Высота
                        const newHeight = Math.max(lines.length * lineHeight + 40, 500);
                        textarea.style.height = newHeight + 'px';
                        lineNumbers.style.height = newHeight + 'px';
                    }

                    // Слушатели событий
                    textarea.addEventListener('input', () => {
                        syncEditor();
                        // Тут можно вызвать ваш analyzeText() через debounce
                    });

                    textarea.addEventListener('scroll', () => {
                        // Синхронизируем скролл номеров строк и подсветки с textarea
                        lineNumbers.scrollTop = textarea.scrollTop;
                        contentViewer.scrollTop = textarea.scrollTop;
                        contentViewer.scrollLeft = textarea.scrollLeft;
                    });

                    // Запуск при загрузке
                    initErrorsMap(initialErrors);
                    syncEditor();
                </script>

                {{-- Панель отчета --}}
                <div class="space-y-4">
                    <div class="p-4 {{ str_contains(session('analysis')['course_comparison_result'], 'соответствует') ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500' }}">
                        <h2 class="font-bold mb-1">Вердикт:</h2>
                        <p class="text-sm">{{ session('analysis')['course_comparison_result'] }}</p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow border">
                        <h3 class="font-bold mb-3">Показатели:</h3>
                        @foreach(session('analysis')['aggregated_metrics'] as $label => $value)
                            <div class="flex justify-between py-2 border-b last:border-0 text-sm">
                                <span class="text-gray-600">{{ $label }}</span>
                                <span class="font-bold text-blue-600">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                        <h3 class="font-bold text-red-700 mb-2">Ошибки: {{ count(session('analysis')['errors']) }}</h3>
                        <div class="max-h-64 overflow-y-auto text-xs space-y-2">
                            @foreach(session('analysis')['errors'] as $error)
                                <div class="bg-white p-2 rounded shadow-sm border">
                                        <span class="font-bold {{ $error['severity'] === 'error' ? 'text-red-600' : 'text-yellow-600' }}">
                                            [{{ $error['line'] }}:{{ $error['column'] }}]
                                        </span>
                                    {{ $error['message'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <script>
            const textarea = document.getElementById('bibEditor');
            const lineNumbers = document.getElementById('lineNumbers');
            const contentViewer = document.getElementById('contentViewer');
            const errorsInput = document.getElementById('errorsInput');
            const originalFilename = document.querySelector('input[name="original_filename"]')?.value || '';

            let errorsByLine = {};
            let debounceTimer = null;

            function initErrorsMap(errorsData) {
                errorsByLine = {};
                errorsData.forEach(error => {
                    if (!errorsByLine[error.line]) errorsByLine[error.line] = [];
                    errorsByLine[error.line].push(error);
                });
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function syncEditor() {
                const lines = textarea.value.split('\n');
                const lineHeight = 24;

                // 1. Номера строк
                lineNumbers.innerHTML = lines.map((_, i) => {
                    const lineNum = i + 1;
                    const errs = errorsByLine[lineNum] || [];
                    const cls = errs.some(e => e.severity === 'error') ? 'error-line' : (errs.length ? 'warning-line' : '');
                    const msg = errs.map(e => e.message).join(' | ');
                    return `<div class="${cls}" data-error="${escapeHtml(msg)}">${lineNum}</div>`;
                }).join('');

                // 2. Подсветка в фоне
                contentViewer.innerHTML = lines.map((line, i) => {
                    const lineErrs = errorsByLine[i + 1] || [];
                    if (!lineErrs.length) return escapeHtml(line) || ' ';

                    let html = escapeHtml(line);
                    [...lineErrs].sort((a, b) => b.column - a.column).forEach(err => {
                        const start = err.column - 1;
                        const len = err.length || 1;
                        const cls = err.severity === 'error' ? 'error-highlight' : 'warning-highlight';
                        const before = html.substring(0, start);
                        const target = html.substring(start, start + len);
                        const after = html.substring(start + len);
                        html = `${before}<span class="${cls}">${target}</span>${after}`;
                    });
                    return html;
                }).join('\n');

                // 3. Синхронизация высоты
                const height = Math.max(lines.length * lineHeight + 40, 500);
                textarea.style.height = height + 'px';
                lineNumbers.style.height = height + 'px';
                contentViewer.style.height = height + 'px';
            }

            function analyzeText() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(async () => {
                    try {
                        const response = await fetch('{{ route("bib.update") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new URLSearchParams({
                                bib_content: textarea.value,
                                original_filename: originalFilename
                            })
                        });
                        if (response.ok) {
                            const data = await response.json();
                            initErrorsMap(data.errors || []);
                            syncEditor();
                            errorsInput.value = JSON.stringify(data.errors);
                        }
                    } catch (e) { console.error('Analysis failed', e); }
                }, 800);
            }

            textarea.addEventListener('input', () => {
                syncEditor();
                analyzeText();
            });

            textarea.addEventListener('scroll', () => {
                lineNumbers.scrollTop = textarea.scrollTop;
                contentViewer.scrollTop = textarea.scrollTop;
                contentViewer.scrollLeft = textarea.scrollLeft;
            });

            // Инициализация
            initErrorsMap(JSON.parse(errorsInput.value || '[]'));
            syncEditor();
        </script>
    @endif
</div>
</body>
</html>
