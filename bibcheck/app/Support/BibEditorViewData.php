<?php

namespace App\Support;

use App\Models\BibtexTypeEntry;
use App\Models\CheckHistory;
use App\Models\User;

class BibEditorViewData
{
    public static function make(?array $analysisData, ?User $user = null, ?CheckHistory $checkHistory = null): array
    {
        $analysisData ??= [];

        $entries = $analysisData['entries'] ?? [];
        $metrics = $analysisData['aggregated_metrics'] ?? [];
        $errors = $analysisData['errors'] ?? [];

        $totalEntries = count($entries);
        $errorCount = collect($errors)->where('severity', 'error')->count();
        $warningCount = collect($errors)->where('severity', 'warning')->count();
        $apiFoundCount = collect($entries)->filter(fn (array $entry) => data_get($entry, 'api_check.found'))->count();
        $apiErrorCount = collect($entries)->filter(fn (array $entry) => data_get($entry, 'api_check.status') === 'api_error')->count();
        $averageSimilarity = $metrics['api_average_similarity'] ?? null;
        $courseResult = $analysisData['course_comparison_result'] ?? null;
        $bibtexTypes = BibtexTypeEntry::query()
            ->with(['fields' => fn ($query) => $query->orderBy('bibtex_field_bibtex_type_entry.sort_order')->orderBy('name_field')])
            ->orderBy('name_type_entry')
            ->get()
            ->map(fn (BibtexTypeEntry $type) => [
                'id' => $type->id,
                'name' => $type->name_type_entry,
                'fields' => $type->fields->pluck('name_field')->values()->all(),
            ])
            ->values()
            ->all();
        $bibliographyMetrics = [
            [
                'label' => 'Всего источников',
                'value' => $metrics['totalQuantity'] ?? $totalEntries,
            ],
            [
                'label' => 'Иностранные источники',
                'value' => $metrics['amountOfLiteratureInForeignLanguages'] ?? 0,
            ],
            [
                'label' => 'Научная периодика',
                'value' => $metrics['numberOfCurrentScientificPeriodicals'] ?? 0,
            ],
            [
                'label' => 'Источники XXI века',
                'value' => $metrics['Literature21Century'] ?? 0,
            ],
        ];

        return [
            'analysis' => $analysisData ?: null,
            'analysisData' => $analysisData ?: null,
            'entries' => $entries,
            'metrics' => $metrics,
            'errors' => $errors,
            'totalEntries' => $totalEntries,
            'errorCount' => $errorCount,
            'warningCount' => $warningCount,
            'apiFoundCount' => $apiFoundCount,
            'apiErrorCount' => $apiErrorCount,
            'averageSimilarity' => $averageSimilarity,
            'bibliographyMetrics' => $bibliographyMetrics,
            'hasApiKey' => $user ? filled($user->openalex_api_key) : false,
            'courseResult' => $courseResult,
            'isSuccessVerdict' => $courseResult ? str_contains(mb_strtolower($courseResult), 'соответствует') : false,
            'checkHistory' => $checkHistory,
            'bibtexTypes' => $bibtexTypes,
        ];
    }
}
