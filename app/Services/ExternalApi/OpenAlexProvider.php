<?php

namespace App\Services\ExternalApi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAlexProvider extends Provider
{
    protected $client;

    public function __construct()
    {
        $apiKey = auth()->user()?->openalex_api_key ?: config('services.openalex.key');

        $this->client = Http::withoutVerifying()
            ->timeout(15)
            ->retry(3, 300)
            ->baseUrl(config('services.openalex.url'));

        if (filled($apiKey)) {
            $this->client = $this->client->withOptions([
                'query' => [
                    'api_key' => $apiKey,
                ],
            ]);
        }
    }

    public function findByTitle($title, ?string $author = null, ?string $year = null): array
    {
        try {
            $cleanTitle = trim($title);

            $firstSearch = $this->searchWorks($cleanTitle);
            if ($firstSearch['status'] !== 'ok') {
                return [
                    'status' => 'api_error',
                    'match' => null,
                    'message' => $firstSearch['message'],
                ];
            }

            $firstMatch = $this->pickBestMatch($cleanTitle, $firstSearch['results']);

            if ($firstMatch && $firstMatch['similarity'] >= 95) {
                return [
                    'status' => 'found',
                    'match' => $firstMatch,
                    'message' => 'Источник найден в OpenAlex.',
                ];
            }

            $xpacSearch = $this->searchWorks($cleanTitle, true);
            if ($xpacSearch['status'] !== 'ok') {
                return [
                    'status' => 'api_error',
                    'match' => null,
                    'message' => $xpacSearch['message'],
                ];
            }

            $xpacMatch = $this->pickBestMatch($cleanTitle, $xpacSearch['results']);

            $bestMatch = null;

            if ($firstMatch && $xpacMatch) {
                $bestMatch = ($xpacMatch['similarity'] > $firstMatch['similarity'])
                    ? $xpacMatch
                    : $firstMatch;
            } else {
                $bestMatch = $firstMatch ?: $xpacMatch;
            }

            if (!$bestMatch) {
                return [
                    'status' => 'not_found',
                    'match' => null,
                    'message' => 'Источник не найден в базе OpenAlex.',
                ];
            }

            return [
                'status' => 'found',
                'match' => $bestMatch,
                'message' => 'Источник найден в OpenAlex.',
            ];
        } catch (\Throwable $e) {
            Log::error("OpenAlex Double Check failed: {$e->getMessage()}");

            return [
                'status' => 'api_error',
                'match' => null,
                'message' => 'Не удалось выполнить запрос к OpenAlex.',
            ];
        }
    }

    public function pickBestMatch(string $cleanTitle, array $results): ?array
    {
        if (empty($results)) {
            return null;
        }

        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($results as $work) {
            $currentTitle = $work['title'] ?? '';

            similar_text(
                mb_strtolower($cleanTitle),
                mb_strtolower($currentTitle),
                $percent
            );

            if ($percent > $bestSimilarity) {
                $bestSimilarity = $percent;
                $bestMatch = $work;
            }
        }

        if ($bestSimilarity < 48) {
            Log::info("OpenAlex: similarity too low: {$bestSimilarity}%");
            return null;
        }

        return [
            'title' => $bestMatch['title'],
            'authors' => collect($bestMatch['authorships'] ?? [])
                ->map(fn ($a) => $a['author']['display_name'] ?? '')
                ->filter()
                ->implode(', '),
            'year' => $bestMatch['publication_year'] ?? null,
            'similarity' => $bestSimilarity,
        ];
    }

    private function searchWorks(string $cleanTitle, bool $includeXpac = false): array
    {
        $params = [
            'search' => $cleanTitle,
        ];

        if ($includeXpac) {
            $params['include_xpac'] = 'true';
        }

        $response = $this->client->get('works', $params);

        if (!$response->successful()) {
            Log::warning('OpenAlex request failed', [
                'status' => $response->status(),
                'title' => $cleanTitle,
                'include_xpac' => $includeXpac,
            ]);

            return [
                'status' => 'api_error',
                'results' => [],
                'message' => "OpenAlex вернул HTTP {$response->status()}.",
            ];
        }

        return [
            'status' => 'ok',
            'results' => $response->json('results') ?? [],
            'message' => null,
        ];
    }

    private function extractLastName(string $author): ?string
    {
        $parts = explode(',', $author, 2);
        $lastName = trim($parts[0]);

        if (empty($lastName)) {
            $words = preg_split('/\s+/', trim($author));
            $lastName = end($words);
        }

        $lastName = preg_replace('/[^\p{L}\-\.]/u', '', $lastName);

        return (mb_strlen($lastName) >= 2) ? $lastName : null;
    }

    private function searchCrossref(string $title, ?string $author = null, ?string $year = null): ?array
    {
        try {
            $params = [
                'query.title' => $title,
                'rows' => 10,
            ];
            if ($author) {
                $params['query.author'] = $author;
            }

            $response = Http::timeout(15)->get('https://api.crossref.org/works', $params);

            if (!$response->successful()) {
                Log::warning("Crossref API error: HTTP {$response->status()}");
                return null;
            }

            $data = $response->json();
            $items = $data['message']['items'] ?? [];

            if (empty($items)) {
                Log::info("Crossref: not found by title: {$title}");
                return null;
            }

            if ($year) {
                $items = array_filter($items, function ($item) use ($year) {
                    $itemYear = $item['published-print']['date-parts'][0][0]
                        ?? $item['published-online']['date-parts'][0][0]
                        ?? $item['created']['date-parts'][0][0]
                        ?? null;

                    return $itemYear && abs($itemYear - (int) $year) <= 2;
                });
            }

            if (empty($items)) {
                Log::info("Crossref: nothing left after year filter ({$year})");
                return null;
            }

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
                Log::info("Crossref: similarity too low ({$bestSimilarity}%)");
                return null;
            }

            $authorNames = [];
            if (!empty($bestMatch['author'])) {
                foreach ($bestMatch['author'] as $a) {
                    $name = trim(($a['family'] ?? '') . ', ' . ($a['given'] ?? ''));
                    if ($name) {
                        $authorNames[] = trim($name, ', ');
                    }
                }
            }

            return [
                'title' => $bestMatch['title'][0] ?? '',
                'authors' => implode(', ', $authorNames),
            ];
        } catch (\Throwable $e) {
            Log::error("Crossref request failed: {$e->getMessage()}");
            return null;
        }
    }
}
