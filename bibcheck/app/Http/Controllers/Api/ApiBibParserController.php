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

        // $data = $request->validate([
        //     'text' => ['required_without:file', 'prohibits:file', 'string', 'max:100000'],
        //     'file' => ['required_without:text', 'prohibits:text', 'file', 'mimes:bib,txt', 'max:1024'],
        // ]);

        // if ($request->hasFile('file') && $request->filled('text')) {
        //     return response()->json([
        //             'message' => 'Validation failed',
        //             'errors' => [
        //                 'text' => 'Передайте text или file.'
        //             ]
        //     ], 422);
        // }

        // if (isset($data['file'])) {
        //     $content = file_get_contents($request->file('file')->getRealPath());
        // } elseif (isset($data['text']) && !empty($data['text'])) {
        //     $content = $request->input('text');
        // } elseif (empty($data['text'])) {
        //     return response()->json([

        //         // 'data' => "Вы не передали данных для паринга",

        //         'message' => 'Error find data',
        //             'errors' => [
        //                 'text' => 'Вы не передали данных для паринга.'
        //             ]

        //     ], 422);
        // }else {
        //     return response()->json([
        //         'data' => "Неверный тип входных данных",
        //     ], 422);
        // }

        // $parsedText = $this->parserService->parse($content);

        // return response()->json([
        //     'data' => $parsedText,
        // ], 200);
    }
}
