<?php

namespace App\Services;

use App\DTO\BibEntryDTO;
use App\Models\CourseRequirement;
use App\Services\Bibtex\GostValidator;
use App\Services\Bibtex\Parser;
use App\Services\ExternalApi\BibValidator;

class BibtexService
{
    public function __construct(
        private Parser $parser,
        private GostValidator $gost,
        private BibValidator $api
    ) {}

    public function localCheck(string $bibText): array
    {
        $data = $this->parser->parse($bibText);

        $results = [];
        $errors = $data['error'] ?? [];

        /** @var BibEntryDTO $entry */
        foreach ($data['entries'] as $entry) {
            $gostErrors = $this->gost->validate($entry);
            $errors = array_merge($errors, $gostErrors);

            $results[$entry->key] = [
                'type' => $entry->type,
                'line' => $entry->startLine,
                'fields' => $entry->fields,
                'gost_errors' => $gostErrors,
            ];
        }

        return [
            'errors' => $errors,
            'entries' => $results,
            'aggregated_metrics' => $this->gost->calculateMetrics($data['entries']),
        ];
    }

    public function fullCheck(string $bibText): array
    {
        $analysis = $this->localCheck($bibText);
        $department = $this->compareWithDepartmentRequirements($analysis['aggregated_metrics']);

        foreach ($analysis['entries'] as $key => $entry) {
            $apiResult = $this->api->verifySourceOnline($entry['fields']);

            $analysis['entries'][$key]['api_check'] = $apiResult;
        }

        $apiMetrics = $this->calculateApiMetrics($analysis['entries']);

        $analysis['aggregated_metrics'] = array_merge($analysis['aggregated_metrics'], $apiMetrics);
        $analysis['department'] = $department;
        $analysis['course_comparison_result'] = $department['verdict'];

        return $analysis;
    }

    public function departmentCheck(string $bibText): array
    {
        $data = $this->parser->parse($bibText);
        $metrics = $this->gost->calculateMetrics($data['entries']);

        return [
            'errors' => $data['error'] ?? [],
            'entries' => $this->mapEntries($data['entries']),
            'aggregated_metrics' => $metrics,
            'department' => $this->compareWithDepartmentRequirements($metrics),
        ];
    }

    public function externalCheck(string $bibText): array
    {
        $data = $this->parser->parse($bibText);
        $entries = $this->mapEntries($data['entries']);

        foreach ($entries as $key => $entry) {
            $entries[$key]['api_check'] = $this->api->verifySourceOnline($entry['fields']);
        }

        return [
            'errors' => $data['error'] ?? [],
            'entries' => $entries,
            'aggregated_metrics' => $this->calculateApiMetrics($entries),
        ];
    }

    public function verifySource(array $fields): array
    {
        return $this->api->verifySourceOnline($fields);
    }

    private function mapEntries(array $entries): array
    {
        $mapped = [];

        /** @var BibEntryDTO $entry */
        foreach ($entries as $entry) {
            $mapped[$entry->key] = [
                'type' => $entry->type,
                'line' => $entry->startLine,
                'fields' => $entry->fields,
            ];
        }

        return $mapped;
    }

    private function compareWithDepartmentRequirements(array $metrics): array
    {
        $requirements = CourseRequirement::query()
            ->orderBy('course_number')
            ->get();

        $results = [];
        $bestMatch = null;
        $passedCourse = null;

        foreach ($requirements as $requirement) {
            $checks = [
                'totalQuantity' => [
                    'actual' => $metrics['totalQuantity'] ?? 0,
                    'required' => $requirement->min_total_quantity,
                ],
                'amountOfLiteratureInForeignLanguages' => [
                    'actual' => $metrics['amountOfLiteratureInForeignLanguages'] ?? 0,
                    'required' => $requirement->min_foreign_lang,
                ],
                'numberOfCurrentScientificPeriodicals' => [
                    'actual' => $metrics['numberOfCurrentScientificPeriodicals'] ?? 0,
                    'required' => $requirement->min_current_periodicals,
                ],
                'Literature21Century' => [
                    'actual' => $metrics['Literature21Century'] ?? 0,
                    'required' => $requirement->min_21st_century,
                ],
            ];

            foreach ($checks as $name => $check) {
                $checks[$name]['passed'] = $check['actual'] >= $check['required'];
            }

            $passedChecks = collect($checks)->where('passed', true)->count();
            $passed = $passedChecks === count($checks);

            $courseResult = [
                'course_number' => $requirement->course_number,
                'passed' => $passed,
                'passed_checks' => $passedChecks,
                'total_checks' => count($checks),
                'checks' => $checks,
            ];

            $results[] = $courseResult;

            if ($passed) {
                $passedCourse = $requirement->course_number;
            }

            if ($bestMatch === null || $passedChecks > $bestMatch['passed_checks']) {
                $bestMatch = $courseResult;
            }
        }

        return [
            'requirements' => $requirements->values(),
            'checks' => $results,
            'passed_course' => $passedCourse,
            'best_match' => $bestMatch,
            'verdict' => $this->makeDepartmentVerdict($passedCourse, $bestMatch),
        ];
    }

    private function makeDepartmentVerdict(?int $passedCourse, ?array $bestMatch): string
    {
        if ($passedCourse !== null) {
            return "Matches department requirements for course {$passedCourse}.";
        }

        if ($bestMatch !== null) {
            return "Does not match department requirements. Closest course: {$bestMatch['course_number']} ({$bestMatch['passed_checks']}/{$bestMatch['total_checks']} checks passed).";
        }

        return 'Department requirements are not configured.';
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
