<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibtexTypeEntry;
use App\Models\CourseRequirement;
use App\Services\Bibtex\GostValidator;
use Illuminate\Http\JsonResponse;

class ApiBibMetadataController extends Controller
{
    public function types(): JsonResponse
    {
        $types = BibtexTypeEntry::query()
            ->with(['fields' => fn ($query) => $query
                ->orderBy('bibtex_field_bibtex_type_entry.sort_order')
                ->orderBy('name_field')])
            ->orderBy('name_type_entry')
            ->get();

        if ($types->isNotEmpty()) {
            return response()->json([
                'data' => $types->map(fn (BibtexTypeEntry $type) => $this->typePayload(
                    $type->name_type_entry,
                    $type->fields->pluck('name_field')->values()->all(),
                    $type->id
                ))->values(),
            ]);
        }

        return response()->json([
            'data' => collect(GostValidator::supportedTypes())
                ->map(fn (array $fields, string $type) => $this->typePayload($type, $fields))
                ->sortBy('name')
                ->values(),
        ]);
    }

    public function fields(string $type): JsonResponse
    {
        $normalizedType = strtolower($type);
        $typeEntry = BibtexTypeEntry::query()
            ->with(['fields' => fn ($query) => $query
                ->orderBy('bibtex_field_bibtex_type_entry.sort_order')
                ->orderBy('name_field')])
            ->where('name_type_entry', $normalizedType)
            ->first();

        if ($typeEntry !== null) {
            return response()->json([
                'data' => $this->typePayload(
                    $typeEntry->name_type_entry,
                    $typeEntry->fields->pluck('name_field')->values()->all(),
                    $typeEntry->id
                ),
            ]);
        }

        $requiredFields = GostValidator::requiredFieldsFor($normalizedType);

        if ($requiredFields === null) {
            return response()->json([
                'message' => "BibTeX type '{$type}' is not supported.",
            ], 404);
        }

        return response()->json([
            'data' => $this->typePayload($normalizedType, $requiredFields),
        ]);
    }

    public function requirements(): JsonResponse
    {
        return response()->json([
            'data' => CourseRequirement::query()
                ->orderBy('course_number')
                ->get()
                ->values(),
        ]);
    }

    private function typePayload(string $type, array $requiredFields, ?int $id = null): array
    {
        return [
            'id' => $id,
            'name' => $type,
            'required_fields' => array_values($requiredFields),
            'recommended_fields' => GostValidator::recommendedFields(),
        ];
    }
}
