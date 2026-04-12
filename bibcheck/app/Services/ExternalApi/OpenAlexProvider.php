<?php

namespace App\Services\ExternalApi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAlexProvider extends Provider{

    protected $client;

    // public function __construct()
    // {
    //     $this->client = Http::withoutVerifying()
    //         ->timeout(15)
    //         ->baseUrl(config('services.openalex.url'));
    // }


    public function __construct()
    {
        $this->client = Http::withoutVerifying()
            ->timeout(2)
            ->baseUrl(config('services.openalex.url'))
            ->withOptions([
                'query' => [
                    'api_key' => config('services.openalex.key'),
                ]
            ]);
    }

    /**
     * Поиск по заголовку с опциональными автором и годом.
     */
    public function findByTitle($title, ?string $author = null, ?string $year = null)
    {
        try {
            $cleanTitle = trim($title);

            // 1. ПЕРВЫЙ ПРОХОД: без флага include_xpac
            $firstResponse = $this->client->get("works", [
                'search' => $cleanTitle,
            ])->json();

            $firstMatch = $this->pickBestMatch($cleanTitle, $firstResponse['results'] ?? []);

            // Если нашли идеальное совпадение (например, > 95%), сразу возвращаем
                if ($firstMatch && $firstMatch['similarity'] >= 95) {
                    return ['results' => [$firstMatch]];
            }

            // 2. ВТОРОЙ ПРОХОД: если 100% (или 95%+) не нашли, пробуем с include_xpac
            $xpacResponse = $this->client->get("works", [
                'search'       => $cleanTitle,
                'include_xpac' => 'true',
            ])->json();

            $xpacMatch = $this->pickBestMatch($cleanTitle, $xpacResponse['results'] ?? []);

            // 3. СРАВНЕНИЕ
            $bestMatch = null;

            if ($firstMatch && $xpacMatch) {
                // Выбираем тот, где процент сходства выше
                $bestMatch = ($xpacMatch['similarity'] > $firstMatch['similarity'])
                    ? $xpacMatch
                    : $firstMatch;
            } else {
                // Если одного из них нет, берем тот, что нашелся
                $bestMatch = $firstMatch ?: $xpacMatch;
            }

            return ['title' => $bestMatch ? [$bestMatch] : []];

        } catch (\Throwable $e) {
            Log::error("OpenAlex Double Check failed: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Выбирает лучшее совпадение из массива результатов по схожести заголовков.
     */
    public function pickBestMatch(string $cleanTitle, array $results): ?array
    {
        if (empty($results)) return null;

        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($results as $work) {
            // У OpenAlex заголовок лежит в $work['title']
            $currentTitle = $work['title'] ?? '';

            similar_text(
                mb_strtolower($cleanTitle),
                mb_strtolower($currentTitle),
                $percent
            );

//            dump("Title: $currentTitle | Score: $percent");

            if ($percent > $bestSimilarity) {
                $bestSimilarity = $percent;
                $bestMatch = $work;
            }
        }

        // Если сходство слишком низкое, считаем что ничего не нашли
        if ($bestSimilarity < 48) {
            Log::info("OpenAlex: Сходство слишком низкое: $bestSimilarity%");
            return null;
        }

        return [
            'title'   => $bestMatch['title'],
            'authors' => collect($bestMatch['authorships'] ?? [])
                ->map(fn($a) => $a['author']['display_name'] ?? '')
                ->filter()
                ->implode(', '),
            'year'    => $bestMatch['publication_year'] ?? null,
            'similarity' => $bestSimilarity
        ];
    }





    /**
     * Извлекает фамилию автора из строки "Фамилия, И. О." или "Smith, John".
     */
    private function extractLastName(string $author): ?string
    {
        // Берём первую часть до запятой
        $parts = explode(',', $author, 2);
        $lastName = trim($parts[0]);

        // Если нет запятой — берём последнее слово
        if (empty($lastName)) {
            $words = preg_split('/\s+/', trim($author));
            $lastName = end($words);
        }

        // Только буквы/кириллица, минимум 2 символа
        $lastName = preg_replace('/[^\p{L}\-\.]/u', '', $lastName);

        return (mb_strlen($lastName) >= 2) ? $lastName : null;
    }

    /**
     * Резервный поиск через Crossref API.
     */
    private function searchCrossref(string $title, ?string $author = null, ?string $year = null): ?array
    {
        try {
            $params = [
                'query.title' => $title,
                'rows' => 10,
            ];
            if ($author) $params['query.author'] = $author;

            $response = Http::timeout(15)->get('https://api.crossref.org/works', $params);

            if (!$response->successful()) {
                Log::warning("Crossref API error: HTTP {$response->status()}");
                return null;
            }

            $data = $response->json();
            $items = $data['message']['items'] ?? [];

            if (empty($items)) {
                Log::info("Crossref: не найдено по заголовку: $title");
                return null;
            }

            // Фильтруем по году если указан
            if ($year) {
                $items = array_filter($items, function($item) use ($year) {
                    $itemYear = $item['published-print']['date-parts'][0][0]
                        ?? $item['published-online']['date-parts'][0][0]
                        ?? $item['created']['date-parts'][0][0] ?? null;
                    return $itemYear && abs($itemYear - (int)$year) <= 2;
                });
            }

            Log::info("Crossref ответ:", [
                'total' => $data['message']['total-results'] ?? 0,
                'results' => collect($items)->map(fn($r) => [
                    'title' => $r['title'][0] ?? '',
                    'score' => $r['score'] ?? 0,
                ])->take(5)->toArray()
            ]);

            if (empty($items)) {
                Log::info("Crossref: не найдено после фильтрации по году ($year)");
                return null;
            }

            // Выбираем лучшее совпадение
            $cleanTitle = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s\-\.]/u', ' ', $title));
            $cleanTitle = preg_replace('/\s+/', ' ', trim($cleanTitle));

            $bestMatch = null;
            $bestSimilarity = 0;

            foreach ($items as $item) {
                $itemTitle = $item['title'][0] ?? '';
                similar_text($cleanTitle, mb_strtolower($itemTitle), $percent);

                if ($percent > $bestSimilarity) {
                    $bestSimilarity = $percent;
                    $bestMatch = $item;
                }
            }

            if (!$bestMatch || $bestSimilarity < 60) {
                Log::info("Crossref: лучшее совпадение слишком низкое ({$bestSimilarity}%)");
                return null;
            }

            $authorNames = [];
            if (!empty($bestMatch['author'])) {
                foreach ($bestMatch['author'] as $a) {
                    $name = trim(($a['family'] ?? '') . ', ' . ($a['given'] ?? ''));
                    if ($name) $authorNames[] = trim($name, ', ');
                }
            }

            Log::info("Crossref: выбрано '{$bestMatch['title'][0]}' (схожесть: {$bestSimilarity}%)");

            return [
                'title'  => $bestMatch['title'][0] ?? '',
                'authors' => implode(', ', $authorNames),
            ];

        } catch (\Throwable $e) {
            Log::error("Crossref request failed: {$e->getMessage()}");
            return null;
        }
    }






}
