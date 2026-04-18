<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --page-bg: #e8f1e2;
            --page-panel: #ffffff;
            --page-border: #dce5d6;
            --page-muted: #6f7c73;
            --page-ink: #243128;
            --accent: #1f7a45;
            --accent-soft: #edf6ef;
            --editor-bg: #ffffff;
            --editor-border: #e5e7eb;
            --editor-line-bg: #f7f8f9;
            --editor-line-text: #9aa3af;
            --editor-text: #1f2937;
        }

        html,
        body {
            min-height: 100%;
            background: var(--page-bg);
        }

        body {
            margin: 0;
            color: var(--page-ink);
            font-family: 'Manrope', sans-serif;
        }

        .app-frame {
            min-height: 100vh;
            padding: 18px;
        }

        .workspace-shell {
            display: flex;
            height: calc(100vh - 36px);
            overflow: hidden;
            border: 1px solid var(--page-border);
            border-radius: 28px;
            background: transparent;
        }

        .workspace-main {
            min-width: 0;
            flex: 1;
            min-height: 0;
            padding: 14px 18px 18px;
            overflow-x: auto;
            overflow-y: auto;
        }

        .workspace-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .workspace-breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: 1px solid rgba(111, 124, 115, 0.14);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.6);
            color: var(--page-muted);
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .sidebar-toggle:hover {
            background: #ffffff;
            color: var(--page-ink);
            border-color: rgba(111, 124, 115, 0.24);
        }

        .workspace-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .workspace-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            color: var(--page-muted);
            text-decoration: none;
            font-size: 0.8125rem;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .workspace-link:hover {
            background: rgba(255, 255, 255, 0.5);
            color: var(--page-ink);
        }

        .editor-shell {
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
            border: 1px solid var(--editor-border);
            border-radius: 18px;
            background: var(--editor-bg);
        }

        .editor-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--editor-border);
            background: #fafafa;
        }

        .editor-toolbar-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #7b8794;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.76rem;
            letter-spacing: 0.02em;
        }

        .editor-container {
            display: flex;
            flex: 1;
            min-height: 460px;
            min-width: 0;
            background: #ffffff;
            overflow: hidden;
        }

        .line-numbers {
            width: 3.75rem;
            flex-shrink: 0;
            height: 100%;
            padding: 1rem 0;
            border-right: 1px solid var(--editor-border);
            background: var(--editor-line-bg);
            color: var(--editor-line-text);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.84rem;
            line-height: 1.5rem;
            text-align: right;
            user-select: none;
            z-index: 2;
            overflow: hidden;
        }

        .line-numbers > div {
            position: relative;
            height: 1.5rem;
            padding-right: 0.75rem;
            cursor: help;
        }

        .line-numbers > div[data-error]:hover::after {
            content: attr(data-error);
            position: absolute;
            left: calc(100% + 8px);
            top: -4px;
            width: 240px;
            padding: 10px 12px;
            border-radius: 12px;
            background: rgba(31, 41, 55, 0.96);
            color: #f8fafc;
            font-size: 12px;
            line-height: 1.45;
            white-space: pre-wrap;
            text-align: left;
            z-index: 100;
            box-shadow: 0 14px 30px -20px rgba(15, 23, 42, 0.7);
        }

        .line-numbers .error-line {
            color: #dc2626;
            font-weight: 600;
        }

        .line-numbers .warning-line {
            color: #d97706;
            font-weight: 600;
        }

        .editor-wrapper {
            position: relative;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }

        #contentViewer {
            position: absolute;
            inset: 0;
            height: 100%;
            margin: 0;
            padding: 1rem 1.1rem;
            color: transparent;
            pointer-events: none;
            white-space: pre;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.9rem;
            line-height: 1.5rem;
            z-index: 1;
            overflow: hidden;
        }

        textarea.line-editor {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            padding: 1rem 1.1rem;
            border: none;
            outline: none;
            resize: none;
            overflow-x: auto;
            overflow-y: auto;
            white-space: pre;
            background: transparent !important;
            color: var(--editor-text);
            caret-color: #111827;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.9rem;
            line-height: 1.5rem;
        }

        textarea.line-editor::selection {
            background: rgba(34, 197, 94, 0.14);
        }

        .error-highlight {
            border-bottom: 2px solid rgba(248, 113, 113, 0.95);
            background: rgba(248, 113, 113, 0.18);
        }

        .warning-highlight {
            border-bottom: 2px solid rgba(251, 191, 36, 0.95);
            background: rgba(251, 191, 36, 0.18);
        }

        .app-sidebar {
            width: 260px;
            height: 100%;
            transition: width 0.22s ease, padding 0.22s ease, border-color 0.22s ease;
        }

        .app-sidebar-inner {
            height: 100%;
        }

        .sidebar-collapsed .app-sidebar {
            width: 88px;
            padding-left: 12px;
            padding-right: 12px;
        }

        .editor-actions-panel {
            top: -0.1rem;
        }

        .editor-report-panel {
            position: sticky;
            top: 0;
            max-height: calc(100vh - 84px);
            overflow-y: auto;
        }

        .editor-report-panel::-webkit-scrollbar {
            width: 6px;
        }

        .editor-report-panel::-webkit-scrollbar-thumb {
            background: #d5ddd1;
            border-radius: 999px;
        }

        @media (max-width: 1024px) {
            body {
                display: block;
            }

            .app-frame {
                padding: 12px;
            }

            .workspace-shell {
                height: calc(100vh - 24px);
            }

            .workspace-main {
                padding: 12px;
            }

            .editor-report-panel {
                position: static;
                max-height: none;
                overflow-y: visible;
            }
        }

        @media (max-width: 768px) {
            .workspace-header {
                flex-direction: row;
                align-items: center;
            }

            .workspace-actions {
                width: auto;
            }

            .editor-container {
                min-height: 320px;
            }

            .line-numbers {
                width: 3.2rem;
            }

            .editor-actions-panel {
                position: static;
                margin-left: 0;
                margin-right: 0;
                padding-left: 0;
                padding-right: 0;
                background: transparent;
                backdrop-filter: none;
            }
        }
    </style>
</head>
<body>
    <div class="app-frame">
        <div id="workspaceShell" class="workspace-shell">
            <x-sidebar />

            <main class="workspace-main flex-1">
                <header class="workspace-header">
                    <div class="workspace-breadcrumb">
                        <button
                            id="sidebarToggle"
                            type="button"
                            class="sidebar-toggle"
                            aria-label="Скрыть боковую панель"
                            aria-expanded="true"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                    <div class="workspace-actions">
                        @guest
                            <a href="{{ route('guest.login') }}" class="workspace-link">
                                Войти
                            </a>
                        @endguest
                        <span class="workspace-link">Справка</span>
                    </div>
                </header>

                <div>
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const workspaceShell = document.getElementById('workspaceShell');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const storageKey = 'bibcheck-sidebar-collapsed';

            if (!workspaceShell || !sidebarToggle) {
                return;
            }

            const applyState = (collapsed) => {
                workspaceShell.classList.toggle('sidebar-collapsed', collapsed);
                sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
                sidebarToggle.setAttribute('aria-label', collapsed ? 'Показать боковую панель' : 'Скрыть боковую панель');
            };

            const savedState = window.localStorage.getItem(storageKey) === 'true';
            applyState(savedState);

            sidebarToggle.addEventListener('click', () => {
                const collapsed = !workspaceShell.classList.contains('sidebar-collapsed');
                applyState(collapsed);
                window.localStorage.setItem(storageKey, String(collapsed));
            });

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-sidebar-expand-target]');

                if (!trigger) {
                    return;
                }

                const targetId = trigger.getAttribute('data-sidebar-expand-target');
                const targetMenu = targetId ? document.getElementById(targetId) : null;
                const isCollapsed = workspaceShell.classList.contains('sidebar-collapsed');

                if (!isCollapsed || !targetMenu) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                applyState(false);
                window.localStorage.setItem(storageKey, 'false');

                window.setTimeout(() => {
                    targetMenu.classList.remove('hidden');
                }, 180);
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
