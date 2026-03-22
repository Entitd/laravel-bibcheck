@extends('admin.layouts.app')

@section('title', 'Конфигуратор BibTeX')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Конфигуратор структуры BibTeX</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-8">
                {{-- Форма добавления типа --}}
                @include('admin.bibtex.partials.type-form')

                {{-- Список всех доступных полей в системе --}}
                @include('admin.bibtex.partials.field-list')
            </div>

            <div class="lg:col-span-2">
                <h2 class="text-xl font-semibold mb-4">Существующие типы записей</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($types as $type)
                        <div class="bg-white p-5 rounded shadow border-t-4 border-blue-500 flex flex-col justify-between">
                            <div>
                                <h3 class="font-bold text-lg uppercase mb-3 flex justify-between">
                                    {{ $type->name_type_entry }}
                                </h3>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach($type->fields as $field)
                                        <span class="bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded border border-blue-100">
                                        {{ $field->name_field }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>

                            <form action="{{ route('admin.bibtex.destroy', $type->id) }}" method="POST"
                                  onsubmit="return confirm('Удалить тип {{ $type->name_type_entry }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 text-xs hover:underline uppercase font-bold">
                                    Удалить тип
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
