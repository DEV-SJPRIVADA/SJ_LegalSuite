<?php

namespace App\Jobs\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Disciplinary\FoGj03CitationService;
use App\Support\Pdf\FoGj03PdfQueueStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Gate;
use Throwable;

class ProcessFoGj03PdfJob implements ShouldQueue
{
    use Queueable;

    public const QUEUE = ProcessFoGj51PdfJob::QUEUE;

    public int $timeout = 180;

    public function __construct(public string $token)
    {
        $this->onQueue(self::QUEUE);
    }

    public function handle(FoGj03CitationService $foGj03): void
    {
        FoGj03PdfQueueStore::updateMeta($this->token, [
            'status' => FoGj03PdfQueueStore::STATUS_PROCESSING,
        ]);

        $meta = FoGj03PdfQueueStore::meta($this->token);
        if ($meta === null) {
            throw new \RuntimeException('Datos de cola FO-GJ-03 incompletos.');
        }

        $user = User::query()->findOrFail((int) $meta['user_id']);
        $case = DisciplinaryCase::query()->findOrFail((int) $meta['case_id']);
        $intent = (string) ($meta['intent'] ?? 'preview');

        try {
            if ($intent === 'generate') {
                Gate::forUser($user)->authorize('generateFoGj03', $case);
                $foGj03->generateAndStore($case->fresh(), $user);
            } else {
                Gate::forUser($user)->authorize('previewFoGj03', $case);
                $binary = $foGj03->downloadPdf($case->fresh(), $user);
                if (file_put_contents(FoGj03PdfQueueStore::outputPath($this->token), $binary) === false) {
                    throw new \RuntimeException('No se pudo escribir el PDF FO-GJ-03.');
                }
            }

            FoGj03PdfQueueStore::updateMeta($this->token, [
                'status' => FoGj03PdfQueueStore::STATUS_READY,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            FoGj03PdfQueueStore::updateMeta($this->token, [
                'status' => FoGj03PdfQueueStore::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        FoGj03PdfQueueStore::updateMeta($this->token, [
            'status' => FoGj03PdfQueueStore::STATUS_FAILED,
            'error' => $exception?->getMessage() ?: 'Error desconocido al generar FO-GJ-03.',
        ]);
    }
}
