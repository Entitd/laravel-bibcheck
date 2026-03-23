<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

use App\Http\Controllers\BibFileController;
use App\Http\Controllers\Admin\BibtexController as AdminBibTexController;

use App\Http\Controllers\Admin\BibtexController;
use App\Http\Controllers\Admin\BibtexFieldController;

use App\Http\Controllers\Admin\DepartmentController;

use function Pest\Laravel\post;

Route::post('/upload-bib', [BibFileController::class, 'upload'])->name('bib.upload');

// Страница с Blade-формой
Route::get('/', function () {
    return view('analyzer');
})->name('bib.blade');

// Роут для обработки формы именно из Blade
Route::post('/upload-bib-blade', [BibFileController::class, 'uploadBlade'])->name('bib.upload.blade');


/**
 * Роуты для админки
 */
Route::prefix('admin')->name('admin.')->group(function () {

    // Главная страница админки
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Управление типами (BibtexController)
    Route::get('/bibtex', [BibtexController::class, 'index'])->name('bibtex.index');
    Route::post('/bibtex', [BibtexController::class, 'store'])->name('bibtex.store');
    Route::delete('/bibtex/{type}', [BibtexController::class, 'destroy'])->name('bibtex.destroy');

    // Управление полями (BibtexFieldController)
    // Метод index тут не нужен, так как поля выводятся на главной странице BibTeX
    Route::post('/fields', [BibtexFieldController::class, 'store'])->name('fields.store');
    Route::delete('/fields/{field}', [BibtexFieldController::class, 'destroy'])->name('fields.destroy');

    // Критерии кафедры (DepartmentController)
    Route::get('/department', [DepartmentController::class, 'index'])->name('department.index');
    Route::post('/department/requirements', [DepartmentController::class, 'updateRequirements'])->name('department.updateRequirements');
});



require __DIR__.'/settings.php';
