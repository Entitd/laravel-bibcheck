@extends('admin.layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto">
        <h1 class="text-2xl font-bold mb-8">Критерии кафедры и ГОСТ</h1>

        <section class="bg-white p-6 rounded shadow mb-10">
            <h2 class="text-xl font-semibold mb-6">Минимальные требования по курсам</h2>

            <form action="{{ route('admin.department.updateRequirements') }}" method="POST">
                @csrf
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                        <tr class="bg-gray-50 text-gray-600 text-sm uppercase">
                            <th class="p-3 border">Курс</th>
                            <th class="p-3 border">Всего источников</th>
                            <th class="p-3 border">Иностр. яз.</th>
                            <th class="p-3 border">Периодика</th>
                            <th class="p-3 border">XXI век (>=2001)</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($requirements as $req)
                            <tr>
                                <td class="p-3 border font-bold text-center bg-gray-50">{{ $req->course_number }}</td>
                                <td class="p-3 border">
                                    <input type="number" name="req[{{ $req->id }}][min_total_quantity]" value="{{ $req->min_total_quantity }}" class="w-full p-1 border rounded">
                                </td>
                                <td class="p-3 border">
                                    <input type="number" name="req[{{ $req->id }}][min_foreign_lang]" value="{{ $req->min_foreign_lang }}" class="w-full p-1 border rounded">
                                </td>
                                <td class="p-3 border">
                                    <input type="number" name="req[{{ $req->id }}][min_current_periodicals]" value="{{ $req->min_current_periodicals }}" class="w-full p-1 border rounded">
                                </td>
                                <td class="p-3 border">
                                    <input type="number" name="req[{{ $req->id }}][min_21st_century]" value="{{ $req->min_21st_century }}" class="w-full p-1 border rounded">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    Сохранить настройки курсов
                </button>
            </form>
        </section>

        <section class="bg-white p-6 rounded shadow">
            <h2 class="text-xl font-semibold mb-6">Обязательные поля по типам записей</h2>
            <p class="text-sm text-gray-500 mb-6 italic">* Эти поля BibCheck будет требовать в обязательном порядке для каждого типа.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($types as $type)
                    <div class="border rounded p-4 bg-gray-50">
                        <h3 class="font-bold text-blue-800 uppercase mb-3 flex justify-between">
                            {{ $type->name_type_entry }}
                            <span class="text-xs font-normal text-gray-400">ID: {{ $type->id }}</span>
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            @forelse($type->fields as $field)
                                <span class="bg-white border border-blue-200 text-blue-700 px-2 py-1 rounded text-sm shadow-sm">
                            {{ $field->name_field }}
                        </span>
                            @empty
                                <span class="text-gray-400 text-sm italic">Поля не назначены</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 text-sm">
                Изменить состав обязательных полей можно во вкладке <strong>"Конфигуратор BibTeX"</strong>.
            </div>
        </section>
    </div>
@endsection
