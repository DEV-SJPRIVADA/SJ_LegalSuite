<?php

use App\Http\Controllers\Disciplinary\DisciplinaryCaseController;
use App\Http\Controllers\Disciplinary\DisciplinaryDashboardController;
use App\Livewire\Auth\ForcePasswordChange;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use App\Livewire\Disciplinary\Cases\CasesIndex;
use App\Livewire\Disciplinary\Dashboard;
use App\Livewire\Disciplinary\FormatsCatalog;
use App\Livewire\Home;
use App\Livewire\Users\UserDetail;
use App\Livewire\Users\UsersIndex;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Disciplinary\OfficialFormsCatalog;
use Illuminate\Support\Facades\Gate;
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
        Route::get('formats/descarga-en-blanco/{code}', function (string $code) {
            Gate::authorize('viewOfficialForms', DisciplinaryCase::class);

            $normalized = strtoupper($code);

            if ($normalized === 'FO-GJ-51') {
                return response()->view('disciplinary.forms.fo-gj-51-blank-download', [], 200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="FO-GJ-51-informe-disciplinario-en-blanco.html"',
                ]);
            }

            foreach (OfficialFormsCatalog::all() as $row) {
                if (($row['code'] ?? '') === $normalized && ! empty($row['pdf'])) {
                    $path = public_path('formatos/disciplinarios/'.$row['pdf']);
                    if (! is_file($path)) {
                        abort(404);
                    }

                    return response()->download($path, $row['pdf']);
                }
            }

            abort(404);
        })->where('code', '[A-Za-z0-9\-]+')->name('formats.download-blank');

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
