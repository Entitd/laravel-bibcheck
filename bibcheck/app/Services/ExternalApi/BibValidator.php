<?php

namespace App\Services\ExternalApi;

class BibValidator
{
    public function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = mb_strtolower(preg_replace('/[^\p{L}\p{N} ]/u', '', $str1));
        $str2 = mb_strtolower(preg_replace('/[^\p{L}\p{N} ]/u', '', $str2));

        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }

    public function verifySourceOnline(array $fields): array
    {
        if (empty($fields['title'])) {
            return [
                'found' => false,
                'status' => 'invalid_input',
                'similarity' => null,
                'external_data' => null,
                'external_title' => null,
                'message' => 'Отсутствует поле title',
            ];
        }

        $provider = new OpenAlexProvider();
        $author = $fields['author'] ?? null;
        $year = $fields['year'] ?? null;

        $externalData = $provider->findByTitle($fields['title'], $author, $year);

        if (($externalData['status'] ?? null) === 'api_error') {
            return [
                'found' => false,
                'status' => 'api_error',
                'similarity' => null,
                'external_data' => null,
                'external_title' => null,
                'message' => $externalData['message'] ?? 'Не удалось выполнить запрос к OpenAlex.',
            ];
        }

        if (($externalData['status'] ?? null) === 'not_found') {
            return [
                'found' => false,
                'status' => 'not_found',
                'similarity' => null,
                'external_data' => null,
                'external_title' => null,
                'message' => $externalData['message'] ?? 'Источник не найден в базе OpenAlex.',
            ];
        }

        $match = $externalData['match'] ?? null;
        $titleSimilarity = $match['similarity'] ?? null;

        if ($match === null || $titleSimilarity === null) {
            return [
                'found' => false,
                'status' => 'api_error',
                'similarity' => null,
                'external_data' => null,
                'external_title' => null,
                'message' => 'OpenAlex вернул неполный ответ.',
            ];
        }

        if ($titleSimilarity > 85) {
            return [
                'found' => true,
                'status' => 'found',
                'external_data' => $match,
                'similarity' => $titleSimilarity,
                'external_title' => $match['title'] ?? null,
                'message' => 'Источник найден',
            ];
        }

        return [
            'found' => false,
            'status' => 'not_found',
            'similarity' => $titleSimilarity,
            'external_data' => $match,
            'external_title' => $match['title'] ?? null,
            'message' => 'Похожий источник найден, но название совпадает лишь на ' . round($titleSimilarity) . '%',
        ];
    }
}
