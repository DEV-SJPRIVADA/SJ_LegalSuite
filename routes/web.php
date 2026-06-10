<?php

use App\Http\Controllers\Disciplinary\DisciplinaryPortalController;
use App\Http\Controllers\Disciplinary\DisciplinaryAgendaAttachmentDownloadController;
use App\Http\Controllers\Disciplinary\DisciplinaryAgendaAttachmentInlineController;
use App\Http\Controllers\Disciplinary\DisciplinaryAgendaThreadAttachmentDownloadController;
use App\Http\Controllers\Disciplinary\DisciplinaryAgendaThreadAttachmentInlineController;
use App\Http\Controllers\Disciplinary\DisciplinaryCaseController;
use App\Http\Controllers\Disciplinary\DisciplinaryCaseDocumentInlineController;
use App\Http\Controllers\Disciplinary\DisciplinaryDashboardController;
use App\Http\Controllers\Disciplinary\DisciplinaryGeoJsonController;
use App\Http\Controllers\Disciplinary\FoGj03CaseController;
use App\Http\Controllers\Disciplinary\FoGj04CaseController;
use App\Http\Controllers\Disciplinary\FoGj51InformeController;
use App\Http\Controllers\Disciplinary\InformeSubmissionEvidenceInlineController;
use App\Http\Controllers\Disciplinary\OfficialFormBlankDownloadController;
use App\Http\Controllers\Disciplinary\OfficialFormPreviewController;
use App\Http\Controllers\Employees\EmployeeSearchController;
use App\Http\Controllers\Employees\EmployeeTemplateDownloadController;
use App\Livewire\Auth\ForcePasswordChange;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use App\Livewire\Disciplinary\Cases\CasesIndex;
use App\Livewire\Disciplinary\Coordinations\Index as CoordinationsIndex;
use App\Livewire\Disciplinary\Dashboard;
use App\Livewire\Disciplinary\FormatsCatalog;
use App\Livewire\Disciplinary\InformesPendientes;
use App\Livewire\Disciplinary\Supervisor\PendingEvidenceIndex;
use App\Livewire\Employees\EmployeesIndex;
use App\Livewire\Home;
use App\Livewire\Settings\TerritoryImport;
use App\Livewire\Users\OrganizationCatalog;
use App\Livewire\Users\UserDetail;
use App\Livewire\Users\UsersIndex;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::view('/', 'welcome');

Route::middleware(['auth'])->group(function () {
    Route::get('password/first-login', ForcePasswordChange::class)->name('password.force-change');
});

Route::middleware(['auth', 'must-change-password'])->group(function () {
    Route::view('profile', 'profile')->name('profile');

    Route::get('profile/signature', function () {
        $user = auth()->user();
        abort_unless($user && $user->hasSignature(), 404);

        return Storage::disk($user->signature_disk ?? 'local')->response((string) $user->signature_path);
    })->name('profile.signature');
});

Route::middleware(['auth', 'must-change-password', 'verified'])->group(function () {
    Route::get('dashboard', Home::class)->name('dashboard');

    Route::get('settings/territorio', TerritoryImport::class)->name('settings.territory-import');

    Route::prefix('disciplinary')->name('disciplinary.')->group(function () {
        Route::get('/', DisciplinaryPortalController::class)->name('index');
        Route::get('dashboard', Dashboard::class)->name('dashboard');
        Route::get('map-geo/{file}', DisciplinaryGeoJsonController::class)
            ->where('file', 'gadm41_COL_1\.json|gadm41_COL_2\.json')
            ->name('map-geo');
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
        Route::get('informes-pendientes/{submission}/evidence/{index}', InformeSubmissionEvidenceInlineController::class)
            ->whereNumber('index')
            ->name('informes-pendientes.evidence');

        Route::get('cases', CasesIndex::class)->name('cases.index');
        Route::get('evidences-pending', PendingEvidenceIndex::class)->name('evidences-pending.index');
        Route::get('coordinations', CoordinationsIndex::class)->name('coordinations.index');
        Route::get('coordinations/{thread}/attachments/{attachment}/inline', DisciplinaryAgendaThreadAttachmentInlineController::class)
            ->name('coordinations.attachments.inline');
        Route::get('coordinations/{thread}/attachments/{attachment}', DisciplinaryAgendaThreadAttachmentDownloadController::class)
            ->name('coordinations.attachments.download');
        Route::get('cases/{case}/agenda-attachments/{attachment}/inline', DisciplinaryAgendaAttachmentInlineController::class)
            ->name('cases.agenda-attachment.inline');
        Route::get('cases/{case}/agenda-attachments/{attachment}', DisciplinaryAgendaAttachmentDownloadController::class)
            ->name('cases.agenda-attachment.download');
        Route::get('cases/{case}/documents/{document}/file', DisciplinaryCaseDocumentInlineController::class)
            ->name('cases.documents.file');
        Route::get('cases/{case}/fo-gj-03/pdf', [FoGj03CaseController::class, 'download'])
            ->name('cases.fo-gj-03.pdf');
        Route::post('cases/{case}/fo-gj-03/generate', [FoGj03CaseController::class, 'generate'])
            ->name('cases.fo-gj-03.generate');
        Route::get('cases/{case}/fo-gj-04/pdf', [FoGj04CaseController::class, 'download'])
            ->name('cases.fo-gj-04.pdf');
        Route::get('cases/{case}', CaseDetail::class)->name('cases.show');
    });

    Route::get('employees', EmployeesIndex::class)->name('employees.index');
    Route::get('employees/plantilla', EmployeeTemplateDownloadController::class)->name('employees.template');
    Route::get('api/employees/search', EmployeeSearchController::class)->name('api.employees.search');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', UsersIndex::class)->name('index');
        Route::get('/organizacion', OrganizationCatalog::class)->name('organization');
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
