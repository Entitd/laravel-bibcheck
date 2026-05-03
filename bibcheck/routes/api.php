<?php

use App\Http\Controllers\Api\ApiBibParserController;
use Illuminate\Support\Facades\Route;


/**
 * ----------------НАДО РЕАЛИЗОВАТЬ----------------
 * - Просто парсер, который выдает разбитые записи в json
 * Просто метод который отпаршенные данные проверяет на соответствие ГОСТ
 * Метод который парсит данные, проверяет только на соответствие ГОСТ
 * Метод который парсит данные, проверяет только на соответствие нормам кафедры
 * Метод который парсит данные, проверяет соответствие ГОСТ и кафедры
 * Метод который отпаршенные данные, проверяет соответствие ГОСТ и кафедры
 */

/**
 * Парсинг текста/файла bib и txt
 */
Route::post('/bib/parse', [ApiBibParserController::class, 'parse']);
Route::post('/bib/check-gost', [ApiBibValidationController::class, 'checkGost']);








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
