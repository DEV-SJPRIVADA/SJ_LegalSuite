<?php

namespace App\Services\Disciplinary;

use App\Models\ColombianMunicipality;
use App\Models\Employee;
use App\Services\Employees\EmployeeResolver;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;

final class FoGj51PdfBuilder
{
    /**
     * @param  array<string, mixed>  $formFields
     */
    public function buildBinary(array $formFields, ?Employee $employee = null): string
    {
        $embeddedLogo = EmbeddedPublicAsset::disciplinaryLogoDataUri();

        $code = isset($formFields['fo51_municipality_code'])
            ? trim((string) $formFields['fo51_municipality_code'])
            : '';
        $cityLabel = $code !== ''
            ? (string) (ColombianMunicipality::query()->where('municipality_code', $code)->value('municipality_name') ?? '')
            : '';

        $workerCargo = $this->resolveWorkerCargo($formFields, $employee);

        return HtmlLetterPdfGenerator::fromView('disciplinary.forms.fo-gj-51-filled-download', [
            'embeddedLogoSrc' => $embeddedLogo,
            'workerName' => $formFields['fo51_worker_name'] ?? '',
            'workerDocument' => $formFields['fo51_worker_document'] ?? '',
            'workerCargo' => $workerCargo,
            'city' => $cityLabel,
            'shift' => $formFields['fo51_shift'] ?? '',
            'position' => $formFields['fo51_position'] ?? '',
            'faultOtherDetail' => $formFields['fo51_fault_other_detail'] ?? '',
            'observations' => $formFields['fo51_observations'] ?? '',
            'preparerName' => $formFields['fo51_preparer_name'] ?? '',
            'preparerRole' => $formFields['fo51_preparer_role'] ?? '',
            'preparerSignature' => $formFields['fo51_preparer_signature'] ?? '',
            'reportDay' => $formFields['fo51_report_dd'] ?? null,
            'reportMonth' => $formFields['fo51_report_mm'] ?? null,
            'reportYear' => $formFields['fo51_report_yyyy'] ?? null,
            'faultLeftChecked' => $formFields['fo51_fault_left'] ?? [],
            'faultRightChecked' => $formFields['fo51_fault_right'] ?? [],
            'faultOtherChecked' => (bool) ($formFields['fo51_fault_other_chk'] ?? false),
            'jurPd' => $formFields['fo51_jur_pd'] ?? '',
            'entregaGh' => $formFields['fo51_entrega_gh'] ?? '',
            'jurDd' => $formFields['fo51_jur_dd'] ?? '',
            'jurMm' => $formFields['fo51_jur_mm'] ?? '',
            'jurYyyy' => $formFields['fo51_jur_yyyy'] ?? '',
        ]);
    }

    /**
     * @param  array<string, mixed>  $formFields
     */
    public function resolveEmployee(array $formFields): ?Employee
    {
        try {
            return app(EmployeeResolver::class)->resolveById(
                isset($formFields['fo51_employee_id']) ? (int) $formFields['fo51_employee_id'] : null,
                (string) ($formFields['fo51_worker_document'] ?? ''),
            );
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $formFields
     */
    private function resolveWorkerCargo(array $formFields, ?Employee $employee): string
    {
        if ($employee !== null) {
            return trim((string) ($employee->job_title ?? ''));
        }

        $employeeId = isset($formFields['fo51_employee_id']) ? (int) $formFields['fo51_employee_id'] : 0;
        if ($employeeId > 0) {
            return trim((string) (Employee::query()->whereKey($employeeId)->value('job_title') ?? ''));
        }

        $document = trim((string) ($formFields['fo51_worker_document'] ?? ''));
        if ($document === '') {
            return '';
        }

        try {
            return trim((string) (app(EmployeeResolver::class)->resolveByDocument($document)->job_title ?? ''));
        } catch (\InvalidArgumentException) {
            return '';
        }
    }
}
