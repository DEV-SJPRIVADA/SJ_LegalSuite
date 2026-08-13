<?php

namespace App\Jobs\Employees;

use App\Services\Employees\EmployeeBulkImportService;
use App\Support\Employees\EmployeeBulkImportStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessEmployeeBulkImportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public function __construct(public string $token) {}

    public function handle(EmployeeBulkImportService $importer): void
    {
        $importer->processAllBatches($this->token);
    }

    public function failed(?Throwable $exception): void
    {
        EmployeeBulkImportStore::markFailed(
            $this->token,
            $exception?->getMessage() ?: 'Error desconocido al importar empleados.',
        );
    }
}
