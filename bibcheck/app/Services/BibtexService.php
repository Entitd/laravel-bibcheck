<?php

namespace App\Services;

use App\DTO\BibEntryDTO;
use App\Services\Bibtex\Parser;
use App\Services\Bibtex\GostValidator;
use App\Services\ExternalApi\BibValidator;

class BibtexService
{
    public function __construct(
        private Parser $parser,
        private GostValidator $gost,
        private BibValidator $api
    ) {}

    public function fullCheck(string $bibText): array
    {
        $data = $this->parser->parse($bibText);

        $results = [];

        /** @var BibEntryDTO $entry */
        foreach ($data['entries'] as $entry) {
            $gostErrors = $this->gost->validate($entry);
            $apiResult = $this->api->verifySourceOnline($entry->fields);

            $results[$entry->key] = [
                'type' => $entry->type,
                'line' => $entry->startLine,
                'fields' => $entry->fields,
                'gost_errors' => $gostErrors,
                'api_check' => $apiResult,
            ];
        }

        $apiMetrics = $this->calculateApiMetrics($results);
        $metrics = array_merge($this->gost->calculateMetrics($data['entries']), $apiMetrics);

        return [
            'errors' => $data['error'] ?? [],
            'entries' => $results,
            'aggregated_metrics' => $metrics,
        ];
    }

    private function calculateApiMetrics(array $results): array
    {
        $found = 0;
        $notFound = 0;
        $errors = 0;
        $totalSimilarity = 0.0;
        $similarityCount = 0;

        foreach ($results as $entry) {
            $api = $entry['api_check'] ?? [];

            if ($api['found'] ?? false) {
                $found++;
                if (isset($api['similarity'])) {
                    $totalSimilarity += $api['similarity'];
                    $similarityCount++;
                }
            } elseif (($api['status'] ?? null) === 'api_error') {
                $errors++;
            } else {
                $notFound++;
            }
        }

        return [
            'api_found' => $found,
            'api_not_found' => $notFound,
            'api_errors' => $errors,
            'api_average_similarity' => $similarityCount > 0 ? round($totalSimilarity / $similarityCount) : 0,
        ];
    }
}
