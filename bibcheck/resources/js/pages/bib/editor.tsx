import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, FilePlus2, TriangleAlert, Upload } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Редактор BibLaTeX',
        href: '/',
    },
];

type AnalysisError = {
    line: number;
    column: number;
    length?: number;
    severity: 'error' | 'warning';
    message: string;
};

type Entry = {
    fields?: {
        title?: string;
    };
    api_check?: {
        found?: boolean;
        similarity?: number;
        message?: string;
        external_title?: string;
    };
};

type PageProps = {
    flash?: {
        success?: string;
        error?: string;
    };
};

type BibtexTypeTemplate = {
    id: number;
    name: string;
    fields: string[];
};

type BibEditorProps = {
    analysis?: {
        raw_content?: string;
        original_filename?: string;
        course_comparison_result?: string;
        aggregated_metrics?: Record<string, number | string>;
        entries?: Record<string, Entry>;
        errors?: AnalysisError[];
    } | null;
    entries?: Record<string, Entry>;
    metrics?: Record<string, number | string>;
    errors?: AnalysisError[];
    bibtexTypes?: BibtexTypeTemplate[];
    checkHistory?: {
        id: number;
        filename: string;
        created_at: string;
    } | null;
};

function escapeHtml(text: string) {
    return text
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function toErrorsMap(errors: AnalysisError[]) {
    return errors.reduce<Record<number, AnalysisError[]>>((accumulator, error) => {
        if (!accumulator[error.line]) {
            accumulator[error.line] = [];
        }
        accumulator[error.line].push(error);
        return accumulator;
    }, {});
}

function getTypeTrigger(value: string, caretPosition: number) {
    const beforeCaret = value.slice(0, caretPosition);
    const match = beforeCaret.match(/(^|\s)(@[a-z_]*)$/i);

    if (!match) {
        return null;
    }

    const token = match[2];
    const query = token.slice(1).toLowerCase();

    return {
        query,
        start: beforeCaret.length - token.length,
        end: beforeCaret.length,
    };
}

function buildBibtexSnippet(type: BibtexTypeTemplate) {
    const key = `${type.name}_key`;
    const fields = type.fields.map((field) => `  ${field} = {}`).join(',\n');
    const snippet = fields.length
        ? `@${type.name}{${key},\n${fields}\n}`
        : `@${type.name}{${key}\n}`;

    return {
        snippet,
        keyStart: type.name.length + 2,
        keyEnd: type.name.length + 2 + key.length,
    };
}

export default function BibEditorPage({
    analysis,
    entries = {},
    metrics = {},
    errors = [],
    bibtexTypes = [],
    checkHistory,
}: BibEditorProps) {
    const page = usePage<PageProps>();
    const flash = page.props.flash;
    const [liveErrors, setLiveErrors] = useState<AnalysisError[]>(errors);
    const uploadForm = useForm({
        bib_file: null as File | null,
    });
    const updateForm = useForm({
        bib_content: analysis?.raw_content ?? '',
        original_filename: analysis?.original_filename ?? 'created_file.bib',
    });
    const textareaRef = useRef<HTMLTextAreaElement | null>(null);
    const lineNumbersRef = useRef<HTMLDivElement | null>(null);
    const contentViewerRef = useRef<HTMLPreElement | null>(null);
    const debounceRef = useRef<number | null>(null);
    const pendingSelectionRef = useRef<{ start: number; end: number } | null>(null);
    const [activeSuggestionIndex, setActiveSuggestionIndex] = useState(0);
    const [typeTrigger, setTypeTrigger] = useState<{
        query: string;
        start: number;
        end: number;
    } | null>(null);

    useEffect(() => {
        setLiveErrors(errors);
        updateForm.setData({
            bib_content: analysis?.raw_content ?? '',
            original_filename: analysis?.original_filename ?? 'created_file.bib',
        });
    }, [analysis?.original_filename, analysis?.raw_content, errors]);

    const errorsByLine = useMemo(() => toErrorsMap(liveErrors), [liveErrors]);
    const suggestedTypes = useMemo(() => {
        if (!typeTrigger) {
            return [];
        }

        const normalizedQuery = typeTrigger.query.trim();

        return bibtexTypes
            .filter((type) =>
                normalizedQuery.length === 0
                    ? true
                    : type.name.toLowerCase().startsWith(normalizedQuery),
            )
            .slice(0, 8);
    }, [bibtexTypes, typeTrigger]);
    const metricLabels: Record<string, string> = {
        totalQuantity: 'Всего источников',
        amountOfLiteratureInForeignLanguages: 'Иностранные языки',
        numberOfCurrentScientificPeriodicals: 'Научная периодика',
        Literature21Century: 'Источники XXI века',
        api_found: 'Найдено в OpenAlex',
        api_not_found: 'Не найдено в OpenAlex',
        api_average_similarity: 'Средний процент совпадения',
    };
    const verdict = analysis?.course_comparison_result;
    const isSuccessVerdict = verdict
        ? verdict.toLowerCase().includes('соответствует')
        : false;

    useEffect(() => {
        return () => {
            if (debounceRef.current) {
                window.clearTimeout(debounceRef.current);
            }
        };
    }, []);

    useEffect(() => {
        if (!suggestedTypes.length) {
            setActiveSuggestionIndex(0);
            return;
        }

        setActiveSuggestionIndex((currentIndex) =>
            Math.min(currentIndex, suggestedTypes.length - 1),
        );
    }, [suggestedTypes]);

    useEffect(() => {
        if (!pendingSelectionRef.current || !textareaRef.current) {
            return;
        }

        const { start, end } = pendingSelectionRef.current;
        textareaRef.current.focus();
        textareaRef.current.setSelectionRange(start, end);
        pendingSelectionRef.current = null;
    }, [updateForm.data.bib_content]);

    const applyTypeSuggestion = (type: BibtexTypeTemplate) => {
        if (!typeTrigger) {
            return;
        }

        const { snippet, keyStart, keyEnd } = buildBibtexSnippet(type);
        const nextValue =
            updateForm.data.bib_content.slice(0, typeTrigger.start) +
            snippet +
            updateForm.data.bib_content.slice(typeTrigger.end);
        const selectionStart = typeTrigger.start + keyStart;
        const selectionEnd = typeTrigger.start + keyEnd;

        pendingSelectionRef.current = {
            start: selectionStart,
            end: selectionEnd,
        };

        updateForm.setData('bib_content', nextValue);
        setTypeTrigger(null);
        analyzeText(nextValue);
    };

    const syncScroll = () => {
        if (!textareaRef.current || !lineNumbersRef.current || !contentViewerRef.current) {
            return;
        }

        lineNumbersRef.current.scrollTop = textareaRef.current.scrollTop;
        contentViewerRef.current.scrollTop = textareaRef.current.scrollTop;
        contentViewerRef.current.scrollLeft = textareaRef.current.scrollLeft;
    };

    const analyzeText = (value: string) => {
        if (debounceRef.current) {
            window.clearTimeout(debounceRef.current);
        }

        debounceRef.current = window.setTimeout(async () => {
            try {
                const response = await fetch('/update-bib', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams({
                        bib_content: value,
                        original_filename: updateForm.data.original_filename,
                    }),
                });

                if (!response.ok) {
                    return;
                }

                const data = (await response.json()) as { errors?: AnalysisError[] };
                setLiveErrors(data.errors ?? []);
            } catch {
                return;
            }
        }, 800);
    };

    const lines = updateForm.data.bib_content.split('\n');
    const highlightedLines = lines.map((line, index) => {
        const lineErrors = errorsByLine[index + 1] ?? [];
        if (!lineErrors.length) {
            return escapeHtml(line) || ' ';
        }

        let html = escapeHtml(line);
        [...lineErrors]
            .sort((left, right) => right.column - left.column)
            .forEach((error) => {
                const start = Math.max((error.column ?? 1) - 1, 0);
                const length = error.length || 1;
                const className =
                    error.severity === 'error'
                        ? 'border-b-2 border-rose-500 bg-rose-200/40'
                        : 'border-b-2 border-amber-500 bg-amber-200/40';

                html =
                    html.slice(0, start) +
                    `<span class="${className}">` +
                    html.slice(start, start + length) +
                    '</span>' +
                    html.slice(start + length);
            });

        return html || ' ';
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Редактор BibLaTeX" />

            <div className="flex min-h-[calc(100svh-4rem)] flex-col gap-6 p-4 md:p-6">
                <section className="rounded-3xl border border-border bg-card p-4 shadow-sm">
                    <div className="flex flex-col gap-4">
                        <div className="flex w-full flex-col gap-3 sm:flex-row sm:items-center">
                            <label className="inline-flex w-full flex-1 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-dashed border-border bg-background px-6 py-3 text-sm font-medium text-foreground transition hover:bg-accent">
                                <Upload className="h-4 w-4" />
                                <span>Загрузить файл</span>
                                <input
                                    type="file"
                                    className="hidden"
                                    onChange={(event) => {
                                        const input = event.target;
                                        const file = event.target.files?.[0];
                                        if (!file) {
                                            return;
                                        }

                                        uploadForm.clearErrors();
                                        uploadForm.setData('bib_file', file);
                                        router.post(
                                            '/upload-bib',
                                            { bib_file: file },
                                            {
                                                forceFormData: true,
                                                onError: (errors) => {
                                                    if (errors.bib_file) {
                                                        uploadForm.setError(
                                                            'bib_file',
                                                            String(errors.bib_file),
                                                        );
                                                    }
                                                },
                                                onFinish: () => {
                                                    input.value = '';
                                                },
                                            },
                                        );
                                    }}
                                />
                            </label>

                            {uploadForm.errors.bib_file && (
                                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                    {uploadForm.errors.bib_file}
                                </div>
                            )}

                            <button
                                type="button"
                                onClick={() => router.get('/')}
                                className="inline-flex w-full flex-1 cursor-pointer items-center justify-center rounded-2xl bg-foreground px-6 py-3 text-sm font-semibold text-background transition hover:opacity-90"
                            >
                                Новая проверка
                            </button>
                        </div>
                    </div>
                </section>

                <div className="flex flex-col gap-6">
                    {flash?.success && (
                        <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {flash.success}
                        </div>
                    )}

                    {flash?.error && (
                        <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {flash.error}
                        </div>
                    )}

                    {checkHistory && (
                        <section className="rounded-3xl border border-sky-200 bg-sky-50 p-4 shadow-sm">
                            <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <div className="text-sm font-semibold text-sky-900">
                                        Просмотр сохранённой проверки
                                    </div>
                                    <div className="mt-1 text-sm text-sky-800">
                                        Файл: {checkHistory.filename}
                                    </div>
                                </div>
                                <Link
                                    href="/"
                                    className="inline-flex items-center justify-center rounded-xl bg-foreground px-4 py-2 text-sm font-semibold text-background transition hover:opacity-90"
                                >
                                    Новая проверка
                                </Link>
                            </div>
                        </section>
                    )}

                    <section className="rounded-3xl border border-border bg-card p-4 shadow-sm">
                        <div className="grid gap-6 xl:grid-cols-[1.45fr_0.85fr]">
                            <form
                                className="min-w-0"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    if (analysis) {
                                        updateForm.post('/update-bib');
                                        return;
                                    }

                                    updateForm.post('/create-bib');
                                }}
                            >
                                <div className="rounded-3xl border border-border bg-background">
                                    <div className="border-b border-border px-4 py-4">
                                        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <div className="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                                                    Название файла
                                                </div>
                                                <input
                                                    value={updateForm.data.original_filename}
                                                    onChange={(event) =>
                                                        updateForm.setData('original_filename', event.target.value)
                                                    }
                                                    className="mt-2 w-full max-w-sm rounded-xl border border-input bg-card px-3 py-2 text-sm font-medium text-foreground outline-none transition focus:border-ring focus:ring-2 focus:ring-ring/30"
                                                    placeholder="created_file.bib"
                                                />
                                                {updateForm.errors.original_filename && (
                                                    <div className="mt-2 text-xs text-rose-600">
                                                        {updateForm.errors.original_filename}
                                                    </div>
                                                )}
                                            </div>
                                            <div className="flex flex-wrap gap-2 text-xs">
                                                <span className="rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 font-medium text-rose-700">
                                                    Ошибки: {liveErrors.filter((item) => item.severity === 'error').length}
                                                </span>
                                                <span className="rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 font-medium text-amber-700">
                                                    Предупреждения:{' '}
                                                    {liveErrors.filter((item) => item.severity === 'warning').length}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="border-b border-border px-4 py-3 text-sm text-muted-foreground">
                                        BibTeX · {lines.length} lines
                                    </div>

                                    <div className="flex min-h-[520px] overflow-hidden">
                                        <div
                                            ref={lineNumbersRef}
                                            className="w-14 overflow-hidden border-r border-border bg-muted/30 py-4 text-right font-mono text-sm text-muted-foreground"
                                        >
                                            {lines.map((_, index) => {
                                                const lineErrors = errorsByLine[index + 1] ?? [];
                                                const title = lineErrors.map((item) => item.message).join(' | ');

                                                return (
                                                    <div
                                                        key={index}
                                                        className={`h-6 px-3 ${
                                                            lineErrors.some((item) => item.severity === 'error')
                                                                ? 'font-semibold text-rose-600'
                                                                : lineErrors.length
                                                                  ? 'font-semibold text-amber-600'
                                                                  : ''
                                                        }`}
                                                        title={title}
                                                    >
                                                        {index + 1}
                                                    </div>
                                                );
                                            })}
                                        </div>

                                        <div className="relative min-w-0 flex-1">
                                            <pre
                                                ref={contentViewerRef}
                                                aria-hidden="true"
                                                className="pointer-events-none absolute inset-0 overflow-hidden whitespace-pre p-4 font-mono text-sm leading-6 text-transparent"
                                                dangerouslySetInnerHTML={{
                                                    __html: highlightedLines.join('\n'),
                                                }}
                                            />
                                            <textarea
                                                ref={textareaRef}
                                                spellCheck={false}
                                                value={updateForm.data.bib_content}
                                                onChange={(event) => {
                                                    const nextValue = event.target.value;
                                                    updateForm.setData('bib_content', nextValue);
                                                    setTypeTrigger(
                                                        getTypeTrigger(
                                                            nextValue,
                                                            event.target.selectionStart ?? nextValue.length,
                                                        ),
                                                    );
                                                    analyzeText(nextValue);
                                                }}
                                                onClick={(event) =>
                                                    setTypeTrigger(
                                                        getTypeTrigger(
                                                            event.currentTarget.value,
                                                            event.currentTarget.selectionStart ??
                                                                event.currentTarget.value.length,
                                                        ),
                                                    )
                                                }
                                                onKeyUp={(event) =>
                                                    setTypeTrigger(
                                                        getTypeTrigger(
                                                            event.currentTarget.value,
                                                            event.currentTarget.selectionStart ??
                                                                event.currentTarget.value.length,
                                                        ),
                                                    )
                                                }
                                                onBlur={() => {
                                                    window.setTimeout(() => setTypeTrigger(null), 120);
                                                }}
                                                onKeyDown={(event) => {
                                                    if (!suggestedTypes.length) {
                                                        return;
                                                    }

                                                    if (event.key === 'ArrowDown') {
                                                        event.preventDefault();
                                                        setActiveSuggestionIndex(
                                                            (currentIndex) =>
                                                                (currentIndex + 1) %
                                                                suggestedTypes.length,
                                                        );
                                                    }

                                                    if (event.key === 'ArrowUp') {
                                                        event.preventDefault();
                                                        setActiveSuggestionIndex(
                                                            (currentIndex) =>
                                                                (currentIndex - 1 + suggestedTypes.length) %
                                                                suggestedTypes.length,
                                                        );
                                                    }

                                                    if (event.key === 'Tab' || event.key === 'Enter') {
                                                        event.preventDefault();
                                                        applyTypeSuggestion(
                                                            suggestedTypes[activeSuggestionIndex],
                                                        );
                                                    }

                                                    if (event.key === 'Escape') {
                                                        setTypeTrigger(null);
                                                    }
                                                }}
                                                onScroll={syncScroll}
                                                placeholder={`@book{key,
  title = {Название},
  author = {Автор},
  year = {2024}
}`}
                                                className="relative h-full min-h-[520px] w-full resize-none overflow-auto bg-transparent p-4 font-mono text-sm leading-6 text-foreground outline-none"
                                            />
                                            {suggestedTypes.length > 0 && (
                                                <div className="absolute right-4 bottom-4 z-20 w-full max-w-md rounded-2xl border border-border bg-background/95 p-2 shadow-xl backdrop-blur">
                                                    <div className="px-2 pb-2 text-[11px] font-medium uppercase tracking-[0.12em] text-muted-foreground">
                                                        BibTeX types from database
                                                    </div>
                                                    <div className="space-y-1">
                                                        {suggestedTypes.map((type, index) => (
                                                            <button
                                                                key={type.id}
                                                                type="button"
                                                                onMouseDown={(event) => {
                                                                    event.preventDefault();
                                                                    applyTypeSuggestion(type);
                                                                }}
                                                                className={`flex w-full items-start justify-between gap-3 rounded-xl px-3 py-2 text-left transition ${
                                                                    index === activeSuggestionIndex
                                                                        ? 'bg-accent text-accent-foreground'
                                                                        : 'hover:bg-muted'
                                                                }`}
                                                            >
                                                                <div className="min-w-0">
                                                                    <div className="font-mono text-sm font-semibold">
                                                                        @{type.name}
                                                                    </div>
                                                                    <div className="mt-1 text-xs text-muted-foreground">
                                                                        {type.fields.length
                                                                            ? type.fields.join(', ')
                                                                            : 'No fields configured'}
                                                                    </div>
                                                                </div>
                                                                <div className="shrink-0 rounded-full border border-border px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                                                                    {type.fields.length} fields
                                                                </div>
                                                            </button>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={updateForm.processing}
                                    className="mt-4 inline-flex items-center justify-center gap-2 rounded-2xl bg-foreground px-5 py-3 text-sm font-semibold text-background transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    {!analysis && <FilePlus2 className="h-4 w-4" />}
                                    {analysis ? 'Сохранить изменения' : 'Создать и проверить'}
                                </button>
                            </form>

                            <div className="space-y-4">
                                {analysis ? (
                                    <>
                                        <section
                                            className={`rounded-2xl border p-5 ${
                                                isSuccessVerdict
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                                                    : 'border-rose-200 bg-rose-50 text-rose-900'
                                            }`}
                                        >
                                            <div className="flex items-center gap-2 text-sm font-semibold">
                                                {isSuccessVerdict ? (
                                                    <CheckCircle2 className="h-4 w-4" />
                                                ) : (
                                                    <TriangleAlert className="h-4 w-4" />
                                                )}
                                                <span>Итог проверки</span>
                                            </div>
                                            <p className="mt-3 text-sm leading-6">{verdict}</p>
                                        </section>

                                        <section className="rounded-2xl border border-border bg-background p-5">
                                            <h2 className="text-sm font-semibold text-foreground">Метрики</h2>
                                            <div className="mt-4 space-y-3">
                                                {Object.entries(metrics).length ? (
                                                    Object.entries(metrics).map(([key, value]) => (
                                                        <div
                                                            key={key}
                                                            className="flex items-center justify-between gap-3 border-b border-border pb-3 text-sm last:border-b-0 last:pb-0"
                                                        >
                                                            <span className="text-muted-foreground">
                                                                {metricLabels[key] ?? key}
                                                            </span>
                                                            <span className="font-semibold text-foreground">
                                                                {value}
                                                                {key === 'api_average_similarity' ? '%' : ''}
                                                            </span>
                                                        </div>
                                                    ))
                                                ) : (
                                                    <p className="text-sm text-muted-foreground">
                                                        Метрики пока недоступны.
                                                    </p>
                                                )}
                                            </div>
                                        </section>

                                        <section className="rounded-2xl border border-border bg-background p-5">
                                            <h2 className="text-sm font-semibold text-foreground">
                                                Поиск в OpenAlex
                                            </h2>
                                            <div className="mt-4 space-y-3">
                                                {Object.entries(entries).length ? (
                                                    Object.entries(entries).map(([key, entry]) => {
                                                        const found = entry.api_check?.found;
                                                        const similarity =
                                                            entry.api_check?.similarity !== undefined
                                                                ? Math.round(entry.api_check.similarity)
                                                                : null;

                                                        return (
                                                            <div
                                                                key={key}
                                                                className={`rounded-2xl border p-4 ${
                                                                    found
                                                                        ? 'border-emerald-200 bg-emerald-50'
                                                                        : 'border-rose-200 bg-rose-50'
                                                                }`}
                                                            >
                                                                <div className="text-sm font-semibold text-foreground">
                                                                    {key}
                                                                </div>
                                                                <div className="mt-1 text-xs text-muted-foreground">
                                                                    {entry.fields?.title ?? 'Без названия'}
                                                                </div>
                                                                <div className="mt-3 text-xs">
                                                                    {similarity !== null ? (
                                                                        <span className="rounded-full border border-border bg-card px-2.5 py-1 font-semibold text-foreground">
                                                                            {similarity}%
                                                                        </span>
                                                                    ) : (
                                                                        <span className="text-muted-foreground">
                                                                            {entry.api_check?.message ?? 'Не найдено'}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        );
                                                    })
                                                ) : (
                                                    <p className="text-sm text-muted-foreground">
                                                        Результаты поиска в OpenAlex пока недоступны.
                                                    </p>
                                                )}
                                            </div>
                                        </section>
                                    </>
                                ) : (
                                    <section className="flex min-h-[300px] items-center justify-center rounded-2xl border border-dashed border-border bg-card p-8 text-center text-muted-foreground">
                                        Ожидание файла для анализа...
                                    </section>
                                )}
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
