<?php

use App\Http\Controllers\Disciplinary\DisciplinaryCaseController;
use App\Http\Controllers\Disciplinary\DisciplinaryDashboardController;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use App\Livewire\Disciplinary\Cases\CasesIndex;
use App\Livewire\Disciplinary\Dashboard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

/*
|--------------------------------------------------------------------------
| Módulo Disciplinario - Páginas Livewire (UI)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])
    ->prefix('disciplinary')
    ->name('disciplinary.')
    ->group(function () {
        Route::get('dashboard', Dashboard::class)->name('dashboard');
        Route::get('cases', CasesIndex::class)->name('cases.index');
        Route::get('cases/{case}', CaseDetail::class)->name('cases.show');
    });

/*
|--------------------------------------------------------------------------
| Módulo Disciplinario - API JSON (programática)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])
    ->prefix('api/disciplinary')
    ->name('api.disciplinary.')
    ->group(function () {
        Route::get('dashboard', DisciplinaryDashboardController::class)->name('dashboard');
        Route::get('cases', [DisciplinaryCaseController::class, 'index'])->name('cases.index');
        Route::post('cases', [DisciplinaryCaseController::class, 'store'])->name('cases.store');
        Route::get('cases/{case}', [DisciplinaryCaseController::class, 'show'])->name('cases.show');
        Route::get('cases/{case}/transitions', [DisciplinaryCaseController::class, 'allowedTransitions'])->name('cases.transitions');
        Route::post('cases/{case}/transition', [DisciplinaryCaseController::class, 'transition'])->name('cases.transition');
    });

require __DIR__.'/auth.php';
