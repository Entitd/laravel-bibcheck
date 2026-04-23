<?php

namespace App\Services\Bibtex;

use App\DTO\BibEntryDTO;

class Parser
{
    /**
     * Разбирает весь текст bib-файла и возвращает найденные записи и ошибки парсинга.
     *
     * На вход получает:
     * - `$text` — исходное содержимое BibTeX-файла одной строкой.
     *
     * Что делает:
     * - разбивает файл на отдельные BibTeX-записи;
     * - разбирает каждую запись независимо;
     * - собирает все ошибки синтаксиса и все успешно распарсенные DTO в один результат.
     *
     * Что возвращает:
     * - массив вида `['error' => array, 'entries' => BibEntryDTO[]]`.
     *
     * @param string $text Сырые данные bib-файла.
     * @return array{error: array, entries: array<int, BibEntryDTO>}
     */
    public function parse(string $text): array
    {
        $rawBlocks = $this->splitIntoBlocks($text);
        $usedKeys = [];

        return $this->parseBlocks($rawBlocks, $usedKeys);
    }

    /**
     * Делит сырой BibTeX-текст на блоки записей и сохраняет исходные номера строк.
     *
     * На вход получает:
     * - `$text` — полное содержимое файла.
     *
     * Что делает:
     * - разбивает текст на строки;
     * - пропускает пустые строки, `%`-комментарии и `@comment`;
     * - начинает новую запись при встрече строки, начинающейся с `@`;
     * - сохраняет каждую запись в формате `[номерСтроки => текстСтроки]`.
     *
     * Что возвращает:
     * - массив записей;
     * - каждая запись — это массив строк, где ключом является исходный номер строки в файле.
     *
     * @param string $text Полный текст BibTeX-файла.
     * @return array<int, array<int, string>>
     */
    private function splitIntoBlocks(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $records = [];
        $recordIndex = -1;

        foreach ($lines as $lineNum => $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '' || str_starts_with($trimmedLine, '%') || str_starts_with(strtolower($trimmedLine), '@comment')) {
                continue;
            }

            if (str_starts_with($trimmedLine, '@')) {
                $recordIndex++;
            }

            if ($recordIndex >= 0) {
                $records[$recordIndex][$lineNum + 1] = $line;
            }
        }

        return $records;
    }

    /**
     * Разбирает все подготовленные блоки записей и объединяет их результат.
     *
     * На вход получает:
     * - `$rawBlocks` — результат работы `splitIntoBlocks()`;
     * - `$usedKeys` — карту уже встреченных ключей записей, переданную по ссылке.
     *
     * Что делает:
     * - вызывает `parseEntry()` для каждого блока;
     * - добавляет локальные ошибки каждой записи в общий список;
     * - собирает все успешно созданные `BibEntryDTO`.
     *
     * Что возвращает:
     * - массив вида `['error' => array, 'entries' => BibEntryDTO[]]`.
     *
     * @param array<int, array<int, string>> $rawBlocks Блоки записей, разбитые по строкам.
     * @param array<string, int> $usedKeys Уже использованные ключи записей.
     * @return array{error: array, entries: array<int, BibEntryDTO>}
     */
    private function parseBlocks(array $rawBlocks, array &$usedKeys): array
    {
        $result = ['error' => [], 'entries' => []];

        foreach ($rawBlocks as $block) {
            $report = $this->parseEntry($block, $usedKeys);

            if (! empty($report['error'])) {
                $result['error'] = array_merge($result['error'], $report['error']);
            }

            if ($report['entry'] !== null) {
                $result['entries'][] = $report['entry'];
            }
        }

        return $result;
    }

    /**
     * Разбирает одну BibTeX-запись в DTO и список локальных ошибок.
     *
     * На вход получает:
     * - `$recordLines` — одну запись в формате `[номерСтроки => текстСтроки]`;
     * - `$usedKeys` — карту ключей, уже встреченных ранее в файле.
     *
     * Что делает:
     * - разбирает заголовок записи вида `@type{key,`;
     * - проверяет уникальность ключа записи;
     * - превращает тело записи в плоский буфер;
     * - разбирает все поля из этого буфера;
     * - создаёт `BibEntryDTO`, даже если внутри записи есть исправимые синтаксические ошибки.
     *
     * Что возвращает:
     * - массив вида `['error' => array, 'entry' => ?BibEntryDTO]`;
     * - `entry` будет `null`, только если не удалось разобрать сам заголовок записи.
     *
     * @param array<int, string> $recordLines Одна BibTeX-запись построчно.
     * @param array<string, int> $usedKeys Уже использованные ключи записей.
     * @return array{error: array, entry: ?BibEntryDTO}
     */
    private function parseEntry(array $recordLines, array &$usedKeys): array
    {
        if ($recordLines === []) {
            return ['error' => [], 'entry' => null];
        }

        $firstLineKey = array_key_first($recordLines);
        $header = $this->extractHeader($recordLines[$firstLineKey], $firstLineKey);

        if (isset($header['error'])) {
            return ['error' => [$header['error']], 'entry' => null];
        }

        $errors = [];
        $currentKey = $header['key'];

        if (isset($usedKeys[$currentKey])) {
            $errors[] = [
                'severity' => 'error',
                'message' => "Дублирующийся ключ записи '$currentKey'. Ранее использован на строке {$usedKeys[$currentKey]}.",
                'line' => $firstLineKey,
                'column' => $header['key_column'],
                'length' => strlen($currentKey),
            ];
        } else {
            $usedKeys[$currentKey] = $firstLineKey;
        }

        $prepared = $this->prepareBuffer($recordLines, $header, $firstLineKey);
        $fieldResults = $this->processFields(
            $prepared['buffer'],
            $prepared['lineMap'],
            $firstLineKey
        );

        if (! $header['has_comma']) {
            array_unshift($fieldResults['errors'], [
                'severity' => 'syntax',
                'message' => 'Пропущена запятая после ключа записи.',
                'line' => $firstLineKey,
                'column' => $header['body_column'],
                'length' => 1,
            ]);
        }

        $entryDTO = new BibEntryDTO(
            type: $header['type'],
            key: $header['key'],
            fields: $fieldResults['foundFieldsValues'],
            startLine: $firstLineKey
        );

        return [
            'error' => array_merge($errors, $fieldResults['errors']),
            'entry' => $entryDTO,
        ];
    }

    /**
     * Превращает многострочную запись в один плоский буфер и карту соответствия offset -> строка.
     *
     * На вход получает:
     * - `$recordLines` — строки одной записи;
     * - `$header` — метаданные заголовка из `extractHeader()`;
     * - `$firstLineKey` — номер первой строки записи в исходном файле.
     *
     * Что делает:
     * - отрезает уже разобранную часть заголовка из первой строки;
     * - склеивает оставшееся тело записи в одну строку-буфер;
     * - для каждого символа в буфере запоминает, из какой строки файла он пришёл.
     *
     * Что возвращает:
     * - массив вида `['buffer' => string, 'lineMap' => array<int, int>]`.
     *
     * @param array<int, string> $recordLines Одна запись построчно.
     * @param array<string, mixed> $header Результат разбора заголовка.
     * @param int $firstLineKey Номер первой строки записи.
     * @return array{buffer: string, lineMap: array<int, int>}
     */
    private function prepareBuffer(array $recordLines, array $header, int $firstLineKey): array
    {
        $buffer = '';
        $lineMap = [];

        foreach ($recordLines as $lineKey => $line) {
            $text = $lineKey === $firstLineKey
                ? substr($line, $header['body_offset'])
                : $line;

            $startPos = strlen($buffer);
            $buffer .= $text."\n";
            $endPos = strlen($buffer);

            for ($i = $startPos; $i < $endPos; $i++) {
                $lineMap[$i] = $lineKey;
            }
        }

        return ['buffer' => $buffer, 'lineMap' => $lineMap];
    }

    /**
     * Разбирает все поля из плоского буфера записи посимвольно.
     *
     * На вход получает:
     * - `$buffer` — плоское тело записи из `prepareBuffer()`;
     * - `$lineMap` — карту соответствия позиции в буфере номеру строки файла;
     * - `$defaultLine` — запасной номер строки, если offset не найден в карте.
     *
     * Что делает:
     * - читает имена полей, знак `=`, значения и разделители по одному токену;
     * - поддерживает значения в `{...}`, в `"..."` и без обрамления;
     * - собирает ошибки синтаксиса и дубли полей;
     * - сохраняет распознанные поля в виде `имяПоля => значение`.
     *
     * Что возвращает:
     * - массив вида
     *   `['errors' => array, 'foundFieldsValues' => array<string, string>, 'foundFields' => array<string, bool>]`.
     *
     * @param string $buffer Плоский текст тела записи.
     * @param array<int, int> $lineMap Карта позиций буфера к строкам исходного файла.
     * @param int $defaultLine Резервный номер строки.
     * @return array{errors: array, foundFieldsValues: array<string, string>, foundFields: array<string, bool>}
     */
    private function processFields(string $buffer, array $lineMap, int $defaultLine): array
    {
        $errors = [];
        $foundFieldsValues = [];
        $foundFields = [];
        $length = strlen($buffer);
        $position = 0;

        while ($position < $length) {
            $position = $this->skipWhitespace($buffer, $position);

            if ($position >= $length || $buffer[$position] === '}') {
                break;
            }

            if ($buffer[$position] === ',') {
                $position++;
                continue;
            }

            $fieldOffset = $position;
            if (! $this->isIdentifierChar($buffer[$position])) {
                $errors[] = $this->makeSyntaxError(
                    $buffer,
                    $lineMap,
                    $defaultLine,
                    $fieldOffset,
                    'Неожиданный символ при разборе поля.',
                    1
                );
                $position++;
                continue;
            }

            $fieldName = strtolower($this->readIdentifier($buffer, $position));
            $position = $this->skipWhitespace($buffer, $position);

            if (($buffer[$position] ?? null) !== '=') {
                $errors[] = $this->makeSyntaxError(
                    $buffer,
                    $lineMap,
                    $defaultLine,
                    $position,
                    "После имени поля '$fieldName' ожидается '='.",
                    1
                );
                $position = $this->recoverToNextFieldBoundary($buffer, $position);
                continue;
            }

            $position++;
            $position = $this->skipWhitespace($buffer, $position);

            [$rawValue, $position, $valueError] = $this->readFieldValue($buffer, $position);
            if ($valueError !== null) {
                $errors[] = $this->makeSyntaxError(
                    $buffer,
                    $lineMap,
                    $defaultLine,
                    $fieldOffset,
                    $valueError,
                    max(strlen($fieldName), 1)
                );
                $position = $this->recoverToNextFieldBoundary($buffer, $position);
                continue;
            }

            if (array_key_exists($fieldName, $foundFieldsValues)) {
                $errors[] = $this->makeSyntaxError(
                    $buffer,
                    $lineMap,
                    $defaultLine,
                    $fieldOffset,
                    "Поле '$fieldName' объявлено повторно.",
                    strlen($fieldName)
                );
                $position = $this->consumeSeparator($buffer, $position);
                continue;
            }

            $cleanValue = $this->sanitizeFieldValue($rawValue);
            $foundFields[$fieldName] = true;
            $foundFieldsValues[$fieldName] = $cleanValue;

            $separatorPos = $this->skipWhitespace($buffer, $position);
            $separator = $buffer[$separatorPos] ?? null;

            if ($separator === ',') {
                $position = $separatorPos + 1;
                continue;
            }

            if ($separator === '}' || $separator === null) {
                $position = $separatorPos;
                continue;
            }

            $errors[] = $this->makeSyntaxError(
                $buffer,
                $lineMap,
                $defaultLine,
                $separatorPos,
                "После поля '$fieldName' ожидается запятая.",
                1
            );
            $position = $separatorPos;
        }

        return [
            'errors' => $errors,
            'foundFieldsValues' => $foundFieldsValues,
            'foundFields' => $foundFields,
        ];
    }

    /**
     * Нормализует значение поля перед сохранением в DTO.
     *
     * На вход получает:
     * - `$value` — сырое значение поля ровно в том виде, как оно было прочитано из буфера.
     *
     * Что делает:
     * - обрезает внешние пробелы;
     * - снимает одну внешнюю пару `{...}` или `"..."`, если она есть;
     * - оставляет внутреннее содержимое без изменений.
     *
     * Что возвращает:
     * - нормализованное строковое значение поля.
     *
     * @param string $value Сырое значение поля.
     * @return string
     */
    private function sanitizeFieldValue(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (
            (str_starts_with($value, '{') && str_ends_with($value, '}'))
            || (str_starts_with($value, '"') && str_ends_with($value, '"'))
        ) {
            return trim(substr($value, 1, -1));
        }

        return trim($value);
    }

    /**
     * Разбирает первую строку записи и извлекает метаданные заголовка.
     *
     * На вход получает:
     * - `$line` — первую строку BibTeX-записи;
     * - `$lineKey` — номер этой строки в исходном файле.
     *
     * Что делает:
     * - проверяет начало записи вида `@type{key,`;
     * - извлекает тип записи и её ключ;
     * - вычисляет, с какой позиции на первой строке начинается тело записи;
     * - определяет, есть ли запятая после ключа.
     *
     * Что возвращает:
     * - при успехе массив с данными заголовка;
     * - при ошибке массив вида `['error' => array]`.
     *
     * @param string $line Первая строка записи.
     * @param int $lineKey Номер строки в исходном файле.
     * @return array<string, mixed>
     */
    private function extractHeader(string $line, int $lineKey): array
    {
        $length = strlen($line);
        $position = 0;

        while ($position < $length && ctype_space($line[$position])) {
            $position++;
        }

        if (($line[$position] ?? null) !== '@') {
            return $this->makeHeaderError($line, $lineKey);
        }

        $position++;
        $typeStart = $position;

        while ($position < $length && $this->isIdentifierChar($line[$position])) {
            $position++;
        }

        $type = strtolower(substr($line, $typeStart, $position - $typeStart));
        if ($type === '') {
            return $this->makeHeaderError($line, $lineKey);
        }

        while ($position < $length && ctype_space($line[$position])) {
            $position++;
        }

        if (($line[$position] ?? null) !== '{') {
            return $this->makeHeaderError($line, $lineKey);
        }

        $position++;
        while ($position < $length && ctype_space($line[$position])) {
            $position++;
        }

        $keyStart = $position;
        while ($position < $length && ! in_array($line[$position], [',', '}'], true)) {
            $position++;
        }

        $key = trim(substr($line, $keyStart, $position - $keyStart));
        if ($key === '') {
            return $this->makeHeaderError($line, $lineKey);
        }

        $hasComma = ($line[$position] ?? null) === ',';
        $bodyOffset = $hasComma ? $position + 1 : $position;
        $bodyColumn = $hasComma ? $bodyOffset + 1 : $position + 1;

        return [
            'type' => $type,
            'key' => $key,
            'key_column' => $keyStart + 1,
            'body_offset' => $bodyOffset,
            'body_column' => $bodyColumn,
            'has_comma' => $hasComma,
        ];
    }

    /**
     * Создаёт стандартную ошибку для невалидного заголовка записи.
     *
     * На вход получает:
     * - `$line` — исходную строку заголовка;
     * - `$lineKey` — номер строки в файле.
     *
     * Что возвращает:
     * - массив вида `['error' => array]`.
     *
     * @param string $line Исходная строка заголовка.
     * @param int $lineKey Номер строки в файле.
     * @return array{error: array}
     */
    private function makeHeaderError(string $line, int $lineKey): array
    {
        return ['error' => [
            'severity' => 'error',
            'message' => "Неверный формат заголовка. Ожидается '@type{key,'",
            'line' => $lineKey,
            'column' => 1,
            'length' => max(strlen($line), 1),
        ]];
    }

    /**
     * Выбирает нужный способ чтения значения поля по первому символу.
     *
     * На вход получает:
     * - `$buffer` — плоский текст тела записи;
     * - `$position` — позицию, с которой должно начинаться значение.
     *
     * Что делает:
     * - если значение начинается с `{`, вызывает `readBraceValue()`;
     * - если с `"`, вызывает `readQuotedValue()`;
     * - иначе читает значение как bare value через `readBareValue()`.
     *
     * Что возвращает:
     * - массив вида `[rawValue, nextPosition, errorMessage]`.
     *
     * @param string $buffer Плоский текст тела записи.
     * @param int $position Позиция начала значения.
     * @return array{0: string, 1: int, 2: ?string}
     */
    private function readFieldValue(string $buffer, int $position): array
    {
        $length = strlen($buffer);
        if ($position >= $length) {
            return ['', $position, 'После "=" ожидается значение поля.'];
        }

        return match ($buffer[$position]) {
            '{' => $this->readBraceValue($buffer, $position),
            '"' => $this->readQuotedValue($buffer, $position),
            default => $this->readBareValue($buffer, $position),
        };
    }

    /**
     * Читает значение в фигурных скобках и поддерживает вложенные скобки.
     *
     * На вход получает:
     * - `$buffer` — плоский текст тела записи;
     * - `$position` — позицию открывающей `{`.
     *
     * Что делает:
     * - считает глубину вложенности фигурных скобок;
     * - завершает чтение только на парной закрывающей скобке внешнего уровня.
     *
     * Что возвращает:
     * - массив вида `[rawValue, nextPosition, errorMessage]`.
     *
     * @param string $buffer Плоский текст тела записи.
     * @param int $position Позиция открывающей фигурной скобки.
     * @return array{0: string, 1: int, 2: ?string}
     */
    private function readBraceValue(string $buffer, int $position): array
    {
        $length = strlen($buffer);
        $start = $position;
        $depth = 0;

        while ($position < $length) {
            $char = $buffer[$position];

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    $position++;

                    return [substr($buffer, $start, $position - $start), $position, null];
                }
            }

            $position++;
        }

        return [substr($buffer, $start), $position, 'У значения в фигурных скобках нет закрывающей скобки.'];
    }

    /**
     * Читает значение в кавычках и учитывает экранированные символы.
     *
     * На вход получает:
     * - `$buffer` — плоский текст тела записи;
     * - `$position` — позицию открывающей кавычки.
     *
     * Что делает:
     * - читает символы до первой неэкранированной закрывающей кавычки;
     * - корректно пропускает конструкции вроде `\"`.
     *
     * Что возвращает:
     * - массив вида `[rawValue, nextPosition, errorMessage]`.
     *
     * @param string $buffer Плоский текст тела записи.
     * @param int $position Позиция открывающей кавычки.
     * @return array{0: string, 1: int, 2: ?string}
     */
    private function readQuotedValue(string $buffer, int $position): array
    {
        $length = strlen($buffer);
        $start = $position;
        $position++;
        $escaped = false;

        while ($position < $length) {
            $char = $buffer[$position];

            if ($escaped) {
                $escaped = false;
                $position++;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                $position++;
                continue;
            }

            if ($char === '"') {
                $position++;

                return [substr($buffer, $start, $position - $start), $position, null];
            }

            $position++;
        }

        return [substr($buffer, $start), $position, 'У значения в кавычках нет закрывающей кавычки.'];
    }

    /**
     * Читает значение без фигурных скобок и без кавычек.
     *
     * На вход получает:
     * - `$buffer` — плоский текст тела записи;
     * - `$position` — позицию первого символа значения.
     *
     * Что делает:
     * - читает символы до запятой, закрывающей `}` или конца строки;
     * - убирает пробелы справа у считанного фрагмента.
     *
     * Что возвращает:
     * - массив вида `[rawValue, nextPosition, errorMessage]`.
     *
     * @param string $buffer Плоский текст тела записи.
     * @param int $position Позиция начала значения.
     * @return array{0: string, 1: int, 2: ?string}
     */
    private function readBareValue(string $buffer, int $position): array
    {
        $length = strlen($buffer);
        $start = $position;

        while ($position < $length) {
            $char = $buffer[$position];

            if ($char === ',' || $char === '}' || $char === "\n" || $char === "\r") {
                break;
            }

            $position++;
        }

        $value = rtrim(substr($buffer, $start, $position - $start));
        if ($value === '') {
            return ['', $position, 'После "=" ожидается значение поля.'];
        }

        return [$value, $position, null];
    }

    /**
     * Превращает внутреннюю ошибку парсера в нормализованный объект синтаксической ошибки.
     *
     * На вход получает:
     * - `$buffer` — плоский текст тела записи;
     * - `$lineMap` — карту позиций буфера к строкам файла;
     * - `$defaultLine` — запасной номер строки;
     * - `$offset` — позицию в буфере, где найдена проблема;
     * - `$message` — текст ошибки;
     * - `$length` — длину проблемного фрагмента для подсветки.
     *
     * Что возвращает:
     * - массив с полями `severity`, `message`, `line`, `column`, `length`.
     *
     * @param string $buffer Плоский текст тела записи.
     * @param array<int, int> $lineMap Карта позиций буфера к строкам.
     * @param int $defaultLine Резервный номер строки.
     * @param int $offset Позиция ошибки в буфере.
     * @param string $message Текст ошибки.
     * @param int $length Длина проблемного фрагмента.
     * @return array{severity: string, message: string, line: int, column: int, length: int}
     */
    private function makeSyntaxError(
        string $buffer,
        array $lineMap,
        int $defaultLine,
        int $offset,
        string $message,
        int $length = 1
    ): array {
        $offset = max($offset, 0);
        $line = $lineMap[$offset] ?? $defaultLine;

        return [
            'severity' => 'syntax',
            'message' => $message,
            'line' => $line,
            'column' => $this->calculateColumn($buffer, $offset),
            'length' => max($length, 1),
        ];
    }

    /**
     * Переводит позицию в буфере в номер колонки внутри строки.
     *
     * На вход получает:
     * - `$buffer` — плоский текст тела записи;
     * - `$offset` — абсолютную позицию внутри буфера.
     *
     * Что возвращает:
     * - номер колонки, начиная с 1.
     *
     * @param string $buffer Плоский текст тела записи.
     * @param int $offset Позиция в буфере.
     * @return int
     */
    private function calculateColumn(string $buffer, int $offset): int
    {
        $offset = min(max($offset, 0), strlen($buffer));
        $lineStartPos = 0;

        for ($i = $offset - 1; $i >= 0; $i--) {
            if ($buffer[$i] === "\n") {
                $lineStartPos = $i + 1;
                break;
            }
        }

        return ($offset - $lineStartPos) + 1;
    }

    /**
     * Сдвигает курсор вперёд, пропуская пробелы, табы и переводы строк.
     *
     * На вход получает:
     * - `$buffer` — плоский текст тела записи;
     * - `$position` — текущую позицию курсора.
     *
     * Что возвращает:
     * - новую позицию курсора после пропуска whitespace.
     *
     * @param string $buffer Плоский текст тела записи.
     * @param int $position Текущая позиция.
     * @return int
     */
    private function skipWhitespace(string $buffer, int $position): int
    {
        $length = strlen($buffer);

        while ($position < $length && ctype_space($buffer[$position])) {
            $position++;
        }

        return $position;
    }

    /**
     * Читает идентификатор и двигает курсор по ссылке.
     *
     * На вход получает:
     * - `$buffer` — плоский текст тела записи;
     * - `$position` — текущую позицию, которая будет изменена по ссылке.
     *
     * Что делает:
     * - читает последовательность из букв, цифр, `_` и `-`;
     * - останавливается на первом символе, который не входит в идентификатор.
     *
     * Что возвращает:
     * - найденный идентификатор строкой.
     *
     * @param string $buffer Плоский текст тела записи.
     * @param int $position Текущая позиция курсора.
     * @return string
     */
    private function readIdentifier(string $buffer, int &$position): string
    {
        $start = $position;
        $length = strlen($buffer);

        while ($position < $length && $this->isIdentifierChar($buffer[$position])) {
            $position++;
        }

        return substr($buffer, $start, $position - $start);
    }

    /**
     * Проверяет, допустим ли символ внутри идентификатора.
     *
     * На вход получает:
     * - `$char` — один символ строки.
     *
     * Что возвращает:
     * - `true`, если символ является буквой, цифрой, `_` или `-`;
     * - иначе `false`.
     *
     * @param string $char Один символ.
     * @return bool
     */
    private function isIdentifierChar(string $char): bool
    {
        return ctype_alnum($char) || $char === '_' || $char === '-';
    }

    /**
     * Перемещает курсор к ближайшей безопасной границе поля после ошибки парсинга.
     *
     * На вход получает:
     * - `$buffer` — плоский текст тела записи;
     * - `$position` — позицию, с которой нужно начать восстановление.
     *
     * Что делает:
     * - пропускает символы, пока не встретит запятую, перевод строки или `}`;
     * - позволяет продолжить парсинг со следующей вероятной границы поля.
     *
     * Что возвращает:
     * - новую позицию курсора.
     *
     * @param string $buffer Плоский текст тела записи.
     * @param int $position Текущая позиция.
     * @return int
     */
    private function recoverToNextFieldBoundary(string $buffer, int $position): int
    {
        $length = strlen($buffer);

        while ($position < $length) {
            if (in_array($buffer[$position], [',', "\n", "\r", '}'], true)) {
                return $position;
            }

            $position++;
        }

        return $position;
    }

    /**
     * Поглощает необязательную запятую после поля и возвращает следующую позицию курсора.
     *
     * На вход получает:
     * - `$buffer` — плоский текст тела записи;
     * - `$position` — текущую позицию курсора.
     *
     * Что делает:
     * - пропускает whitespace;
     * - если следующий значимый символ — запятая, двигается за неё.
     *
     * Что возвращает:
     * - обновлённую позицию курсора.
     *
     * @param string $buffer Плоский текст тела записи.
     * @param int $position Текущая позиция.
     * @return int
     */
    private function consumeSeparator(string $buffer, int $position): int
    {
        $position = $this->skipWhitespace($buffer, $position);

        if (($buffer[$position] ?? null) === ',') {
            return $position + 1;
        }

        return $position;
    }
}
