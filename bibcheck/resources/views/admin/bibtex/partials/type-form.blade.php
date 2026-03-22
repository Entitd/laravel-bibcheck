<section class="bg-white p-6 rounded shadow">
    <h2 class="text-lg font-bold mb-4 text-gray-700">Создать новый тип</h2>
    <form action="{{ route('admin.bibtex.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-600 mb-1">Название (slug)</label>
            <input type="text" name="name_type_entry" placeholder="напр: thesis"
                   class="w-full border p-2 rounded @error('name_type_entry') border-red-500 @enderror">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-600 mb-2">Выберите обязательные поля:</label>
            <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto p-2 border rounded bg-gray-50">
                @foreach($allFields as $field)
                    <label class="flex items-center space-x-2 text-sm cursor-pointer hover:text-blue-600">
                        <input type="checkbox" name="field_ids[]" value="{{ $field->id }}" class="rounded text-blue-600">
                        <span>{{ $field->name_field }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition font-bold">
            Создать тип
        </button>
    </form>
</section>
