<?php

use App\Http\Controllers\Api\ApiBibParserController;
use Illuminate\Support\Facades\Route;

/**
 * Метод для парсинга, пользователь отправляет в теле текст или bib-файл 
 */
Route::post('/bib/parse', [ApiBibParserController::class, 'parse']);


// use Inertia\Inertia;
// use Laravel\Fortify\Features;

// use App\Http\Controllers\BibFileController;
// use App\Http\Controllers\CheckHistoryController;
// use App\Http\Controllers\Admin\BibtexController as AdminBibTexController;

// use App\Http\Controllers\Admin\BibtexController;
// use App\Http\Controllers\Admin\BibtexFieldController;

// use App\Http\Controllers\Admin\DepartmentController;
// use App\Http\Controllers\ProfileController;
// use App\Support\BibEditorViewData;

// use function Pest\Laravel\post;



// require __DIR__.'/settings.php';
