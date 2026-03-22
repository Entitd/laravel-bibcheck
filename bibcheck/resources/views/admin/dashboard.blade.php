@extends('admin.layouts.app')

@section('title', 'Дашборд')

@section('content')
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold mb-8 text-gray-800">Обзор системы BibCheck</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            {{-- Карточка: Всего файлов --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                <div class="text-sm font-medium text-gray-500 uppercase italic">Загружено файлов</div>
                <div class="text-3xl font-bold text-gray-800 mt-1">
                    {{ \App\Models\BibFile::count() }}
                </div>
            </div>

            {{-- Карточка: Типы записей --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-indigo-500">
                <div class="text-sm font-medium text-gray-500 uppercase italic">Типов записей</div>
                <div class="text-3xl font-bold text-gray-800 mt-1">
                    {{ \App\Models\BibtexTypeEntry::count() }}
                </div>
            </div>

            {{-- Карточка: Ошибки валидации --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-red-500">
                <div class="text-sm font-medium text-gray-500 uppercase italic">Всего ошибок</div>
                <div class="text-3xl font-bold text-gray-800 mt-1 text-red-600">
                    {{ \App\Models\ValidationError::count() ?? 0 }}
                </div>
            </div>

            {{-- Карточка: Справочник полей --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                <div class="text-sm font-medium text-gray-500 uppercase italic">Полей в базе</div>
                <div class="text-3xl font-bold text-gray-800 mt-1">
                    {{ \App\Models\BibtexField::count() }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Последние загрузки (заглушка) --}}
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="text-lg font-bold mb-4">Последние загруженные файлы</h3>
                <div class="text-sm text-gray-500 italic">Здесь будет список последних .bib файлов от студентов...</div>
            </div>

            {{-- Быстрые действия --}}
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="text-lg font-bold mb-4">Быстрые действия</h3>
                <div class="flex flex-col space-y-3">
                    <a href="{{ route('admin.bibtex.index') }}" class="text-blue-600 hover:underline">→ Настроить структуру полей</a>
                    <a href="{{ route('admin.department.index') }}" class="text-blue-600 hover:underline">→ Изменить критерии кафедры</a>
                </div>
            </div>
        </div>
    </div>
@endsection
