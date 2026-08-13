<?php

namespace App\Jobs\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryInformeSubmissionService;
use App\Services\Disciplinary\FoGj51PdfBuilder;
use App\Services\Employees\EmployeeResolver;
use App\Support\Pdf\FoGj51PdfQueueStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Throwable;

class ProcessFoGj51PdfJob implements ShouldQueue
{
    use Queueable;

    /** Cola dedicada: el cron la vacía antes que `default` (notificaciones). */
    public const QUEUE = 'pdf';

    public int $timeout = 180;

    public function __construct(public string $token)
    {
        $this->onQueue(self::QUEUE);
    }

    public function handle(
        FoGj51PdfBuilder $builder,
        DisciplinaryInformeSubmissionService $submissions,
        EmployeeResolver $employees,
    ): void {
        FoGj51PdfQueueStore::updateMeta($this->token, [
            'status' => FoGj51PdfQueueStore::STATUS_PROCESSING,
        ]);

        $meta = FoGj51PdfQueueStore::meta($this->token);
        $payload = FoGj51PdfQueueStore::payload($this->token);

        if ($meta === null || $payload === null) {
            throw new \RuntimeException('Datos de cola PDF incompletos.');
        }

        $user = User::query()->findOrFail((int) $meta['user_id']);
        $formFields = (array) ($payload['form_fields'] ?? []);
        $employee = $employees->resolveById(
            isset($formFields['fo51_employee_id']) ? (int) $formFields['fo51_employee_id'] : null,
            (string) ($formFields['fo51_worker_document'] ?? ''),
        );

        $binary = $builder->buildBinary($formFields, $employee);
        $outputPath = FoGj51PdfQueueStore::outputPath($this->token);

        if (file_put_contents($outputPath, $binary) === false) {
            throw new \RuntimeException('No se pudo escribir el PDF generado.');
        }

        $intent = (string) ($meta['intent'] ?? 'pdf');

        if ($intent === 'pdf') {
            FoGj51PdfQueueStore::updateMeta($this->token, [
                'status' => FoGj51PdfQueueStore::STATUS_READY,
            ]);

            return;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'fo51_queue_');
        if ($tempPath === false) {
            throw new \RuntimeException('No se pudo preparar el archivo temporal del informe.');
        }

        try {
            file_put_contents($tempPath, $binary);

            $uploaded = new UploadedFile(
                $tempPath,
                'FO-GJ-51-informe-disciplinario.pdf',
                'application/pdf',
                UPLOAD_ERR_OK,
                true,
            );

            $evidenceFiles = $this->evidenceUploads($this->token, (array) ($payload['evidence_files'] ?? []));

            $submissions->storePending(
                $uploaded,
                $user,
                $employee->id,
                (int) ($payload['assigned_reviewer_id'] ?? 0),
                $formFields,
                isset($formFields['fo51_observations'])
                    ? mb_substr((string) $formFields['fo51_observations'], 0, 5000)
                    : null,
                $evidenceFiles,
            );
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }

        $redirectRoute = Gate::forUser($user)->allows('viewAny', DisciplinaryCase::class)
            ? 'disciplinary.cases.index'
            : 'disciplinary.evidences-pending.index';

        FoGj51PdfQueueStore::updateMeta($this->token, [
            'status' => FoGj51PdfQueueStore::STATUS_SUBMITTED,
            'redirect_route' => $redirectRoute,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        FoGj51PdfQueueStore::updateMeta($this->token, [
            'status' => FoGj51PdfQueueStore::STATUS_FAILED,
            'error' => $exception?->getMessage() ?: 'Error desconocido al generar el PDF.',
        ]);
    }

    /**
     * @param  list<string>  $relativePaths
     * @return list<UploadedFile>
     */
    private function evidenceUploads(string $token, array $relativePaths): array
    {
        $base = FoGj51PdfQueueStore::directoryFor($token);
        $uploads = [];

        foreach ($relativePaths as $relativePath) {
            $absolute = $base.'/'.ltrim($relativePath, '/');
            if (! is_readable($absolute)) {
                continue;
            }

            $uploads[] = new UploadedFile(
                $absolute,
                basename($absolute),
                mime_content_type($absolute) ?: null,
                UPLOAD_ERR_OK,
                true,
            );
        }

        return $uploads;
    }
}
