<section class="bg-white p-6 rounded shadow">
    <h2 class="text-lg font-bold mb-4 text-gray-700">Справочник полей</h2>

    {{-- Форма быстрого добавления поля --}}
    <form action="{{ route('admin.fields.store') }}" method="POST" class="flex gap-2 mb-6">
        @csrf
        <input type="text" name="name_field" placeholder="doi, isbn..."
               class="flex-1 border p-2 rounded text-sm @error('name_field') border-red-500 @enderror">
        <button type="submit" class="bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700">
            <span class="text-xl leading-none">+</span>
        </button>
    </form>

    {{-- Облако тегов с полями --}}
    <div class="flex flex-wrap gap-2">
        @foreach($fields as $field)
            <div class="group flex items-center bg-gray-100 pl-3 pr-2 py-1 rounded-full border border-gray-200 hover:bg-white hover:border-red-300 transition">
                <span class="text-sm text-gray-600 mr-2">{{ $field->name_field }}</span>
                <form action="{{ route('admin.fields.destroy', $field->id) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-gray-400 group-hover:text-red-500 font-bold hover:scale-125 transition">
                        &times;
                    </button>
                </form>
            </div>
        @endforeach
    </div>
</section>
