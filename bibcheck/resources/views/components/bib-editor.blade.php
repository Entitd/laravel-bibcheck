@props([
    'content' => '',
    'errors' => [],
    'filename' => '',
    'errorCount' => 0,
    'warningCount' => 0,
])

<section class="min-w-0 h-full overflow-hidden rounded-[22px] border border-[#e5e7eb] bg-white flex flex-col">
    <div class="border-b border-[#e5e7eb] px-4 py-3 shrink-0">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-400">Название файла</p>
                <div class="mt-2 max-w-full break-all rounded-xl border border-[#e5e7eb] bg-[#fafafa] px-3 py-2 text-sm font-medium text-slate-700 lg:max-w-md">
                    {{ $filename }}
                </div>
            </div>

            <div class="flex flex-wrap gap-2 text-xs">
                <span class="rounded-lg bg-[#fff4f4] px-3 py-2 font-medium text-red-700">
                    Ошибки: {{ $errorCount }}
                </span>
                <span class="rounded-lg bg-[#fff9ec] px-3 py-2 font-medium text-amber-700">
                    Предупреждения: {{ $warningCount }}
                </span>
            </div>
        </div>
    </div>

    <div class="min-w-0 p-2 sm:p-3 flex-1 min-h-0 flex flex-col">
        <div class="editor-shell min-w-0 flex-1 min-h-0">
            <div class="editor-toolbar shrink-0">
                <div class="editor-toolbar-meta">
                    <span>BibTeX</span>
                    <span id="editorLineCount">0 lines</span>
                </div>
            </div>

            <div id="editorContainer" class="editor-container min-w-0">
                <div id="lineNumbers" class="line-numbers"></div>
                <div class="editor-wrapper min-w-0">
                    <pre id="contentViewer"></pre>
                    <textarea id="bibEditor" name="bib_content" class="line-editor" spellcheck="false">{{ $content }}</textarea>
                </div>
            </div>
        </div>
    </div>
</section>

<input type="hidden" id="errorsInput" value='@json($errors)'>

@once
    @push('scripts')
        <script>
            const textarea = document.getElementById('bibEditor');
            const lineNumbers = document.getElementById('lineNumbers');
            const contentViewer = document.getElementById('contentViewer');
            const errorsInput = document.getElementById('errorsInput');
            const editorLineCount = document.getElementById('editorLineCount');
            const editorContainer = document.getElementById('editorContainer');

            let errorsByLine = {};
            let debounceTimer = null;

            function initErrorsMap(errorsData) {
                errorsByLine = {};
                errorsData.forEach(error => {
                    if (!errorsByLine[error.line]) {
                        errorsByLine[error.line] = [];
                    }
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

                lineNumbers.innerHTML = lines.map((_, i) => {
                    const lineNum = i + 1;
                    const errs = errorsByLine[lineNum] || [];
                    const cls = errs.some(e => e.severity === 'error') ? 'error-line' : (errs.length ? 'warning-line' : '');
                    const msg = errs.map(e => e.message).join(' | ');
                    const dataAttr = msg ? `data-error="${escapeHtml(msg)}"` : '';
                    return `<div class="${cls}" ${dataAttr}>${lineNum}</div>`;
                }).join('');

                contentViewer.innerHTML = lines.map((line, i) => {
                    const lineErrs = errorsByLine[i + 1] || [];
                    if (!lineErrs.length) {
                        return escapeHtml(line) || ' ';
                    }

                    let html = escapeHtml(line);
                    [...lineErrs].sort((a, b) => b.column - a.column).forEach(err => {
                        const start = Math.max((err.column || 1) - 1, 0);
                        const len = err.length || 1;
                        const cls = err.severity === 'error' ? 'error-highlight' : 'warning-highlight';
                        html = html.substring(0, start) + `<span class="${cls}">` + html.substring(start, start + len) + `</span>` + html.substring(start + len);
                    });
                    return html;
                }).join('\n');

                editorLineCount.textContent = `${lines.length} lines`;
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
                    } catch (e) {
                        console.error('Ошибка анализа', e);
                    }
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

            function syncViewportHeight() {
                const minHeight = window.innerWidth < 640 ? 300 : (window.innerWidth < 1024 ? 360 : 460);
                editorContainer.style.minHeight = minHeight + 'px';
            }

            initErrorsMap(JSON.parse(errorsInput.value || '[]'));
            syncViewportHeight();
            syncEditor();

            document.addEventListener('DOMContentLoaded', () => {
                const errorsData = JSON.parse(document.getElementById('errorsInput').value || '[]');
                initErrorsMap(errorsData);
                syncViewportHeight();
                syncEditor();
            });

            window.addEventListener('resize', () => {
                syncViewportHeight();
                syncEditor();
            });
        </script>
    @endpush
@endonce
