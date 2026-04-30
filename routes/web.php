<?php

use App\Http\Controllers\Disciplinary\DisciplinaryCaseController;
use App\Http\Controllers\Disciplinary\DisciplinaryDashboardController;
use App\Livewire\Auth\ForcePasswordChange;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use App\Livewire\Disciplinary\Cases\CasesIndex;
use App\Livewire\Disciplinary\Dashboard;
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
