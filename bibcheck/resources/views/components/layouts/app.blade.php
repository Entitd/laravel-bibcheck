<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script> {{-- ПРОВЕРЬ ЭТУ СТРОКУ --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8F9FA; }
        /* Контейнер редактора */
        .editor-container {
            display: flex;
            /*background-color: #111827; !* Темный фон как в VS Code *!*/
            background-color: #c4d4f6;
            border-radius: 0.75rem;
            border: 1px solid #374151;
            min-height: 550px;
            position: relative;
            overflow: hidden;
        }

        /* Номера строк */
        .line-numbers {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
            line-height: 1.5rem;
            padding: 1.25rem 0;
            width: 3.5rem;
            /*background-color: #1f2937;*/
            background-color: #bfceea;
            border-right: 1px solid #374151;
            color: #9ca3af;
            user-select: none;
            z-index: 30;
            text-align: right;
        }

        .line-numbers > div {
            padding-right: 0.75rem;
            height: 1.5rem;
            cursor: help;
            position: relative;
        }

        /* Тултип ошибки */
        .line-numbers > div[data-error]:hover::after {
            content: attr(data-error);
            position: absolute; left: 100%; top: 0; margin-left: 10px;
            background: #111827; color: #f3f4f6; padding: 10px;
            border-radius: 6px; font-size: 12px; width: 280px;
            z-index: 100; border: 1px solid #4b5563;
            white-space: pre-wrap; text-align: left;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        }

        .line-numbers .error-line { color: #ef4444; font-weight: bold; }
        .line-numbers .warning-line { color: #f59e0b; font-weight: bold; }

        /* Область текста */
        .editor-wrapper { position: relative; flex: 1; overflow: hidden; }

        #contentViewer {
            position: absolute; inset: 0; padding: 1.25rem;
            font-family: 'JetBrains Mono', monospace; font-size: 0.875rem;
            line-height: 1.5rem; white-space: pre; color: transparent;
            z-index: 1; pointer-events: none;
        }

        textarea.line-editor {
            position: relative; z-index: 2; width: 100%; padding: 1.25rem;
            background: transparent !important; color: #f3f4f6;
            font-family: 'JetBrains Mono', monospace; font-size: 0.875rem;
            line-height: 1.5rem; border: none; outline: none;
            resize: none; white-space: pre; overflow-x: auto;
        }

        /* Волнистые линии */
        .error-highlight { border-bottom: 2px wavy #ef4444; background: rgba(239, 68, 68, 0.15); }
        .warning-highlight { border-bottom: 2px wavy #f59e0b; background: rgba(245, 158, 11, 0.15); }

    </style>


</head>
<body class="flex min-h-screen"> {{-- flex нужен для сайдбара --}}

<x-sidebar /> {{-- Проверь, что этот файл есть в components/sidebar.blade.php --}}

<main class="flex-1 p-8 overflow-y-auto">
    <header class="flex justify-between items-center mb-8 text-sm text-gray-500">
        <span>📋 @yield('breadcrumb')</span>
        <span>Справка ⓘ</span>
    </header>

    {{ $slot }} {{-- Сюда вставится контент страницы --}}
</main>

@stack('scripts')
</body>
</html>
