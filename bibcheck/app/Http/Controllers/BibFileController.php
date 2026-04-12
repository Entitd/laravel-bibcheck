<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BibFile;
use App\Models\CheckHistory;
use Illuminate\Support\Facades\Storage;
use App\Services\BibtexService;
use Illuminate\Support\Facades\Auth;

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
        $analysisResults['original_filename'] = $bibFile->filename;
        $analysisResults['course_comparison_result'] = $this->generateVerdict($analysisResults['aggregated_metrics']);

        // Сохраняем в историю проверок
        $this->saveToHistory($bibFile->filename, $content, $analysisResults);

        // Возвращаемся назад и кладем результат в сессию
        return redirect()->route('bib.blade')->with('analysis', $analysisResults);
    }

    public function update(Request $request)
    {
        $request->validate(['bib_content' => 'required|string']);

        $content = $request->input('bib_content');

        $analysisResults = $this->parserService->fullCheck($content);

        $analysisResults['raw_content'] = $content;
        $analysisResults['original_filename'] = $request->input('original_filename', 'edited_file.bib');
        $analysisResults['course_comparison_result'] = $this->generateVerdict($analysisResults['aggregated_metrics']);

        // Сохраняем в историю проверок
        $this->saveToHistory($analysisResults['original_filename'], $content, $analysisResults);

        // Если это AJAX-запрос, возвращаем только ошибки
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'errors' => $analysisResults['errors']
            ]);
        }

        // Возвращаемся назад и кладем результат в сессию
        return redirect()->route('bib.blade')->with('analysis', $analysisResults);
    }

    /**
     * Формирует текстовый вердикт на основе агрегированных метрик.
     */
    private function generateVerdict(array $metrics): string
    {
        $total = $metrics['totalQuantity'] ?? 0;

        if ($total === 0) {
            return 'Не удалось проанализировать ни одной записи.';
        }

        $foreign = $metrics['amountOfLiteratureInForeignLanguages'] ?? 0;
        $periodicals = $metrics['numberOfCurrentScientificPeriodicals'] ?? 0;
        $modern = $metrics['Literature21Century'] ?? 0;
        $apiFound = $metrics['api_found'] ?? 0;
        $apiAvgSimilarity = $metrics['api_average_similarity'] ?? 0;

        $issues = [];

        if ($total < 10) {
            $issues[] = "мало источников ($total, рекомендуется от 10)";
        }
        if ($foreign === 0) {
            $issues[] = 'нет источников на иностранных языках';
        }
        if ($periodicals === 0) {
            $issues[] = 'нет научных периодических изданий';
        }
        if ($modern === 0) {
            $issues[] = 'нет источников XXI века';
        }
        if ($apiFound === 0 && $total > 0) {
            $issues[] = 'ни один источник не найден в OpenAlex';
        }

        if (empty($issues)) {
            return "Полностью соответствует требованиям. Источников: $total, иностранных: $foreign, периодика: $periodicals, современные: $modern, найдено в OpenAlex: $apiFound (среднее совпадение: {$apiAvgSimilarity}%).";
        }

        return 'Не соответствует требованиям кафедры: ' . implode(', ', $issues) . '.';
    }

    /**
     * Сохранить результат проверки в историю
     */
    private function saveToHistory(string $filename, string $content, array $analysisResults): void
    {
        $user = Auth::user();
        
        if (!$user) {
            return; // Не сохраняем для неавторизованных
        }

        $metrics = $analysisResults['aggregated_metrics'] ?? [];
        $errors = $analysisResults['errors'] ?? [];
        
        $errorCount = 0;
        $warningCount = 0;
        
        foreach ($errors as $error) {
            if (($error['severity'] ?? 'error') === 'warning') {
                $warningCount++;
            } else {
                $errorCount++;
            }
        }

        $verdict = $this->generateVerdict($metrics);

        CheckHistory::create([
            'user_id' => $user->id,
            'filename' => $filename,
            'content' => $content,
            'status' => 'completed',
            'stats' => $metrics,
            'analysis_data' => $analysisResults, // сохраняем все результаты проверки
            'verdict' => $verdict,
            'total_entries' => $metrics['totalQuantity'] ?? 0,
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
        ]);
    }

}
