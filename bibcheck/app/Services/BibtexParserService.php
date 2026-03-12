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
        return $this->validateStandards($parsedData);
    }

    /**
     * Разрезает текст файла на отдельные блоки записей (от @ до конца блока).
     * * @param string $text
     * @return array Массив, где каждый элемент — массив строк одной записи.
     */
    private function splitIntoBlocks(string $text): array
    {
        $lines = explode("\n", $text);
        $records = [];
        $recordIndex = -1;

        foreach ($lines as $lineNum => $line) {
            $trimmedLine = trim($line);

            if (empty($trimmedLine) || str_starts_with($trimmedLine, '%') || str_contains($trimmedLine, '@comment')) {
                continue;
            }

            // Новая запись начинается здесь
            if (str_starts_with($trimmedLine, '@')) {
                $recordIndex++;
            }

            // Собираем только если мы уже внутри какой-то записи
            if ($recordIndex >= 0) {
                $records[$recordIndex][$lineNum] = $trimmedLine;
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
        $errors = [];
        $parsedEntry = [];
        $foundFields = [];

        $lineKeys = array_keys($recordLines);
        $firstLineKey = $lineKeys[0];

        // 1. Заголовок (теперь запятая после ключа необязательна для парсинга)
        $firstLine = $recordLines[$firstLineKey];
        $header = $this->extractHeader($firstLine, $firstLineKey);

        if (isset($header['error'])) return ['error' => [$header['error']], 'zapis' => []];

        // Проверка на пропущенную запятую в заголовке
        if (!str_contains($header['full_match'], ',')) {
            $errors[] = "СИНТАКСИС (Строка $firstLineKey): Пропущена запятая после ключа '{$header['key']}'.";
        }

        $parsedEntry[$firstLineKey][] = ['type' => $header['type'], 'key' => $header['key']];

        // 2. Создание буфера и карты строк
        $buffer = "";
        $lineMap = [];
        foreach ($recordLines as $lineKey => $line) {
            $text = ($lineKey === $firstLineKey)
                ? substr($line, strlen($header['full_match']))
                : $line;

            $startPos = strlen($buffer);
            $buffer .= $text . " ";
            $endPos = strlen($buffer);

            for ($i = $startPos; $i < $endPos; $i++) {
                $lineMap[$i] = $lineKey;
            }
        }

        // 3. СУПЕР-РЕГУЛЯРКА для полей
        // Она ищет ключ, а затем захватывает значение, балансируя между скобками
        // или останавливаясь перед следующим полем.
        preg_match_all('/(\w+)\s*=\s*(\{.*?\}|".*?"|[^{},\s][^=]*(?=\s*,\s*\w+\s*=|\s*\}|$))/su', $buffer, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $lastMatchEnd = 0;
        foreach ($matches as $match) {
            $fieldName = strtolower($match[1][0]);
            $fieldValueRaw = trim($match[2][0]);
            $fieldOffset = $match[0][1];
            $fieldEnd = $fieldOffset + strlen($match[0][0]);

            $currentLine = $lineMap[$fieldOffset] ?? $firstLineKey;

            // Чистим внешние скобки/кавычки, если они есть
            $cleanValue = preg_replace('/^\{|\}$|^\"|\"$/u', '', $fieldValueRaw);

            // Проверка на твой случай: author=Value} (пропущена открывающая скобка)
            if (str_ends_with($fieldValueRaw, '}') && !str_starts_with($fieldValueRaw, '{')) {
                $errors[] = "СИНТАКСИС (Строка $currentLine): У поля '$fieldName' есть закрывающая скобка, но нет открывающей.";
                $cleanValue = rtrim($cleanValue, '}');
            }

            $foundFields[$fieldName] = true;
            $parsedEntry[$currentLine][] = ['field' => $fieldName, 'value' => trim($cleanValue)];

            // 4. Проверка запятой между полями
            if ($lastMatchEnd > 0) {
                $gap = substr($buffer, $lastMatchEnd, $fieldOffset - $lastMatchEnd);
                if (!str_contains($gap, ',')) {
                    $errorLine = $lineMap[$lastMatchEnd] ?? $currentLine;
                    $errors[] = "СИНТАКСИС (Строка $errorLine): Пропущена запятая перед полем '$fieldName'.";
                }
            }
            $lastMatchEnd = $fieldEnd;
        }

        $validationErrors = $this->validateEntry($header['type'], $foundFields, $firstLineKey);
        return ['error' => array_merge($errors, $validationErrors), 'zapis' => $parsedEntry];
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
     * Разбор отдельной строки поля (author = {Ivanov})
     */
    private function extractField(string $line): ?array
    {
        if (preg_match('/\s*(\w+)\s*=\s*(.*)/i', $line, $matches)) {
            return [
                'name'  => strtolower($matches[1]),
                'value' => preg_replace('/^[\{\"\']|[\}\"\']$/u', '', rtrim($matches[2], ','))
            ];
        }
        return null;
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
            return ["ВНИМАНИЕ (Строка $headerLine): Неизвестный тип записи '@$type'."];
        }

        // Проверка обязательных полей
        foreach ($rules as $reqField) {
            if (!isset($foundFields[$reqField])) {
                $errors[] = "ОШИБКА (Строка $headerLine): У '@$type' отсутствует обязательное поле '$reqField'.";
            }
        }

        // Проверка на язык (Важно для ГОСТ)
        $langFields = ['language', 'langid', 'hyphenation'];
        $hasLanguage = (bool)array_intersect(array_keys($foundFields), $langFields);

        if (!$hasLanguage) {
            $errors[] = "ПРЕДУПРЕЖДЕНИЕ (Строка $headerLine): Для ГОСТ рекомендуется добавить 'language' или 'langid'.";
        }

        // Проверка лишних полей
        foreach ($foundFields as $fName => $_) {
            if (!in_array($fName, $rules) && !in_array($fName, $langFields)) {
                $errors[] = "ПРЕДУПРЕЖДЕНИЕ (Строка $headerLine): Поле '$fName' не стандартно для '@$type'.";
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

        foreach ($entries as $entry) {
            $type = '';
            $fields = [];

            foreach ($entry as $data) {
                if (isset($data['type'])) $type = $data['type'];
                if (isset($data['field'])) $fields[$data['field']] = $data['value'];
            }

            if (!$type) continue;
            $stats['totalQuantity']++;

            // Считаем английские источники (по полю hyphenation)
            $stats['amountOfLiteratureInForeignLanguages'] += $this->isForeignLanguage($fields);

            // Статьи и конференции считаем периодикой
            if (in_array($type, ['article', 'inproceedings', 'incollection'])) {
                $stats['numberOfCurrentScientificPeriodicals']++;
            }

            // Проверка на 21 век (>= 2001 год)
            if (isset($fields['year'])) {
                $year = (int) preg_replace('/[^0-9]/', '', $fields['year']);
                if ($year >= 2001) $stats['Literature21Century']++;
            }
        }

        return $stats;
    }

    public function isForeignLanguage(array $fields): int
    {
        $hyphenation = strtolower($fields['hyphenation'] ?? '');
        $title = $fields['title'] ?? '';

        // Если явно указано 'russian', то это точно не иностранный
        if (in_array($hyphenation, ['russian', 'russia', 'rus'])) {
            return 0;
        }

        // Если поле $title пустое или содержит что-то другое,
        if ($title === '') {
            return 0;
        }

        // Проверка на отсутствие кириллицы
        if (preg_match('/[а-яё]/iu', $title)) {
            return 0; // Русских букв нет -> иностранный
        }

        // Если не русский, то иностранный
        if ($hyphenation !== '') {
            return 1;
        }



        return 0; // Нашли русские буквы -> отечественный
    }
}
