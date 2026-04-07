<?php

namespace App\Services\ExternalApi;

class BibValidator
{



    /**
     * Сравнивает две строки на схожесть (0.0 - 1.0)
     */
    public function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = mb_strtolower(preg_replace('/[^\p{L}\p{N} ]/u', '', $str1));
        $str2 = mb_strtolower(preg_replace('/[^\p{L}\p{N} ]/u', '', $str2));

        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }

    /**
     * Проверка существования источника через API
     */
    public function verifySourceOnline(array $fields): array
    {
        if (empty($fields['title'])) return ['found' => false];

        $provider = new OpenAlexProvider();
        $externalData = $provider->findByTitle($fields['title']);

        if (!$externalData) {
            return [
                'found' => false,
                'message' => "Источник не найден в базе OpenAlex."
            ];
        }

        // Проверяем схожесть названий
        $titleSimilarity = $this->calculateSimilarity($fields['title'], $externalData['title']);

        // Проверяем авторов (опционально, так как форматы записи авторов сильно разнятся)
        $authorMatch = false;
//        if (!empty($fields['author'])) {
//            // Простая проверка: содержится ли фамилия первого автора из BibTeX в ответе API
//            $firstAuthor = explode(',', $fields['author'])[0];
//            if (mb_stripos($externalData['authors'], trim($firstAuthor)) !== false) {
//                $authorMatch = true;
//            }
//        }

        // Если название совпадает более чем на 85%, считаем что нашли
        if ($titleSimilarity > 0.85) {
            return [
                'found' => true,
                'external_data' => $externalData,
                'similarity' => $titleSimilarity,
                'message' => "Источник найден"
            ];
        }

        return [
            'found' => false,
            'message' => "Похожий источник найден, но название совпадает лишь на " . round($titleSimilarity * 100) . "%"
        ];
    }


}
