<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ParseBibRequest;
use App\Services\BibtexService;
use Illuminate\Http\JsonResponse;

class ApiBibValidationController extends Controller
{
    public function __construct(
        private BibtexService $bibtexService
    ) {}

    public function checkGost(ParseBibRequest $request): JsonResponse
    {
        $content = $request->bibContent();

        return response()->json([
            'data' => $this->bibtexService->localCheck($content),
        ]);
    }

    public function checkFull(ParseBibRequest $request): JsonResponse
    {
        $content = $request->bibContent();

        return response()->json([
            'data' => $this->bibtexService->fullCheck($content),
        ]);
    }

    public function checkDepartment(ParseBibRequest $request): JsonResponse
    {
        $content = $request->bibContent();

        return response()->json([
            'data' => $this->bibtexService->departmentCheck($content),
        ]);
    }

    public function checkExternal(ParseBibRequest $request): JsonResponse
    {
        $content = $request->bibContent();

        return response()->json([
            'data' => $this->bibtexService->externalCheck($content),
        ]);
    }
}
