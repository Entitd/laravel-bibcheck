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



{{--        @if(session('analysis'))--}}
{{--            <div class="space-y-6">--}}
{{--                <div class="p-4 bg-green-50 border-l-4 border-green-500">--}}
{{--                    <h2 class="font-bold">Вердикт:</h2>--}}
{{--                    <p>{{ session('analysis')['course_comparison_result'] }}</p>--}}
{{--                </div>--}}

{{--                <div class="grid grid-cols-4 gap-4">--}}
{{--                    @foreach(session('analysis')['aggregated_metrics'] as $label => $value)--}}
{{--                        <div class="bg-gray-50 p-3 border rounded text-center">--}}
{{--                            <span class="text-xs text-gray-500 block">{{ $label }}</span>--}}
{{--                            <span class="text-xl font-bold">{{ $value }}</span>--}}
{{--                        </div>--}}
{{--                    @endforeach--}}
{{--                </div>--}}

{{--                <div class="p-4 bg-red-50 border-l-4 border-red-500">--}}
{{--                    <h2 class="font-bold text-red-700 mb-2">Ошибки ({{ count(session('analysis')['errors']) }}):</h2>--}}
{{--                    <div class="max-h-60 overflow-y-auto text-sm text-red-600">--}}
{{--                        @foreach(session('analysis')['errors'] as $key => $error)--}}
{{--                            @foreach($error as $err)--}}
{{--                                <p class="mb-1">• {{ $err }}</p>--}}
{{--                            @endforeach--}}

{{--                        @endforeach--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        @endif--}}



        @if(session('analysis'))
            <div class="mt-10">
                <h2 class="text-xl font-bold mb-4">Визуальный редактор с ошибками:</h2>
                <div class="relative bg-gray-900 text-gray-100 p-6 rounded-lg font-mono text-sm overflow-x-auto leading-relaxed">
                    @php
                        $rawText = session('analysis')['raw_content'];
                        $errors = session('analysis')['errors'];

                        // Группируем ошибки по строкам для быстрого поиска
                        $errorsByLine = [];
                        foreach ($errors as $err) {
                            $errorsByLine[$err['line']][] = $err;
                        }

                        $lines = explode("\n", $rawText);
                    @endphp

                    @foreach($lines as $index => $line)
                        @php
                            $lineNum = $index + 1;
                            $hasErrors = isset($errorsByLine[$lineNum]);
                        @endphp

                        <div class="flex group {{ $hasErrors ? 'bg-red-900/30' : '' }}">
                            <span class="w-10 inline-block text-gray-500 select-none">{{ $lineNum }}</span>

                            <span class="relative">
                        @if(!$hasErrors)
                                    {{ $line }}
                                @else
                                    @php
                                        // Сортируем ошибки в строке по колонке (от конца к началу, чтобы не ломать офсеты при вставке тегов)
                                        $currentLineErrors = $errorsByLine[$lineNum];
                                        usort($currentLineErrors, fn($a, $b) => $b['column'] <=> $a['column']);

                                        $tempLine = e($line); // Экранируем HTML
                                        foreach ($currentLineErrors as $error) {
                                            $col = $error['column'] - 1;
                                            $len = $error['length'] ?? 1;

                                            // Вставляем span с тултипом
                                            $part1 = mb_substr($tempLine, 0, $col);
                                            $part2 = mb_substr($tempLine, $col, $len);
                                            $part3 = mb_substr($tempLine, $col + $len);

                                            $tempLine = $part1 . '<span class="bg-red-600 text-white cursor-help group/err relative" title="' . e($error['message']) . '">' . $part2 .
                                                        '<span class="hidden group-hover/err:block absolute bottom-full left-0 mb-2 w-64 bg-black text-xs p-2 rounded shadow-xl z-50">' . e($error['message']) . '</span>' .
                                                        '</span>' . $part3;
                                        }
                                    @endphp
                                    {!! $tempLine !!}
                                @endif
                    </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif


    </div>
</body>
</html>
