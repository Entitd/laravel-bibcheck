<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

use App\Http\Controllers\BibFileController;
use App\Http\Controllers\CheckHistoryController;
use App\Http\Controllers\Admin\BibtexController as AdminBibTexController;

use App\Http\Controllers\Admin\BibtexController;
use App\Http\Controllers\Admin\BibtexFieldController;

use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\ProfileController;
use App\Support\BibEditorViewData;

use function Pest\Laravel\post;

Route::get('apiCheck', [\App\Services\ExternalApi\OpenAlexProvider::class, 'findByTitle']);


Route::post('/upload-bib', [BibFileController::class, 'upload'])->name('bib.upload');

// Страница с Blade-формой
//Route::get('/', function () {
//    return view('analyzer');
//})->name('bib.blade');

/**
 * Маршруты аутентификации (Fortify - автоматически регистрируются):
 * 
 * GET  /login          - страница входа (Fortify::loginView)
 * POST /login          - выполнить вход
 * GET  /register       - страница регистрации (Fortify::registerView)
 * POST /register       - выполнить регистрацию
 * POST /logout         - выход из системы
 * 
 * Эти маршруты НЕ видны здесь, но работают через FortifyServiceProvider
 */

Route::get('/', function () {
    // Если пользователь не авторизован, редиректим на вход
    if (!auth()->check()) {
        return redirect()->route('guest.login');
    }
    
    // Проверяем, не истекла ли сессия гостя
    $user = auth()->user();
    if ($user->isGuest() && $user->isGuestSessionExpired()) {
        auth()->logout();
        return redirect()->route('guest.login')
            ->with('error', 'Гостевая сессия истекла. Пожалуйста, войдите снова.');
    }
    
    $analysis = session('analysis');

    return view('bib.editor', BibEditorViewData::make($analysis, $user));
})->name('bib.blade');

// Роут для обработки формы именно из Blade
Route::post('/upload-bib-blade', [BibFileController::class, 'uploadBlade'])->name('bib.upload.blade');

// Роут для обновления отредактированного файла
Route::post('/update-bib', [BibFileController::class, 'update'])->name('bib.update');

// Роуты для профиля пользователя
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/api-key', [ProfileController::class, 'updateApiKey'])->name('profile.apikey.update');
    Route::delete('/profile/api-key', [ProfileController::class, 'deleteApiKey'])->name('profile.apikey.delete');
    Route::put('/profile/name', [ProfileController::class, 'updateName'])->name('profile.name.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/guest/extend', [ProfileController::class, 'extendGuestSession'])->name('profile.guest.extend');
    Route::post('/profile/guest/register', [ProfileController::class, 'registerFromGuest'])->name('profile.guest.register');
    Route::get('/profile/guest/register', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/register');
    })->name('profile.guest.register.form');
    
    // API для истории проверок
    Route::get('/api/check-history', [CheckHistoryController::class, 'index'])->name('check-history.index');
    Route::get('/check-history/{id}', [CheckHistoryController::class, 'show'])->name('check-history.show');
    Route::delete('/api/check-history/{id}', [CheckHistoryController::class, 'destroy'])->name('check-history.destroy');
});

// Роуты для входа как гость
Route::get('/guest/login', function () {
    // Если уже авторизован, редиректим на главную
    if (auth()->check()) {
        return redirect('/');
    }
    // Показываем страницу выбора входа
    return view('auth.guest-login');
})->name('guest.login');


// Роуты для регистраици
Route::get('/guest/register', function () {
    // Если уже авторизован, редиректим на главную
    if (auth()->check()) {
        return redirect('/');
    }
    // Показываем страницу выбора входа
    return view('register');
})->name('guest.register');


Route::post('/guest/login', [ProfileController::class, 'loginAsGuest'])->name('guest.login.submit');


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
