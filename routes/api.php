<?php

use App\Http\Controllers\Api\Integration\EmployeeIngestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
|
| Solo endpoints de integracion maquina-a-maquina. No usan auth web ni CSRF.
|
*/

Route::prefix('integraciones')->middleware('throttle:60,1')->group(function (): void {
    Route::get('ping', [EmployeeIngestController::class, 'ping'])->name('api.integraciones.ping');

    Route::post('empleados', [EmployeeIngestController::class, 'store'])
        ->middleware('integration.signature')
        ->name('api.integraciones.empleados');
});
