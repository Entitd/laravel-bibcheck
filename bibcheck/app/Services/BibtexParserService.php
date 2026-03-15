<?php

namespace App\Services;

use App\Models\BibFile;
use App\Models\BibEntry;
use App\Models\ValidationError;
use App\Models\CourseRequirement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для парсинга, валидации и анализа BibTeX файлов.
 * Проверяет синтаксис, обязательные поля и соответствие требованиям кафедры.
 */
class BibtexParserService
{
    /**
     * Справочник типов записей BibTeX и их обязательных полей.
     * В будущем планируется перенос в БД для гибкой настройки.
     */
    private const BIBTEX_DB_TYPES = [
        'article'        => ["author", "title", "journal", "year", "pages", "volume", "number"],
        'book'           => ["author", "title", "year", "address", "publisher", "pagetotal"],
        'manual'         => ["organization", "title", "year"],
        'misc'           => ["author", "title", "urldate", "url"],
        'online'         => ["author", "title", "urldate", "url"],
        'mvbook'         => ["author", "title", "year", "address", "publisher", "pagetotal"],
        'inbook'         => ["author", "title", "booktitle", "year"],
        'bookinbook'     => ["author", "title", "booktitle", "year"],
        'suppbook'       => ["author", "title", "booktitle", "year"],
        'booklet'        => ["author", "title", "year"],
        'collection'     => ["editor", "title", "year"],
        'mvcollection'   => ["editor", "title", "year"],
        'incollection'   => ["author", "title", "booktitle", "year"],
        'suppcollection' => ["author", "title", "booktitle", "year"],
        'patent'         => ["author", "title", "number", "year"],
        'periodical'     => ["editor", "title", "year"],
        'suppperiodical' => ["author", "title", "journal", "year", "pages"],
        'proceedings'    => ["title", "year"],
        'mvproceedings'  => ["title", "year"],
        'inproceedings'  => ["author", "title", "booktitle", "year", "pages", "organization"],
        'reference'      => ["editor", "title", "year"],
        'mvreference'    => ["editor", "title", "year"],
        'inreference'    => ["author", "title", "booktitle", "year"],
        'report'         => ["author", "title", "type", "institution", "year"],
        'thesis'         => ["author", "title", "type", "institution", "year"],
        'unpublished'    => ["author", "title", "year"],
        'mastersthesis'  => ["author", "title", "institution", "year"],
        'techreport'     => ["author", "title", "institution", "year"],
        'conference'     => ["author", "title", "booktitle", "year", "pages", "organization"],
        'electronic'     => ["author", "title", "urldate", "url"],
        'phdthesis'      => ["author", "title", "institution", "year"],
        'www'            => ["author", "title", "urldate", "url"],
        'school'         => ["author", "title", "institution", "year"],
    ];

    /**
     * Выполняет полный цикл обработки текста: разбиение, парсинг и проверку нормоконтроля.
     * * @param string $text Содержимое .bib файла.
     * @return array Результаты анализа: метрики, ошибки и вердикт по курсу.
     */
    public function analyze(string $text): array
    {
        $rawBlocks = $this->splitIntoBlocks($text);
        $parsedData = $this->parseBlocks($rawBlocks);

//        var_dump("parsedData");
//        var_dump($parsedData);
        return $this->validateStandards($parsedData);
    }

    /**
     * Разрезает текст файла на отдельные блоки записей (от @ до конца блока).
     * * @param string $text
     * @return array Массив, где каждый элемент — массив строк одной записи.
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
     * * @param array $rawBlocks
     * @return array ['error' => [...], 'zapis' => [...]]
     */
    private function parseBlocks(array $rawBlocks): array
    {
        $result = ['error' => [], 'zapis' => []];
        foreach ($rawBlocks as $block) {
            $report = $this->parseEntry($block);

            if (!empty($report['error'])) {
                $result['error'] = array_merge($result['error'], $report['error']);
            }
            if (!empty($report['zapis'])) {
                $result['zapis'][] = $report['zapis'];
            }
        }
        return $result;
    }

    private function parseEntry(array $recordLines): array
    {
        $lineKeys = array_keys($recordLines);
        $firstLineKey = $lineKeys[0];

        // 1. Работаем с заголовком
        $header = $this->extractHeader($recordLines[$firstLineKey], $firstLineKey);
        if (isset($header['error'])) return ['error' => [$header['error']], 'zapis' => []];

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
                'message'  => "Пропущена запятая после ключа записи.",
                'line'     => $firstLineKey,
                'column'   => strlen($header['full_match']),
                'length'   => 1
            ]);
        }

        // Собираем финальный результат
        $zapis = $fieldResults['zapis'];
        $zapis[$firstLineKey][] = ['type' => $header['type'], 'key' => $header['key']];
        ksort($zapis);

        return [
            'error' => array_merge($fieldResults['errors'], $this->validateEntry($header['type'], $fieldResults['foundFields'], $firstLineKey)),
            'zapis' => $zapis
        ];
    }


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

            $cleanData = $this->sanitizeFieldValue($fieldName, $fieldValueRaw, $absoluteLine);

            if ($cleanData['error']) {
                $errors[] = [
                    'severity' => 'error',
                    'message'  => $cleanData['error'],
                    'line'     => $absoluteLine,
                    'column'   => $column,
                    'length'   => strlen($match[0][0]),
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
                        if ($buffer[$i] === "\n") { $gapLineStart = $i + 1; break; }
                    }

                    $errors[] = [
                        'severity' => 'syntax',
                        'message'  => "Пропущена запятая перед полем '$fieldName'",
                        'line'     => $gapLine,
                        'column'   => ($lastMatchEnd - $gapLineStart) + 1,
                        'length'   => 1
                    ];
                }
            }
            $lastMatchEnd = $fieldEnd;
        }

        return ['errors' => $errors, 'zapis' => $zapis, 'foundFields' => $foundFields];
    }


    private function sanitizeFieldValue(string $fieldName, string $value, int $line): array
    {
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
     * Разбор заголовка записи
     */
    private function extractHeader(string $line, int $lineKey): array
    {
        // Запятая теперь опциональна (\s*,?\s*)
        if (preg_match('/@(\w+)\s*\{\s*([^,\s\}]+)\s*,?\s*/i', $line, $matches)) {
            return [
                'type' => strtolower($matches[1]),
                'key'  => trim($matches[2]),
                'full_match' => $matches[0] // Сохраняем, чтобы точно знать, что отрезать
            ];
        }
        return ['error' => "ОШИБКА (Строка $lineKey): Неверный формат заголовка. Ожидается '@type{key,'"];
    }


    /**
     * Проверка синтаксического завершения строки
     */
    private function hasProperEnding(string $line): bool
    {
        $trimmed = trim($line);
        return str_ends_with($trimmed, ',');
//        return str_ends_with($trimmed, ',') || str_ends_with($trimmed, '}');

    }

    /**
     * Большая валидация полей и требований ГОСТ
     */
    private function validateEntry(string $type, array $foundFields, int $headerLine): array
    {
        $errors = [];
        $rules = self::BIBTEX_DB_TYPES[$type] ?? null;

        if (!$rules) {
            return [[
                'severity' => 'warning',
                'message'  => "Неизвестный тип записи '@$type'.",
                'line'     => $headerLine,
                'column'   => 1,
                'length'   => strlen($type) + 1
            ]];
        }

        // Проверка обязательных полей
        foreach ($rules as $reqField) {
            if (!isset($foundFields[$reqField])) {
                $errors[] = [
                    'severity' => 'error',
                    'message'  => "У '@$type' отсутствует обязательное поле '$reqField'.",
                    'line'     => $headerLine,
                    'column'   => 1,
                    'length'   => 10 // Подсвечиваем начало записи
                ];
            }
        }

        // Рекомендация по языку
        $langFields = ['language', 'langid', 'hyphenation'];
        if (!array_intersect(array_keys($foundFields), $langFields)) {
            $errors[] = [
                'severity' => 'info',
                'message'  => "Для ГОСТ рекомендуется добавить 'language' или 'langid'.",
                'line'     => $headerLine,
                'column'   => 1,
                'length'   => 1
            ];
        }

        foreach ($foundFields as $fName => $_) {
            if (!in_array($fName, $rules) && !in_array($fName, $langFields)) {
                $errors[] = [
                    'severity' => 'info',
                    'message'  => "ПРЕДУПРЕЖДЕНИЕ (Строка $headerLine): Поле '$fName' не стандартно для '@$type'.",
                    'line'     => $headerLine,
                    'column'   => 1,
                    'length'   => 1
                ];
            }
        }

        return $errors;
    }












































    /**
     * Сопоставляет статистику записей с требованиями учебных курсов из БД.
     */
    private function validateStandards(array $parsedData): array
    {
        $requirements = CourseRequirement::orderBy('course_number', 'desc')->get();
        $metrics = $this->calculateMetrics($parsedData['zapis']);

        $verdict = 'Не соответствует требованиям кафедры';
        $bestMatch = ['course' => null, 'passed' => -1];

        foreach ($requirements as $req) {
            $passedCount = 0;
            $checks = [
                $metrics['totalQuantity'] >= $req->min_total_quantity,
                $metrics['amountOfLiteratureInForeignLanguages'] >= $req->min_foreign_lang,
                $metrics['numberOfCurrentScientificPeriodicals'] >= $req->min_current_periodicals,
                $metrics['Literature21Century'] >= $req->min_21st_century
            ];

            $passedCount = count(array_filter($checks));

            if ($passedCount === count($checks)) {
                $verdict = "Полностью соответствует требованиям курса **{$req->course_number}**.";
                break;
            }

            if ($passedCount > $bestMatch['passed']) {
                $bestMatch = ['course' => $req->course_number, 'passed' => $passedCount];
            }
        }

        return [
            'aggregated_metrics' => $metrics,
            'errors' => $parsedData['error'],
            'course_comparison_result' => $verdict,
        ];
    }

    /**
     * Считает статистические показатели (иностранные языки, периодика, год издания).
     */
    private function calculateMetrics(array $entries): array
    {
        $stats = [
            'totalQuantity' => 0,
            'amountOfLiteratureInForeignLanguages' => 0,
            'numberOfCurrentScientificPeriodicals' => 0,
            'Literature21Century' => 0,
        ];

        foreach ($entries as $entryData) {
            $type = '';
            $fields = [];

            // ВАЖНО: Проходим по всем строкам записи
            foreach ($entryData as $lineItems) {
                foreach ($lineItems as $item) {
                    if (isset($item['type'])) $type = $item['type'];
                    if (isset($item['field'])) {
                        $fields[$item['field']] = $item['value'];
                    }
                }
            }

            if (!$type) continue;
            $stats['totalQuantity']++;

            $stats['amountOfLiteratureInForeignLanguages'] += $this->isForeignLanguage($fields);

            if (in_array($type, ['article', 'inproceedings', 'incollection'])) {
                $stats['numberOfCurrentScientificPeriodicals']++;
            }

            if (isset($fields['year'])) {
                // Очищаем год от лишних символов (например, "1993}" или "[2020]")
                $year = (int) preg_replace('/[^0-9]/', '', $fields['year']);
                if ($year >= 2001) $stats['Literature21Century']++;
            }
        }

        return $stats;
    }

    public function isForeignLanguage(array $fields): int
    {

        var_dump("fields");
        var_dump($fields);

        $hyphenation = strtolower($fields['hyphenation'] ?? '');
        $title = $fields['title'] ?? '';

        // Если явно указано 'russian', то это точно не иностранный
        if (in_array($hyphenation, ['russian', 'russia', 'rus'])) {
            var_dump("+++++++++++++++++++++++++++");
            return 0;
        }

        // Если поле $title пустое,
        if ($title === '') {
            var_dump("===========");

            return 0;
        }

        // Проверка на отсутствие кириллицы
        if (preg_match('/[а-яё]/iu', $title)) {
            var_dump("00000000000000000000");

            return 0; // Русских букв нет -> иностранный
        }

        // Если не русский, то иностранный
        if ($hyphenation !== '') {
            var_dump("555555555555555");

            return 1;
        }

        var_dump("222222222222");


        return 0; // Нашли русские буквы -> отечественный
    }


    /**
     * Храним смещения каждой строки
     * @param string $text
     * @return array
     */
    private function getLineOffsets(string $text): array
    {
        $offsets = [];
        $currentOffset = 0;
        $lines = explode("\n", $text);

        foreach ($lines as $index => $line) {
            // Запоминаем, на каком символе от начала файла начинается каждая строка
            $offsets[$index + 1] = $currentOffset;
            $currentOffset += strlen($line) + 1; // +1 для символа переноса \n
        }
        return $offsets;
    }


    /**
     * Превращает абсолютный индекс символа в координаты (строка, колонка)
     */
    private function getCoordinates(string $text, int $absoluteOffset): array
    {
        // Разбиваем текст на строки, сохраняя позиции
        // PHP-хак: PREG_OFFSET_CAPTURE вернет позиции начала каждой строки
        preg_match_all('/^/m', $text, $matches, PREG_OFFSET_CAPTURE);
        $lineOffsets = array_column($matches[0], 1);

        $lineNumber = 0;
        foreach ($lineOffsets as $index => $offset) {
            if ($absoluteOffset >= $offset) {
                $lineNumber = $index + 1;
                continue;
            }
            break;
        }

        // Колонка = Абсолютное смещение - Смещение начала этой строки
        $currentLineStart = $lineOffsets[$lineNumber - 1];
        $columnNumber = $absoluteOffset - $currentLineStart;

        return [
            'line' => $lineNumber,
            'column' => $columnNumber + 1, // Обычно колонки считают с 1
        ];
    }

}
