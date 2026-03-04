<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>BibCheck — Blade Mode</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">
        <h1 class="text-2xl font-bold mb-6">Проверка BIB-файла (Blade)</h1>

        <form action="{{ route('bib.upload.blade') }}" method="POST" enctype="multipart/form-data" class="mb-10">
@csrf
<div class="flex items-center gap-4">
    <input type="file" name="bib_file" required class="border p-2 rounded w-full">
    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Отправить</button>
</div>
</form>

@if(session('analysis'))
    <div class="space-y-6">
        <div class="p-4 bg-green-50 border-l-4 border-green-500">
            <h2 class="font-bold">Вердикт:</h2>
            <p>{{ session('analysis')['course_comparison_result'] }}</p>
        </div>

        <div class="grid grid-cols-4 gap-4">
            @foreach(session('analysis')['aggregated_metrics'] as $label => $value)
                <div class="bg-gray-50 p-3 border rounded text-center">
                    <span class="text-xs text-gray-500 block">{{ $label }}</span>
                    <span class="text-xl font-bold">{{ $value }}</span>
                </div>
            @endforeach
        </div>

        <div class="p-4 bg-red-50 border-l-4 border-red-500">
            <h2 class="font-bold text-red-700 mb-2">Ошибки ({{ count(session('analysis')['errors']) }}):</h2>
            <div class="max-h-60 overflow-y-auto text-sm text-red-600">
                @foreach(session('analysis')['errors'] as $error)
                    <p class="mb-1">• {{ $error }}</p>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    </div>
    </body>
    </html>
