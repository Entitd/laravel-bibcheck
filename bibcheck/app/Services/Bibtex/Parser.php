<?php

namespace App\Services\Bibtex;

use App\DTO\BibEntryDTO;

/**
 * Парсер
 */
class Parser
{
    /**
     * * @param string $text Содержимое .bib файла
     * @return array Результаты анализа: метрики, ошибки и вердикт по курсу
     */
    public function analyze(string $text): array
    {
        $rawBlocks = $this->splitIntoBlocks($text);
        $usedKeys = []; // Это для ключей @article{KEY,
        $parsedData = $this->parseBlocks($rawBlocks,$usedKeys);

        return $parsedData;
    }

    /**
     *  Разрезает текст bib-файла на отдельные блоки записей (от @ до конца блока).
     * * @param string $text Содержимое .bib файла
     * @return array Массив, где каждый элемент - массив строк одной записи.
     */
    private function splitIntoBlocks(string $text): array
    {
        // Используем preg_split, чтобы не терять символы переноса в логике
        $lines = explode("\n", $text);
        $records = [];
        $recordIndex = -1;

        foreach ($lines as $lineNum => $line) {
            $trimmedLine = trim($line);

            // Пропускаем мусор, но сохраняем структуру записи
            if (empty($trimmedLine) || str_starts_with($trimmedLine, '%') || str_contains($trimmedLine, '@comment')) {
                continue;
            }

            if (str_starts_with($trimmedLine, '@')) {
                $recordIndex++;
            }

            if ($recordIndex >= 0) {
                // ВАЖНО: сохраняем $line целиком (с пробелами в начале), а не $trimmedLine
                $records[$recordIndex][$lineNum + 1] = $line;
            }
        }
        return $records;
    }

    /**
     * Проводит синтаксическую проверку каждого блока записи.
     * * @param array $rawBlocks Массив разбитых блоков построчно в таком формате: $rawBlocks[0][4] = "year = {2008},"
     * * @param array $usedKeys Массив пользователем использованных ключей
     * @return array ['error' => [...], 'zapis' => [...]]
     */
    private function parseBlocks(array $rawBlocks, array &$usedKeys): array
    {
        $result = ['error' => [], 'entries' => []]; // Поменяли 'zapis' на 'entries'
        foreach ($rawBlocks as $block) {
            $report = $this->parseEntry($block, $usedKeys);

            if (!empty($report['error'])) {
                $result['error'] = array_merge($result['error'], $report['error']);
            }

            if ($report['entry']) {
                $result['entries'][] = $report['entry']; // Массив объектов DTO
            }
        }
        return $result;
    }

    /**
     * * @param array $recordLines Массив разбитых блоков построчно в таком формате: $rawBlocks[4] = "year = {2008},"
     * * @param array $usedKeys Массив пользователем использованных ключей
     * @return array ['error' => [...], 'zapis' => [...]]
     */
    private function parseEntry(array $recordLines, array &$usedKeys): array
    {
        $lineKeys = array_keys($recordLines);
        $firstLineKey = $lineKeys[0];

        // 1. Работаем с заголовком
        $header = $this->extractHeader($recordLines[$firstLineKey], $firstLineKey);
        if (isset($header['error'])) {
            return ['error' => [$header['error']], 'entry' => null];
        }

        $errors = [];

        // --- ПРОВЕРКА НА УНИКАЛЬНОСТЬ KEY ---
        $currentKey = $header['key'] ?? null;
        if (isset($usedKeys[$currentKey])) {
            $errors[] = [
                'severity' => 'error',
                'message' => "Дублирующийся ключ записи '$currentKey'. Ранее использован на строке {$usedKeys[$currentKey]}.",
                'line' => $firstLineKey,
                'column' => $header['key_column'],
                'length' => strlen($currentKey),
            ];
        } else {
            // Запоминаем строку первого появления ключа
            $usedKeys[$currentKey] = $firstLineKey;
        }
        // ------------------------------------


        // 2. Подготавливаем плоский буфер и карту строк
        $prepared = $this->prepareBuffer($recordLines, $header, $firstLineKey);

        // 3. Извлекаем поля и проверяем синтаксис
        $fieldResults = $this->processFields(
            $prepared['buffer'],
            $prepared['lineMap'],
            $header['type'],
            $firstLineKey
        );

        // 4. Добавляем ошибку заголовка, если нет запятой
        // Внутри parseEntry, заменяем пункт 4:
        if (!str_contains($header['full_match'], ',')) {
            array_unshift($fieldResults['errors'], [
                'severity' => 'syntax',
                'message' => "Пропущена запятая после ключа записи.",
                'line' => $firstLineKey,
                'column' => strlen($header['full_match']),
                'length' => 1
            ]);
        }

        // Собираем финальный результат
//        $zapis = $fieldResults['zapis'];
//        $zapis[$firstLineKey][] = ['type' => $header['type'], 'key' => $header['key']];
//        ksort($zapis);
//
//        return [
//            'error' => array_merge($errors, $fieldResults['errors']),
//            'zapis' => $zapis
//        ];

        // СОЗДАЕМ DTO
        $entryDTO = new \App\DTO\BibEntryDTO(
            type: $header['type'],
            key: $header['key'],
            fields: $fieldResults['foundFieldsValues'], // Плоский массив полей
            startLine: $firstLineKey
        );

        return [
            'error' => array_merge($errors, $fieldResults['errors']),
            'entry' => $entryDTO // Возвращаем объект вместо массива zapis
        ];

    }


    /**
     * * @param array $recordLines Массив разбитых блоков построчно в таком формате: $rawBlocks[4] = "year = {2008},"
     * * @param array $header
     * * @param int $firstLineKey
     * @return array
     */
    private function prepareBuffer(array $recordLines, array $header, int $firstLineKey): array
    {
        $buffer = "";
        $lineMap = [];

        foreach ($recordLines as $lineKey => $line) {
            // Отрезаем заголовок только на первой строке
            $text = ($lineKey === $firstLineKey)
                ? substr($line, strlen($header['full_match']))
                : $line;

            $startPos = strlen($buffer);
            $buffer .= $text . "\n";
            $endPos = strlen($buffer);

            for ($i = $startPos; $i < $endPos; $i++) {
                $lineMap[$i] = $lineKey;
            }
        }

        return ['buffer' => $buffer, 'lineMap' => $lineMap];
    }


    /**
     * * @param string $buffer
     * * @param array $lineMap
     * * @param string $type
     * * @param int $defaultLine
     * @return array
     */
    private function processFields(string $buffer, array $lineMap, string $type, int $defaultLine): array
    {
        $errors = [];
        $zapis = [];
        $foundFields = [];
        $lastMatchEnd = 0;

        // Регулярка для поиска полей
        $pattern = '/(\w+)\s*=\s*(\{.*?\}|".*?"|[^{},\s][^=]*(?=\s*,\s*\w+\s*=|\s*\}|$))/su';
        preg_match_all($pattern, $buffer, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            $fieldName = strtolower($match[1][0]);
            $fieldValueRaw = trim($match[2][0]);
            $fieldOffset = $match[0][1];
            $fieldEnd = $fieldOffset + strlen($match[0][0]);

            // Находим реальную строку из нашего lineMap
            $absoluteLine = $lineMap[$fieldOffset] ?? $defaultLine;

            // Считаем колонку: ищем начало текущей строки в буфере
            $lineStartPos = 0;
            for ($i = $fieldOffset; $i >= 0; $i--) {
                if ($buffer[$i] === "\n") {
                    $lineStartPos = $i + 1;
                    break;
                }
            }
            $column = ($fieldOffset - $lineStartPos) + 1;

//            var_dump($fieldName, $fieldValueRaw, $absoluteLine);
            $cleanData = $this->sanitizeFieldValue($fieldName, $fieldValueRaw, $absoluteLine);

            if ($cleanData['error']) {
                $errors[] = [
                    'severity' => 'error',
                    'message' => $cleanData['error'],
                    'line' => $absoluteLine,
                    'column' => $column,
                    'length' => strlen($match[0][0]),
                ];
            }

            $foundFields[$fieldName] = true;
            $zapis[$absoluteLine][] = ['field' => $fieldName, 'value' => $cleanData['value']];

            // Проверка пропущенной запятой
            if ($lastMatchEnd > 0) {
                $gap = substr($buffer, $lastMatchEnd, $fieldOffset - $lastMatchEnd);
                if (!str_contains($gap, ',')) {
                    $gapLine = $lineMap[$lastMatchEnd] ?? $absoluteLine;

                    // Расчет колонки для места, где должна быть запятая
                    $gapLineStart = 0;
                    for ($i = $lastMatchEnd; $i >= 0; $i--) {
                        if ($buffer[$i] === "\n") {
                            $gapLineStart = $i + 1;
                            break;
                        }
                    }

                    $errors[] = [
                        'severity' => 'syntax',
                        'message' => "Пропущена запятая перед полем '$fieldName'",
                        'line' => $gapLine,
                        'column' => ($lastMatchEnd - $gapLineStart) + 1,
                        'length' => 1
                    ];
                }
            }
            $lastMatchEnd = $fieldEnd;
        }

//        return ['errors' => $errors, 'zapis' => $zapis, 'foundFields' => $foundFields];
        return [
            'errors' => $errors,
            'foundFieldsValues' => collect($zapis)->collapse()->pluck('value', 'field')->toArray(),
            'foundFields' => $foundFields,
        ];
    }



    /**
     * * @param string $fieldName
     * * @param string $value
     * * @param int $line
     * @return array
     */
    private function sanitizeFieldValue(string $fieldName, string $value, int $line): array
    {

        var_dump("fieldName - ");
        var_dump($fieldName);
        var_dump("value - ");
        var_dump($value);
        var_dump("line - ");
        var_dump($line);

        $error = null;

        if (str_ends_with($value, '"') && !str_starts_with($value, '"')) {
            $error = "СИНТАКСИС (Строка $line): У поля '$fieldName' есть закрывающая кавычка, но нет открывающей.";
            $value = rtrim($value, '"');
        } elseif (str_ends_with($value, '}') && !str_starts_with($value, '{')) {
            $error = "СИНТАКСИС (Строка $line): У поля '$fieldName' есть закрывающая скобка, но нет открывающей.";
            $value = rtrim($value, '}');
        } else {
            $value = preg_replace('/^\{|\}$|^\"|\"$/u', '', $value);
        }

        return ['value' => trim($value), 'error' => $error];
    }


    /**
     * * @param string $line Первая строка записи
     * * @param int $lineKey Номер строки
     * @return array|array[] Массив
     */
    private function extractHeader(string $line, int $lineKey): array
    {
        // Улучшенная регулярка для захвата позиции ключа
        // Группа 1: тип, Группа 2: ключ
        if (preg_match('/@(\w+)\s*\{\s*([^,\s\}]+)/i', $line, $matches, PREG_OFFSET_CAPTURE)) {
            return [
                'type' => strtolower($matches[1][0]),
                'key' => trim($matches[2][0]),
                'key_column' => $matches[2][1] + 1, // Позиция ключа для подсветки
                'full_match' => $line // Используем всю строку до начала полей
            ];
        }
        return ['error' => [
            'severity' => 'error',
            'message' => "Неверный формат заголовка. Ожидается '@type{key,'",
            'line' => $lineKey,
            'column' => 1,
            'length' => strlen($line)
        ]];
    }
}

