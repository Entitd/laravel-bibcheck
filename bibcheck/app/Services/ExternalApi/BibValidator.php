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
        if (empty($fields['title'])) {
            return ['found' => false, 'message' => 'Отсутствует поле title'];
        }

        $provider = new OpenAlexProvider();
        $author = $fields['author'] ?? null;
        $year = $fields['year'] ?? null;

        $externalData = $provider->findByTitle($fields['title'], $author, $year);

        if (!$externalData) {
            return [
                'found' => false,
                'message' => "Источник не найден в базе OpenAlex."
            ];
        }

//        dump("externalData:" ,  [$externalData]);

        // Проверяем схожесть названий
//        $titleSimilarity = $this->calculateSimilarity($fields['title'], $externalData['title']);

        $titleSimilarity = $externalData['title'][0]['similarity'];
//        dump("titleSimilarity:" ,  [$titleSimilarity]);

        // Если название совпадает более чем на 85%, считаем что нашли
        if ($titleSimilarity > 85) {
            return [
                'found' => true,
                'external_data' => $externalData,
                'similarity' => $titleSimilarity,
                'message' => "Источник найден"
            ];
        }

        // Возвращаем информацию даже если не нашли — для отладки
        return [
            'found' => false,
            'similarity' => $titleSimilarity,
            'external_title' => $externalData['title'],
            'message' => "Похожий источник найден, но название совпадает лишь на " . round($titleSimilarity) . "%"
        ];
    }


}
