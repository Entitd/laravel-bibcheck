<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BibFile;
use App\Services\BibtexParserService; // Твой сервис
use Illuminate\Support\Facades\Storage;

class BibFileController extends Controller
{
    protected $parserService;

    // Внедряем сервис через конструктор
    public function __construct(BibtexParserService $parserService)
    {
        $this->parserService = $parserService;
    }

    public function upload(Request $request)
    {
        // 1. Валидация файла
        $request->validate([
            'bib_file' => 'required|file', // можно добавить |mimes:bib,txt если нужно
        ]);

        // 2. Сохраняем файл на диск (в storage/app/bib_uploads)
        $path = $request->file('bib_file')->store('bib_uploads');

        // 3. Создаем запись в таблице bib_files
        $bibFile = BibFile::create([
            'filename' => $request->file('bib_file')->getClientOriginalName(),
            'path' => $path,
            'status' => 'pending',
            'user_id' => auth()->id(), // null, если юзер не залогинен
        ]);

        // 4. Запускаем парсинг и сохранение в БД (entries и errors)
        $this->parserService->parseAndSave($bibFile);

        // 5. Запускаем анализ метрик (твоя функция proverka_na_kafedru)
        $content = Storage::get($path);
        $analysisResults = $this->parserService->analyze($content);

        // Обновляем статистику в главном файле
        $bibFile->update([
            'stats' => $analysisResults['aggregated_metrics'],
        ]);

        // Возвращаем результат (пока в JSON для теста)
        return response()->json([
            'message' => 'Файл успешно обработан',
            'file_id' => $bibFile->id,
            'analysis' => $analysisResults,
        ]);

    }


    public function uploadBlade(Request $request)
    {
        $request->validate(['bib_file' => 'required|file']);
        $path = $request->file('bib_file')->store('bib_uploads');

        // Твой созданный BibFile (если модель готова)
        $bibFile = \App\Models\BibFile::create([
            'filename' => $request->file('bib_file')->getClientOriginalName(),
            'path' => $path,
            'status' => 'completed'
        ]);

        // Твой сервис
        $content = \Illuminate\Support\Facades\Storage::get($path);
        $analysisResults = $this->parserService->analyze($content);

        // Возвращаемся назад и кладем результат в сессию
        return redirect()->route('bib.blade')->with('analysis', $analysisResults);
    }
    
}
