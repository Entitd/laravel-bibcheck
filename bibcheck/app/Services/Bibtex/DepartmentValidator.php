<?php

namespace App\Services\Bibtex;

use App\Models\CourseRequirement;

class DepartmentValidator
{
    /**
     * Справочник типов записей BibTeX и их обязательных полей.
     * В будущем планируется перенос в БД для гибкой настройки.
     */
    private const BIBTEX_DB_TYPES = [
        'article' => ["author", "title", "journal", "year", "pages", "volume", "number"],
        'book' => ["author", "title", "year", "address", "publisher", "pagetotal"],
        'manual' => ["organization", "title", "year"],
        'misc' => ["author", "title", "urldate", "url"],
        'online' => ["author", "title", "urldate", "url"],
        'mvbook' => ["author", "title", "year", "address", "publisher", "pagetotal"],
        'inbook' => ["author", "title", "booktitle", "year"],
        'bookinbook' => ["author", "title", "booktitle", "year"],
        'suppbook' => ["author", "title", "booktitle", "year"],
        'booklet' => ["author", "title", "year"],
        'collection' => ["editor", "title", "year"],
        'mvcollection' => ["editor", "title", "year"],
        'incollection' => ["author", "title", "booktitle", "year"],
        'suppcollection' => ["author", "title", "booktitle", "year"],
        'patent' => ["author", "title", "number", "year"],
        'periodical' => ["editor", "title", "year"],
        'suppperiodical' => ["author", "title", "journal", "year", "pages"],
        'proceedings' => ["title", "year"],
        'mvproceedings' => ["title", "year"],
        'inproceedings' => ["author", "title", "booktitle", "year", "pages", "organization"],
        'reference' => ["editor", "title", "year"],
        'mvreference' => ["editor", "title", "year"],
        'inreference' => ["author", "title", "booktitle", "year"],
        'report' => ["author", "title", "type", "institution", "year"],
        'thesis' => ["author", "title", "type", "institution", "year"],
        'unpublished' => ["author", "title", "year"],
        'mastersthesis' => ["author", "title", "institution", "year"],
        'techreport' => ["author", "title", "institution", "year"],
        'conference' => ["author", "title", "booktitle", "year", "pages", "organization"],
        'electronic' => ["author", "title", "urldate", "url"],
        'phdthesis' => ["author", "title", "institution", "year"],
        'www' => ["author", "title", "urldate", "url"],
        'school' => ["author", "title", "institution", "year"],
    ];


    /**
     * Валидирует одну записи (DTO) на соответствие ГОСТ
     */
    public function validate($entry): array
    {
        if (!$entry instanceof \App\DTO\BibEntryDTO) {
            return [['severity' => 'error', 'message' => 'Неверный тип записи для валидации.']];
        }

        $errors = [];
        $type = $entry->type;
        $fields = $entry->fields;
        $headerLine = $entry->startLine;

        $rules = self::BIBTEX_DB_TYPES[$type] ?? null;

        if (!$rules) {
            $errors[] = [
                'severity' => 'warning',
                'message' => "Неизвестный тип записи '@$type'.",
                'line' => $headerLine,
                'column' => 1,
                'length' => strlen($type) + 1
            ];
        } else {
            // Проверка обязательных полей
            foreach ($rules as $reqField) {
                if (!isset($fields[$reqField])) {
                    $errors[] = [
                        'severity' => 'error',
                        'message' => "У '@$type' отсутствует обязательное поле '$reqField'.",
                        'line' => $headerLine,
                        'column' => 1,
                        'length' => 10
                    ];
                }
            }

            // Рекомендация по языку
            $langFields = ['language', 'langid', 'hyphenation'];
            if (!array_intersect(array_keys($fields), $langFields)) {
                $errors[] = [
                    'severity' => 'info',
                    'message' => "Для ГОСТ рекомендуется добавить 'language' или 'langid'.",
                    'line' => $headerLine,
                    'column' => 1,
                    'length' => 1
                ];
            }

            // Проверка на нестандартные поля
            foreach ($fields as $fName => $_) {
                if (!in_array($fName, $rules) && !in_array($fName, $langFields)) {
                    $errors[] = [
                        'severity' => 'info',
                        'message' => "ПРЕДУПРЕЖДЕНИЕ (Строка $headerLine): Поле '$fName' не стандартно для '@$type'.",
                        'line' => $headerLine,
                        'column' => 1,
                        'length' => 1
                    ];
                }
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
        $metrics = $this->calculateMetrics($parsedData['entries']);

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
     *
     * @param array $entries Массив BibEntryDTO
     */
    public function calculateMetrics(array $entries): array
    {
        $stats = [
            'totalQuantity' => 0,
            'amountOfLiteratureInForeignLanguages' => 0,
            'numberOfCurrentScientificPeriodicals' => 0,
            'Literature21Century' => 0,
        ];

        foreach ($entries as $entry) {
            if (!$entry instanceof \App\DTO\BibEntryDTO) {
                continue;
            }

            $stats['totalQuantity']++;

            $stats['amountOfLiteratureInForeignLanguages'] += $this->isForeignLanguage($entry->fields);

            if (in_array($entry->type, ['article', 'inproceedings', 'incollection'])) {
                $stats['numberOfCurrentScientificPeriodicals']++;
            }

            if (isset($entry->fields['year'])) {
                $year = (int)preg_replace('/[^0-9]/', '', $entry->fields['year']);
                if ($year >= 2001) {
                    $stats['Literature21Century']++;
                }
            }
        }

        return $stats;
    }

    public function isForeignLanguage(array $fields): int
    {

//        var_dump("fields");
//        var_dump($fields);

        $hyphenation = strtolower($fields['hyphenation'] ?? '');
        $title = $fields['title'] ?? '';

        // Если явно указано 'russian', то это точно не иностранный
        if (in_array($hyphenation, ['russian', 'russia', 'rus'])) {
//            var_dump("+++++++++++++++++++++++++++");
            return 0;
        }

        // Если поле $title пустое,
        if ($title === '') {
//            var_dump("===========");

            return 0;
        }

        // Проверка на отсутствие кириллицы
        if (preg_match('/[а-яё]/iu', $title)) {
//            var_dump("00000000000000000000");

            return 0; // Русских букв нет -> иностранный
        }

        // Если не русский, то иностранный
        if ($hyphenation !== '') {
//            var_dump("555555555555555");

            return 1;
        }

//        var_dump("222222222222");


        return 0; // Нашли русские буквы -> отечественный
    }

}
