<?php

namespace App\Http\Controllers\Employees;

use App\Models\Employee;
use App\Services\Employees\EmployeeBulkImportService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeTemplateDownloadController
{
    public function __invoke(EmployeeBulkImportService $importer): StreamedResponse
    {
        Gate::authorize('import', Employee::class);

        return response()->streamDownload(function () use ($importer): void {
            $tmp = tempnam(sys_get_temp_dir(), 'emp_tpl_');
            if ($tmp === false) {
                abort(500);
            }
            $path = $tmp.'.xlsx';
            rename($tmp, $path);
            try {
                $importer->writeTemplateToPath($path);
                echo file_get_contents($path);
            } finally {
                @unlink($path);
            }
        }, 'plantilla-empleados-sj.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
