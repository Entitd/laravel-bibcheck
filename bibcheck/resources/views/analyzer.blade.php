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
                                        $chars = mb_str_split($line);
                                        $currentLineErrors = $errorsByLine[$lineNum];

                                        // 1. Группируем ошибки по позиции (column + length),
                                        // чтобы не рисовать матрешку из тегов
                                        $grouped = [];
                                        foreach ($currentLineErrors as $err) {
                                            $key = $err['column'] . '-' . ($err['length'] ?? 1);
                                            $grouped[$key][] = $err['message'];
                                        }

                                        // 2. Сортируем ключи позиций от конца строки к началу
                                        krsort($grouped);

                                        foreach ($grouped as $pos => $messages) {
                                            list($col, $len) = explode('-', $pos);
                                            $col = (int)$col - 1;
                                            $len = (int)$len;

                                            // Объединяем сообщения через разделитель
                                            $fullMessage = implode(" | ", $messages);

                                            // Берем текст, который вызвал ошибку
                                            $originalText = array_slice($chars, $col, $len);
                                            $textToWrap = e(implode('', $originalText));

                                            // Формируем ОДИН span для всех ошибок в этой позиции
                                            $wrapped = '<span class="bg-red-600 text-white cursor-help group/err relative" title="' . e($fullMessage) . '">'
                                                     . $textToWrap
                                                     . '<span class="hidden group-hover/err:block absolute bottom-full left-0 mb-2 w-64 bg-black text-xs p-2 rounded shadow-xl z-50 normal-case font-sans font-normal">'
                                                     . e($fullMessage)
                                                     . '</span></span>';

                                            array_splice($chars, $col, $len, [$wrapped]);
                                        }

                                        // Собираем строку, экранируя только то, что не является нашим HTML
                                        $finalLine = '';
                                        foreach ($chars as $item) {
                                            $finalLine .= (str_contains($item, '<span')) ? $item : e($item);
                                        }
                                    @endphp
                                    {!! $finalLine !!}
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
