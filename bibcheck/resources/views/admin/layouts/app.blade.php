<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — BibCheck</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">

<div class="flex h-screen overflow-hidden">

    <aside class="w-64 bg-slate-900 text-white flex-shrink-0 hidden md:flex flex-col">
        <div class="p-6 flex items-center space-x-3">
            <div class="bg-blue-600 p-2 rounded-lg">
                <i class="fas fa-book-bookmark text-xl"></i>
            </div>
            <span class="text-xl font-bold tracking-wider">BibCheck</span>
        </div>

        <nav class="flex-1 px-4 space-y-2 mt-4">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center space-x-3 p-3 rounded-lg transition {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 text-slate-400' }}">
                <i class="fas fa-chart-line w-5"></i>
                <span>Дашборд</span>
            </a>

            <a href="{{ route('admin.bibtex.index') }}"
               class="flex items-center space-x-3 p-3 rounded-lg transition {{ request()->routeIs('admin.bibtex.*') ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 text-slate-400' }}">
                <i class="fas fa-tags w-5"></i>
                <span>Структура BibTeX</span>
            </a>

            <a href="{{ route('admin.department.index') }}"
               class="flex items-center space-x-3 p-3 rounded-lg transition {{ request()->routeIs('admin.department.*') ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 text-slate-400' }}">
                <i class="fas fa-university w-5"></i>
                <span>Критерии кафедры</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <div class="flex items-center space-x-3 p-2">
                <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs">
                    AS
                </div>
                <div class="text-sm">
                    <p class="font-medium">Amal S.</p>
                    <p class="text-xs text-slate-500">Administrator</p>
                </div>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-y-auto">

        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 flex-shrink-0">
            <div class="text-sm text-gray-500 italic">
                Панель управления / @yield('title')
            </div>

            <div class="flex items-center space-x-4">
                {{-- Здесь можно добавить уведомления или выход --}}
                <button class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-bell"></i>
                </button>
                <form action="/logout" method="POST">
                    @csrf
                    <button type="submit" class="text-sm text-gray-600 hover:text-red-600 font-medium">
                        Выход
                    </button>
                </form>
            </div>
        </header>

        <main class="p-8">
            {{-- Вывод Flash-сообщений (Success / Error) --}}
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 shadow-sm rounded-r">
                    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 shadow-sm rounded-r">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

    </div>
</div>

{{-- Скрипты, если понадобятся --}}
@stack('scripts')
</body>
</html>
