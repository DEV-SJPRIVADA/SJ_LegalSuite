<?php

use App\Http\Controllers\Disciplinary\DisciplinaryCaseController;
use App\Http\Controllers\Disciplinary\DisciplinaryDashboardController;
use App\Http\Controllers\Disciplinary\FoGj51InformeController;
use App\Http\Controllers\Disciplinary\OfficialFormBlankDownloadController;
use App\Http\Controllers\Disciplinary\OfficialFormPreviewController;
use App\Livewire\Auth\ForcePasswordChange;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use App\Livewire\Disciplinary\Cases\CasesIndex;
use App\Livewire\Disciplinary\Dashboard;
use App\Livewire\Disciplinary\FormatsCatalog;
use App\Livewire\Disciplinary\InformesPendientes;
use App\Livewire\Home;
use App\Livewire\Users\UserDetail;
use App\Livewire\Users\UsersIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth'])->group(function () {
    Route::get('password/first-login', ForcePasswordChange::class)->name('password.force-change');
});

Route::middleware(['auth', 'must-change-password'])->group(function () {
    Route::view('profile', 'profile')->name('profile');
});

Route::middleware(['auth', 'must-change-password', 'verified'])->group(function () {
    Route::get('dashboard', Home::class)->name('dashboard');

    Route::prefix('disciplinary')->name('disciplinary.')->group(function () {
        Route::get('dashboard', Dashboard::class)->name('dashboard');
        Route::get('formats', FormatsCatalog::class)->name('formats.index');
        Route::get('formats/descarga-en-blanco/{code}', OfficialFormBlankDownloadController::class)
            ->where('code', '[A-Za-z0-9\-]+')
            ->name('formats.download-blank');
        Route::get('formats/preview/{code}', OfficialFormPreviewController::class)
            ->where('code', '[A-Za-z0-9\-]+')
            ->name('formats.preview');

        Route::get('forms/informe-fo-gj-51', [FoGj51InformeController::class, 'show'])
            ->name('forms.informe-fo-gj-51');
        Route::post('forms/informe-fo-gj-51', [FoGj51InformeController::class, 'process'])
            ->name('forms.informe.process');

        Route::get('informes-pendientes', InformesPendientes::class)->name('informes-pendientes.index');
        Route::get('informes-pendientes/{submission}/pdf', [FoGj51InformeController::class, 'pendingPdf'])
            ->name('informes-pendientes.pdf');

        Route::get('cases', CasesIndex::class)->name('cases.index');
        Route::get('cases/{case}', CaseDetail::class)->name('cases.show');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', UsersIndex::class)->name('index');
        Route::get('/{user}', UserDetail::class)->name('show');
    });

    Route::prefix('api/disciplinary')->name('api.disciplinary.')->group(function () {
        Route::get('dashboard', DisciplinaryDashboardController::class)->name('dashboard');
        Route::get('cases', [DisciplinaryCaseController::class, 'index'])->name('cases.index');
        Route::post('cases', [DisciplinaryCaseController::class, 'store'])->name('cases.store');
        Route::get('cases/{case}', [DisciplinaryCaseController::class, 'show'])->name('cases.show');
        Route::get('cases/{case}/transitions', [DisciplinaryCaseController::class, 'allowedTransitions'])->name('cases.transitions');
        Route::post('cases/{case}/transition', [DisciplinaryCaseController::class, 'transition'])->name('cases.transition');
    });
});

require __DIR__.'/auth.php';
