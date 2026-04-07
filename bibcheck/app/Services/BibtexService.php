<?php

namespace App\Services;

use App\Services\Bibtex\Parser;
use App\Services\Bibtex\GostValidator;
use App\Services\ExternalApi\BibValidator;

/**
 * Сервис, который объединяет воедино работу парсера, проверку на ГОСТ и проверку на наличие литературы
 */
class BibtexService
{
    public function __construct(
        private Parser $parser,
        private GostValidator $gost,
        private BibValidator $api
    ) {}

    public function fullCheck(string $bibText)
    {
        // 1. Парсим
        $data = $this->parser->analyze($bibText);

        // 2. Валидируем по ГОСТу и через API
        foreach ($data['entries'] as $entry) {
            $gostErrors = $this->gost->validate($entry);
            $apiResult = $this->api->verifySourceOnline(...);

            // Собираем всё в единый отчет
        }

        return $data;
    }
}

