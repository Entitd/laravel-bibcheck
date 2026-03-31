@props(['content' => '', 'errors' => [], 'filename' => ''])

<div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-gray-800">✏️ Редактор: {{ $filename }}</h2>
    </div>

    <div class="editor-container">
        <div id="lineNumbers" class="line-numbers"></div>
        <div class="editor-wrapper">
            <div id="contentViewer"></div>
            <textarea id="bibEditor" name="bib_content" class="line-editor" spellcheck="false">{{ $content }}</textarea>
        </div>
    </div>
</div>

<input type="hidden" id="errorsInput" value='@json($errors)'>

@once
    @push('scripts')
        <script>
            const textarea = document.getElementById('bibEditor');
            const lineNumbers = document.getElementById('lineNumbers');
            const contentViewer = document.getElementById('contentViewer');
            const errorsInput = document.getElementById('errorsInput');

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

                // 2. Подсветка
                contentViewer.innerHTML = lines.map((line, i) => {
                    const lineErrs = errorsByLine[i + 1] || [];
                    if (!lineErrs.length) return escapeHtml(line) || ' ';

                    let html = escapeHtml(line);
                    [...lineErrs].sort((a, b) => b.column - a.column).forEach(err => {
                        const start = err.column - 1;
                        const len = err.length || 1;
                        const cls = err.severity === 'error' ? 'error-highlight' : 'warning-highlight';
                        html = html.substring(0, start) + `<span class="${cls}">` + html.substring(start, start + len) + `</span>` + html.substring(start + len);
                    });
                    return html;
                }).join('\n');

                const height = Math.max(lines.length * lineHeight + 60, 500);
                textarea.style.height = height + 'px';
                lineNumbers.style.height = height + 'px';
            }

            async function analyzeText() {
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
                                original_filename: '{{ $filename }}'
                            })
                        });
                        if (response.ok) {
                            const data = await response.json();
                            initErrorsMap(data.errors || []);
                            syncEditor();
                        }
                    } catch (e) { console.error('Ошибка анализа', e); }
                }, 800);
            }

            textarea.addEventListener('input', () => { syncEditor(); analyzeText(); });
            textarea.addEventListener('scroll', () => {
                lineNumbers.scrollTop = textarea.scrollTop;
                contentViewer.scrollTop = textarea.scrollTop;
                contentViewer.scrollLeft = textarea.scrollLeft;
            });

            // Старт
            initErrorsMap(JSON.parse(errorsInput.value || '[]'));
            syncEditor();

            document.addEventListener('DOMContentLoaded', () => {
                // Инициализация при первой загрузке
                const errorsData = JSON.parse(document.getElementById('errorsInput').value || '[]');
                initErrorsMap(errorsData);
                syncEditor(); // Отрисовываем номера и подсветку
            });

            // Синхронизация скролла (чтобы номера не уезжали от текста)
            textarea.addEventListener('scroll', () => {
                lineNumbers.scrollTop = textarea.scrollTop;
                contentViewer.scrollTop = textarea.scrollTop;
                contentViewer.scrollLeft = textarea.scrollLeft;
            });

        </script>


    @endpush
@endonce
