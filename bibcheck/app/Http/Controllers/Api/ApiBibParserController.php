<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Bibtex\Parser;
use Illuminate\Http\Request;


/**
 * Надо проверить какой тип получаенных данных, перевести все в один тип и отправить в севрис 
 */
class ApiBibParserController extends Controller
{
    public function __construct(
        private Parser $parserService
    ) {}

    public function parse(Request $request) 
    {
        $data = $request->validate([
            'text' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:bib,txt'],
        ]);

        if ($request->hasFile('file') && $request->filled('text')) {
            return response()->json([
                'data' => 'Нельзя отправить сразу два типа данных',
            ], 422);
        }

        if (isset($data['file'])) {
            $content = file_get_contents($request->file('file')->getRealPath());
        } elseif (isset($data['text']) && !empty($data['text'])) {
            $content = $request->input('text');
        } elseif (empty($data['text'])) {
            return response()->json([
                'data' => "Вы не передали данных для паринга",
            ], 422);
        }else {
            return response()->json([
                'data' => "Неверный тип входных данных",
            ], 422);
        }

        $parsedText = $this->parserService->parse($content);

        return response()->json([
            'data' => $parsedText,
        ], 200);
    }
}
