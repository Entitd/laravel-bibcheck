<x-layouts.app>
    @section('breadcrumb', 'Редактор BibTeX')

    {{-- Кнопки загрузки --}}
    <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
        <div class="border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center hover:border-green-500 hover:bg-green-50 cursor-pointer transition">
            + Создать пустой проект
        </div>
        <form action="{{ route('bib.upload.blade') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label class="border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center hover:border-blue-500 hover:bg-blue-50 cursor-pointer transition block">
                <input type="file" name="bib_file" class="hidden" onchange="this.form.submit()">
                <span>↑ Загрузить файл для проверки</span>
            </label>
        </form>
    </div>

    @if(session('analysis'))
        <form id="editForm" action="{{ route('bib.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Левая часть: Редактор --}}
                <div class="lg:col-span-2">
                    <x-bib-editor
                        :content="old('bib_content', session('analysis')['raw_content'] ?? '')"
                        :errors="session('analysis')['errors'] ?? []"
                        :filename="session('analysis')['original_filename'] ?? 'Без названия'"
                    />
                </div>

                {{-- Правая часть: Отчет --}}
                <div class="space-y-6">
                    <div class="p-5 rounded-2xl {{ str_contains(session('analysis')['course_comparison_result'], 'соответствует') ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
                        <h3 class="font-bold mb-2">Вердикт:</h3>
                        <p class="text-sm">{{ session('analysis')['course_comparison_result'] }}</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <h3 class="font-bold mb-4">Метрики:</h3>
                        @foreach(session('analysis')['aggregated_metrics'] as $label => $value)
                            <div class="flex justify-between text-sm py-2 border-b border-gray-50 last:border-0">
                                <span class="text-gray-500">{{ $label }}</span>
                                <span class="font-bold text-blue-600">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>

                    <button type="submit" class="w-full bg-[#00B368] hover:bg-[#009957] text-white py-4 rounded-2xl font-bold shadow-lg transition-all">
                        Сохранить изменения
                    </button>
                </div>
            </div>
        </form>
    @else
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">📄</p>
            <p>Ожидание файла для анализа...</p>
        </div>
    @endif
</x-layouts.app>
