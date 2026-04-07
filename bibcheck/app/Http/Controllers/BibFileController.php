<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BibFile;
use Illuminate\Support\Facades\Storage;
use App\Services\BibtexService;

class BibFileController extends Controller
{
    protected $parserService;

    // Внедряем сервис через конструктор
    public function __construct(BibtexService $parserService)
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
        $analysisResults = $this->parserService->fullCheck($content);

        $analysisResults['raw_content'] = $content;
        $analysisResults['filename'] = $bibFile->filename;

        // Возвращаемся назад и кладем результат в сессию
        return redirect()->route('bib.blade')->with('analysis', $analysisResults);
    }

    public function update(Request $request)
    {
        $request->validate(['bib_content' => 'required|string']);

        $content = $request->input('bib_content');

        $analysisResults = $this->parserService->analyze($content);

        $analysisResults['raw_content'] = $content;
        $analysisResults['filename'] = $request->input('original_filename', 'edited_file.bib');

        // Если это AJAX-запрос, возвращаем только ошибки
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'errors' => $analysisResults['errors']
            ]);
        }

        // Возвращаемся назад и кладем результат в сессию
        return redirect()->route('bib.blade')->with('analysis', $analysisResults);
    }

}
