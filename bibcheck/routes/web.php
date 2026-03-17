<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

use App\Http\Controllers\BibFileController;
use App\Http\Controllers\Admin\BibtexController as AdminBibTexController;
//Route::inertia('/', 'welcome', [
//    'canRegister' => Features::enabled(Features::registration()),
//])->name('home');

//Route::middleware(['auth', 'verified'])->group(function () {
//    Route::inertia('dashboard', 'dashboard')->name('dashboard');
//});


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
Route::get('/admin/bibtex', [AdminBibTexController::class, 'index'])->name('admin.bibtex');

require __DIR__.'/settings.php';
