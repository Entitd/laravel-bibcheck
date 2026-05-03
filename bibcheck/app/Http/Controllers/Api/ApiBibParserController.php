<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Bibtex\Parser;
use Illuminate\Http\Request;
use App\Http\Requests\Api\ParseBibRequest;

class ApiBibParserController extends Controller
{
    public function __construct(
        private Parser $parserService
    ) {}

    public function parse(ParseBibRequest $request)
    {
        $content = $request->bibContent();

        return response()->json([
            'data' => $this->parserService->parse($content),
        ]);
    }
}
