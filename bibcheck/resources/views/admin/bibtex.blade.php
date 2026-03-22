<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>BibCheck — Blade Mode</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
    <h1 class="text-2xl font-bold mb-6">Админ-панель BibCheck</h1>

    <section class="bg-white p-6 rounded shadow mb-10">
        <h2 class="text-xl mb-4">Добавить новый тип записи</h2>
        <form action="{{ route('admin.bibtex.store') }}" method="POST">
            @csrf
            <input type="text" name="name_type_entry" placeholder="Название (напр. thesis)" class="border p-2 rounded mr-2">

            <h3 class="mt-4 mb-2 font-bold">Выберите поля для этого типа:</h3>
            <div class="grid grid-cols-3 gap-2">
                @foreach($allFields as $field)
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="field_ids[]" value="{{ $field->id }}">
                        <span>{{ $field->name_field }}</span>
                    </label>
                @endforeach
            </div>

            <button type="submit" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">Создать тип</button>
        </form>
    </section>



    <section class="bg-white p-6 rounded shadow mb-10">
        <h2 class="text-xl mb-4">Добавить новое поле в систему</h2>
        <form action="{{ route('admin.fields.store') }}" method="POST" class="flex items-center">
            @csrf
            <input type="text"
                   name="name_field"
                   placeholder="Напр: journal, doi, volume"
                   class="border p-2 rounded mr-2 w-1/3 @error('name_field') border-red-500 @enderror"
                   value="{{ old('name_field') }}">
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded shadow hover:bg-green-600">
                + Добавить поле
            </button>
        </form>
        @error('name_field')
        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
        @enderror
    </section>

    <section class="bg-white p-6 rounded shadow">
        <h2 class="text-xl mb-4">Существующие поля</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($fields as $field)
                <div class="flex items-center bg-gray-100 px-3 py-1 rounded-full border border-gray-300">
                    <span class="mr-2 text-gray-700">{{ $field->name_field }}</span>

                    <form action="{{ route('admin.fields.destroy', $field->id) }}" method="POST"
                          onsubmit="return confirm('Удалить это поле? Оно может исчезнуть из типов записей!');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 font-bold">&times;</button>
                    </form>
                </div>
            @endforeach
        </div>
    </section>



    <h2 class="text-xl mb-4">Существующие типы записей:</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($types as $type)
            <form action=" {{ route('admin.bibtex.destroy', $type) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="bg-white p-4 rounded shadow border-t-4 border-blue-500">
                    <h3 class="font-bold text-lg uppercase mb-2">{{ $type->name_type_entry }}</h3>
                    <ul class="text-sm text-gray-600">
                        @foreach($type->fields as $field)
                            <li class="bg-gray-50 mb-1 px-2 py-1 rounded border">
                                {{ $field->name_field }}
                                <span class="text-xs text-gray-400">(order: {{ $field->pivot->sort_order }})</span>
                            </li>
                        @endforeach
                    </ul>
                    <button class="mt-4 text-red-500 text-xs hover:underline" type="submit">Удалить тип</button>
                </div>
            </form>
        @endforeach
    </div>
</body>
</html>
