<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

use App\Http\Controllers\BibFileController;

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


require __DIR__.'/settings.php';
