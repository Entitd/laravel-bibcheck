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
        $recordIndex = 0;

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);

            // Пропуск комментариев BibTeX
            if (empty($line) || str_starts_with($line, '%') || str_contains($line, '@comment')) {
                continue;
            }

            // Если строка начинается с @, значит началась новая запись
            if (str_starts_with($line, '@')) {
                $recordIndex++;
            }

            if ($recordIndex > 0) {
                $records[$recordIndex][$lineNum] = $line;
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

        foreach ($rawBlocks as $lines) {
            $report = $this->parseEntry($lines);

            if (!empty($report['error'])) {
                $result['error'] = array_merge($result['error'], $report['error']);
            }
            if (!empty($report['zapis'])) {
                $result['zapis'][] = $report['zapis'];
            }
        }
        return $result;
    }

    /**
     * Разбирает структуру конкретной записи: тип, ключ и поля.
     * Проверяет наличие обязательных полей согласно BIBTEX_DB_TYPES.
     */
    private function parseEntry(array $recordLines): array
    {
        $errors      = [];
        $parsedEntry = [];
        $foundFields = [];

        if (empty($recordLines)) {
            return ['error' => ["ОШИБКА: Пустой блок."], 'zapis' => []];
        }

        // 1. Парсинг заголовка (тип и уникальный ключ)
        $lineKeys = array_keys($recordLines);
        $firstLine = $recordLines[$lineKeys[0]];

        // Регулярка извлекает: 1 - тип (article), 2 - ключ (ivanov123)
        if (preg_match('/@(\w+)\s*\{\s*([^,]+)/i', $firstLine, $matches)) {
            $recordType = strtolower($matches[1]);
            $headerLineKey = $lineKeys[0];
            $parsedEntry[$headerLineKey] = ['type' => $recordType, 'key' => $matches[2]];
        } else {
            return [
                'error' => ["ОШИБКА (Строка {$lineKeys[0]}): Неверный формат заголовка '@type{key,'"],
                'zapis' => []
            ];
        }

        // 2. Парсинг полей (key = {value})
        foreach ($recordLines as $lineKey => $line) {
            if ($lineKey === $headerLineKey || $line === '}' || empty($line)) continue;

            // Извлекаем имя поля и его значение
            if (preg_match('/\s*(\w+)\s*=\s*(.*)/i', $line, $matches)) {
                $fieldName = strtolower($matches[1]);
                $rawValue = rtrim($matches[2], ',');

                // Очистка от обрамляющих {}, "" или ''
                $cleanValue = preg_replace('/^[\{\"\']|[\}\"\']$/u', '', $rawValue);

                $foundFields[$fieldName] = true;
                $parsedEntry[$lineKey] = ['field' => $fieldName, 'value' => $cleanValue];

                // Проверка на пропущенную запятую в конце (кроме последней строки перед })
                if (!str_ends_with(trim($line), ',') && !str_ends_with(trim($line), '}')) {
                    $errors[] = "СИНТАКСИС (Строка $lineKey): Возможно, пропущена запятая в конце строки.";
                }
            }
        }

        // 3. Валидация состава полей
        if (isset(self::BIBTEX_DB_TYPES[$recordType])) {
            $required = self::BIBTEX_DB_TYPES[$recordType];

            // Проверка отсутствующих полей
            foreach ($required as $reqField) {
                if (!isset($foundFields[$reqField])) {
                    $errors[] = "ОШИБКА (Строка $headerLineKey): У '@$recordType' отсутствует обязательное поле '$reqField'.";
                }
            }

            // --- НОВАЯ ПРОВЕРКА НА ЯЗЫК ---
            // Проверяем, есть ли ХОТЯ БЫ ОДНО из полей языка
            $hasLanguage = isset($foundFields['language']) ||
                isset($foundFields['langid']) ||
                isset($foundFields['hyphenation']);

            if (!$hasLanguage) {
                // Мы добавляем это как ПРЕДУПРЕЖДЕНИЕ, чтобы не блокировать всё,
                // но намекнуть пользователю, что для ГОСТ это важно.
                $errors[] = "ПРЕДУПРЕЖДЕНИЕ (Строка $headerLineKey): Для корректного оформления по ГОСТ рекомендуется добавить поле 'language' или 'langid'.";
            }



            // Проверка лишних полей
            foreach ($foundFields as $fName => $_) {
                if (in_array($fName, ['language', 'langid', 'hyphenation'])) continue;

                if (!in_array($fName, $required)) {
                    $errors[] = "ПРЕДУПРЕЖДЕНИЕ (Строка $headerLineKey): Поле '$fName' не входит в стандарт для '@$recordType'.";
                }
            }
        } else {
            $errors[] = "ВНИМАНИЕ (Строка $headerLineKey): Неизвестный тип записи '@$recordType'.";
        }

        return ['error' => $errors, 'zapis' => $parsedEntry];
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
