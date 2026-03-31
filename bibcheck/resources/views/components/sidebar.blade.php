<aside class="w-[280px] border-right border-gray-200 bg-white flex flex-col p-4 shrink-0 border-r">
    <div class="flex items-center gap-2 mb-8 px-2">
        <div class="w-8 h-8 bg-[#004D33] rounded-md"></div>
        <span class="font-bold text-xl tracking-tight text-[#004D33]">BIBCHECK.RU</span>
    </div>

    <div class="relative mb-6">
        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">🔍</span>
        <input type="text" placeholder="Найти файл" class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
    </div>

    <nav class="flex-1">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-2">История</h3>
        <div class="space-y-1">
            {{-- Здесь можно прокинуть коллекцию файлов через @foreach --}}
            <div class="p-2 px-3 text-sm text-gray-700 bg-gray-100 rounded-lg font-medium cursor-pointer">Название_файла.bib</div>
            <div class="p-2 px-3 text-sm text-gray-600 hover:bg-gray-50 rounded-lg cursor-pointer">Архив_2025.bib</div>
        </div>
    </nav>

    <div class="pt-4 border-t border-gray-100 mt-auto">
        <div class="flex items-center gap-3 p-2 cursor-pointer hover:bg-gray-50 rounded-lg">
            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center font-bold text-gray-600">П</div>
            <span class="text-sm font-medium">Пользователь</span>
            <span class="ml-auto text-gray-400">📥</span>
        </div>
    </div>
</aside>
