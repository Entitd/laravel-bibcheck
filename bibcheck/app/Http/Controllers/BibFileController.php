<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BibFile;
use Illuminate\Support\Facades\Storage;
use App\Services\BibtexParserService;

class BibFileController extends Controller
{
    protected $parserService;

    // Внедряем сервис через конструктор
    public function __construct(BibtexParserService $parserService)
    {
        $this->parserService = $parserService;
    }

    public function uploadBlade(Request $request)
    {
        $request->validate(['bib_file' => 'required|file']);
        $path = $request->file('bib_file')->store('bib_uploads');

        $bibFile = \App\Models\BibFile::create([
            'filename' => $request->file('bib_file')->getClientOriginalName(),
            'path' => $path,
            'status' => 'completed'
        ]);

        $content = \Illuminate\Support\Facades\Storage::get($path);
        $analysisResults = $this->parserService->analyze($content);

        $analysisResults['raw_content'] = $content;
        $analysisResults['filename'] = $bibFile->filename;

        // Возвращаемся назад и кладем результат в сессию
        return redirect()->route('bib.blade')->with('analysis', $analysisResults);
    }

    public function update(Request $request)
    {
        $request->validate(['bib_content' => 'required|string']);

        $content = $request->input('bib_content');

        // Отладка: логируем количество строк
        \Log::info('BIB Update - строки: ' . substr_count($content, "\n") + 1);
        \Log::info('BIB Update - первые 500 символов: ' . substr($content, 0, 500));

        $analysisResults = $this->parserService->analyze($content);

        $analysisResults['raw_content'] = $content;
        $analysisResults['filename'] = $request->input('original_filename', 'edited_file.bib');

        // Возвращаемся назад и кладем результат в сессию
        return redirect()->route('bib.blade')->with('analysis', $analysisResults);
    }

}
