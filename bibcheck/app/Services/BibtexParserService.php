<?php

namespace App\Services;


use App\Models\BibFile;          // Нужно для метода parseAndSave
use App\Models\BibEntry;         // Если будешь создавать записи напрямую
use App\Models\ValidationError;  // Для сохранения ошибок
use App\Models\CourseRequirement; // Для метода proverka_na_kafedru
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BibtexParserService
{
    // Технические типы BibTeX (можно оставить константой или вынести в конфиг)
    private const BIBTEX_DB_TYPES = [
        'article' => ['author', 'title', 'journal', 'year'],
        'book' => ['author', 'title', 'publisher', 'year'],
        // ... добавьте остальные типы из вашего исходного массива
        'manual' => ['title', 'author', 'organization', 'year'],
    ];

    /**
     * Основной метод для выполнения анализа с кэшированием.
     * * @param string $text Содержимое BibTeX-файла.
     * @return array Результаты анализа (метрики, ошибки, результат курса).
     */
    public function analyze($fileContent): array
    {
        $rawResult = $this->runFullAnalysis($fileContent);
        return $rawResult;
    }



    /**
     * Адаптированный метод для Laravel
     */
    public function parseAndSave(BibFile $bibFile)
    {
        $text = Storage::get($bibFile->path);
        $rasb_text = $this->divisionBibFile($text);

        foreach ($rasb_text as $record_lines) {
            // 1. Прогоняем твою логику проверки полей
            $res = $this->proverkaZapisey($record_lines);

            // 2. Вытаскиваем тип и ключ из первой найденной строки записи
            $header = collect($res['zapis'])->firstWhere('type', '!=', null);

            // 3. Собираем все поля в один массив для parsed_data
            $fields = [];
            foreach ($res['zapis'] as $line) {
                if (isset($line['field'])) {
                    $fields[$line['field']] = $line['value'];
                }
            }

            // 4. Сохраняем запись в БД
            $entry = $bibFile->entries()->create([
                'type'        => $header['type'] ?? 'unknown',
                'cite_key'    => $header['key'] ?? 'no_key',
                'raw_content' => implode("\n", $record_lines),
                'parsed_data' => $fields,
                'is_valid'    => empty($res['error']),
            ]);

            // 5. Если есть ошибки — сохраняем их в таблицу ошибок
            if (!empty($res['error'])) {
                foreach ($res['error'] as $errorMessage) {
                    $entry->validationErrors()->create([
                        'message'    => $errorMessage,
                        'error_type' => 'syntax_or_gost', // Потом уточним типы
                        'severity'   => 'critical',
                    ]);
                }
            }
        }

        $bibFile->update(['status' => 'completed']);
    }

    /**
     * Вспомогательный метод, выполняющий полный парсинг.
     * @param string $text
     * @return array
     */
    private function runFullAnalysis(string $text): array
    {
        // Выполняем все шаги парсинга
        $rasb_text = $this->divisionBibFile($text);
        $razb_zapis = $this->syntacticParsingBibFile($rasb_text);

        // Передаем разобранные записи в функцию проверки
        $result = $this->proverka_na_kafedru($razb_zapis);

        return $result;
    }


    /**
     * Перенесенный и немного адаптированный код divisionBibFile.
     */
    private function divisionBibFile(string $text): array
    {
        // ... Здесь код вашей функции divisionBibFile ...
        // Не забудьте заменить все вызовы глобальной функции на $this->
        $arrayText = explode("\n", $text);
        $array = array();
        $numNote = 0;
        foreach ($arrayText as $key => $value) {
            $num = 0;
            $value = trim($value);
            if (substr($value, 0, 1) === '%') {
                continue;
            }
            // Пропуск блочного комментария @comment{...} (добавлено для полноты)
            if (str_contains($value, '@comment{')) {
                continue;
            }
            while ($num < strlen($value)) {
                if ($value[$num] == '@' && strtolower(substr($value, $num, 8)) !== '@comment') {
                    $numNote++;
                }
                if ($numNote > 0) { // Только если мы внутри записи
                    if (!isset($array[$numNote][$key])) {
                        $array[$numNote][$key] = '';
                    }
                    $array[$numNote][$key] .= $value[$num];
                }
                $num++;
            }
        }
        return $array;
    }

    /**
     * Перенесенный код syntacticParsingBibFile.
     */
    private function syntacticParsingBibFile(array $rasb_text): array
    {
        $errorArray = ['error' => [], 'zapis' => []];
        foreach ($rasb_text as $block_index => $record_lines) {
            $res = $this->proverkaZapisey($record_lines);
            if (!empty($res['error'])) {
                // Сохраняем ошибки, чтобы потом их вернуть
                $errorArray['error'] = array_merge($errorArray['error'], $res['error']);
            }
            if (!empty($res['zapis'])) {
                // Сохраняем корректно разобранные записи
                $errorArray['zapis'][] = $res['zapis'];
            }
        }
        return $errorArray;
    }

    /**
     * Перенесенный код proverkaZapisey, использующий константу.
     */
    private function proverkaZapisey(array $record_lines): array
    {
        // Заменили global $BIBTEX_DB_TYPES; на $this::BIBTEX_DB_TYPES
        $BIBTEX_DB_TYPES = $this::BIBTEX_DB_TYPES;

        $errors = [];
        $records = [];
        $found_fields = []; // Для хранения имен найденных полей (для проверки обязательных)
        $record_type = '';
        $record_line_key = 0; // Номер строки, где найден тип записи
        $is_valid = true;

        // Проверка наличия строк
        if (empty($record_lines)) {
            return [
                'error' => ["ОШИБКА: Передан пустой блок записи."],
                'zapis' => []
            ];
        }

        // --- 1. Обработка первой строки (Заголовок)
        $keys = array_keys($record_lines);
        $first_line_key = $keys[0]; // Ключ первой строки (ее номер)
        $trimmed_line = trim($record_lines[$first_line_key]);

        // Проверка начала: Должно начинаться с '@'
        if (empty($trimmed_line) || $trimmed_line[0] !== '@') {
            $errors[$first_line_key] = "ОШИБКА (Строка {$first_line_key}): Запись не начинается с символа '@'. Строка: '{$trimmed_line}'";
            $is_valid = false;
        }

        // Проверка структуры и извлечение типа/ключа
        if ($is_valid) {
            // Шаблон: /@(\w+)\s*\{\s*([^,]+)/
            if (preg_match('/@(\w+)\s*\{\s*([^,]+)/i', $trimmed_line, $matches)) {
                // $matches[1] = тип, $matches[2] = ключ
                $record_type = strtolower($matches[1]);
                $record_line_key = $first_line_key;
                $records[$first_line_key] = [
                    'type' => $record_type,
                    'key' => $matches[2]
                ];
            } else {
                $errors[$first_line_key] = "ОШИБКА (Строка {$first_line_key}): Не удалось разобрать тип и ключ записи. Строка: '{$trimmed_line}'";
                $is_valid = false;
            }
        }

        // Если заголовок невалиден, возвращаем только ошибки
        if (!$is_valid) {
            // Очищаем записи, так как заголовок не был корректно разобран
            return ['error' => $errors, 'zapis' => []];
        }

        // --- 2. Построчная проверка остальных полей
        $num_lines = count($keys);

        for ($i = 1; $i < $num_lines; $i++) {
            $current_key = $keys[$i];
            $current_line = trim($record_lines[$current_key]);

            // Игнорируем пустые строки и комментарии
            if (empty($current_line) || strpos($current_line, '%') === 0) {
                continue;
            }

            // Обработка закрывающей скобки '}'
            if ($current_line === '}') {
                // Проверка, что после '}' нет мусора
                $i++; // переходим к следующей строке
                while ($i < $num_lines) {
                    $extra_line = trim($record_lines[$keys[$i]]);
                    if (!empty($extra_line) && strpos($extra_line, '%') !== 0) {
                        $errors[$keys[$i]] = "ОШИБКА (Строка {$keys[$i]}): НАЙДЕН МУСОР В ЗАПИСИ после закрывающей скобки: '{$extra_line}'";
                    }
                    $i++;
                }
                break; // Выход из цикла, парсинг записи завершен
            }

            // Извлечение типа поля и значения
            // Шаблон: /\s*(\w+)\s*=\s*(.*)/
            if (preg_match('/\s*(\w+)\s*=\s*(.*)/i', $current_line, $matches)) {
                $field_name = strtolower($matches[1]);
                $field_value_raw = $matches[2];

                // Записываем имя найденного поля для финальной проверки
                $found_fields[$field_name] = true;

                // Удаляем запятую в конце значения, если она есть
                $field_value = rtrim($field_value_raw, ',');

                // Удаляем обрамляющие символы: кавычки ('/'/"), фигурные скобки ({})
                // Проверяем первый и последний символы (используем mb_substr для корректной работы с кириллицей, если она там вдруг будет)
                $first_char = mb_substr($field_value, 0, 1);
                $last_char = mb_substr($field_value, -1);

                if (($first_char === '\'' && $last_char === '\'') ||
                    ($first_char === '"' && $last_char === '"') ||
                    ($first_char === '{' && $last_char === '}')) {
                    $field_value = mb_substr($field_value, 1, -1);
                }

                $records[$current_key] = [
                    'field' => $field_name,
                    'value' => $field_value // значение без конечной запятой
                ];

                // Проверка завершения поля запятой (синтаксис BibTeX)
                $ends_with_comma_in_line = (substr($current_line, -1) === ',');

                if (!$ends_with_comma_in_line) {
                    $next_key_index = $i + 1;
                    $next_line_key = ($next_key_index < $num_lines) ? $keys[$next_key_index] : null;
                    $next_line = $next_line_key !== null ? trim($record_lines[$next_line_key]) : '';

                    // Следующая строка - это завершитель записи: '}' или просто ','
                    $next_is_terminator = ($next_line === '}' || $next_line === ',');

                    // Если поле не заканчивается на запятую, и следующая не '}' или ',' - это ошибка
                    if (!$next_is_terminator) {
                        $errors[$keys[$i]] = "ОШИБКА (Строка {$current_key}): Поле не завершено запятой (',') и следующая строка не является '}' или ','. Строка: '{$current_line}'";
                        // Оставляем $is_valid = true, чтобы продолжить сбор полей, несмотря на синтаксическую ошибку
                    }
                }

            } elseif ($current_line !== ',') {
                // Мусорные строки (не поля, не запятые, не '}')
                $errors[$keys[$i]] = "ПРЕДУПРЕЖДЕНИЕ (Строка {$current_key}): Строка не распознана как поле, ',' или '}'. Строка: '{$current_line}'";
            }
        }

        // --- 3. ФИНАЛЬНАЯ ПРОВЕРКА ОБЯЗАТЕЛЬНЫХ ПОЛЕЙ ---

        if (!empty($record_type)) {
            if (isset($BIBTEX_DB_TYPES[$record_type])) {
                $required_fields = $BIBTEX_DB_TYPES[$record_type];

                // echo "required_fields: \n";
                // print_r ($required_fields);
                // echo "found_fields: \n";
                // print_r ($found_fields);

                foreach ($required_fields as $required_field) {
                    // Проверяем, было ли найдено обязательное поле
                    if (!isset($found_fields[$required_field])) {
                        $errors[] = "ОШИБКА (Строка {$record_line_key}): Тип '@{$record_type}' требует обязательное поле '{$required_field}', но оно отсутствует.";
                    }
                }
                foreach ($found_fields as $field=>$true) {
                    if (!in_array($field, $required_fields)) {
                        $errors[] = "ОШИБКА (Строка {$record_line_key}): Тип '@{$record_type}' имеет лишнее поле '{$field}'";
                    }
                }
            } else {
                // Ошибка: Неизвестный тип записи
                $errors[] = "ПРЕДУПРЕЖДЕНИЕ (Строка {$record_line_key}): Тип записи '@{$record_type}' не найден в списке известных типов BibTeX.";
            }
        }

        return [
            // Возвращаем собранные ошибки (переиндексируем)
            'error' => array_values($errors),
            // Возвращаем все разобранные данные записи (заголовок + поля)
            'zapis' => $records
        ];
    }

    /**
     * Реструктурированная proverka_na_kafedru для использования Eloquent (DB).
     */
    private function proverka_na_kafedru(array $razb_zapis): array
    {
        // Загрузка требований из БД
        // Сортируем по убыванию курса, чтобы сначала проверять самые строгие требования
        $requirements = CourseRequirement::orderBy('course_number', 'desc')->get();

        // ... Здесь код подсчета метрик, который был в вашей оригинальной функции ...
        $metrics = $this->calculateMetrics($razb_zapis['zapis']);

        // --- Сравнение с требованиями ---
        $result_course = 'Не соответствует ни одному курсу';
        $best_fit_course_data = ['course' => null, 'passed' => -1, 'total' => 0];

        foreach ($requirements as $req) {
            $passed_criteria = 0;
            $total_criteria = 0;
            $is_passed = true;

            // Преобразование требований из Eloquent-объекта в массив
            $req_array = [
                'totalQuantity' => $req->min_total_quantity,
                'amountOfLiteratureInForeignLanguages' => $req->min_foreign_lang,
                'numberOfCurrentScientificPeriodicals' => $req->min_current_periodicals,
                'Literature21Century' => $req->min_21st_century,
            ];

            foreach ($req_array as $metric_name => $min_value) {
                if ($min_value === null) continue; // Пропускаем, если поле не задано
                $total_criteria++;

                // Сравнение с фактическими метриками
                if (isset($metrics[$metric_name]) && $metrics[$metric_name] >= $min_value) {
                    $passed_criteria++;
                } else {
                    $is_passed = false;
                }
            }

            if ($is_passed) {
                $result_course = "Полностью соответствует требованиям курса **{$req->course_number}**.";
                break; // Нашли максимальное соответствие
            }

            if ($passed_criteria > $best_fit_course_data['passed']) {
                $best_fit_course_data = [
                    'course' => $req->course_number,
                    'passed' => $passed_criteria,
                    'total' => $total_criteria
                ];
            }
        }

        if ($result_course === 'Не соответствует ни одному курсу' && $best_fit_course_data['course'] !== null) {
            $course_num = $best_fit_course_data['course'];
            $passed = $best_fit_course_data['passed'];
            $total = $best_fit_course_data['total'];
            $result_course = "Не соответствует полным требованиям, но максимально близок к **{$course_num}** курсу ({$passed} из {$total} требований выполнено).";
        }

        // Возвращаем чистый структурированный массив данных
        return [
            'aggregated_metrics' => $metrics,
            'errors' => $razb_zapis['error'],
            'course_comparison_result' => $result_course,
        ];
    }

    // Вспомогательный метод для подсчета метрик
    private function calculateMetrics(array $recordBlocks): array
    {
        $metrics = [
            'totalQuantity' => 0,
            'amountOfLiteratureInForeignLanguages' => 0,
            'numberOfCurrentScientificPeriodicals' => 0,
            'Literature21Century' => 0,
        ];
        $periodical_types = ['article', 'inproceedings', 'incollection'];

        foreach ($recordBlocks as $record_block) {
            $record_type = '';
            $record_fields = [];

            foreach ($record_block as $line_data) {
                if (isset($line_data['type'])) {
                    $record_type = $line_data['type'];
                } elseif (isset($line_data['field'])) {
                    $record_fields[$line_data['field']] = $line_data['value'];
                }
            }

            if (!empty($record_type)) {
                $metrics['totalQuantity']++;
            } else {
                continue;
            }

            if (isset($record_fields['hyphenation']) && strtolower($record_fields['hyphenation']) === 'english') {
                $metrics['amountOfLiteratureInForeignLanguages']++;
            }

            if (in_array($record_type, $periodical_types)) {
                $metrics['numberOfCurrentScientificPeriodicals']++;
            }

            if (isset($record_fields['year'])) {
                $year = (int)filter_var($record_fields['year'], FILTER_SANITIZE_NUMBER_INT);
                if ($year >= 2001) {
                    $metrics['Literature21Century']++;
                }
            }
        }
        return $metrics;
    }
}
