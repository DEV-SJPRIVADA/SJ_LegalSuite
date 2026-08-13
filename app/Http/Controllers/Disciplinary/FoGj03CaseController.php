<?php

namespace App\Http\Controllers\Disciplinary;

use App\Jobs\Disciplinary\ProcessFoGj03PdfJob;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\FoGj03CitationService;
use App\Support\Pdf\FoGj03PdfQueueStore;
use App\Support\Pdf\LetterPdfDriver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FoGj03CaseController
{
    public function download(DisciplinaryCase $case, FoGj03CitationService $service, Request $request): Response|RedirectResponse
    {
        Gate::authorize('previewFoGj03', $case);

        if ($this->shouldQueuePdf()) {
            return $this->dispatchQueuedPreview($case, $request);
        }

        return $this->respondPdfBinary($case, $service, $request->boolean('inline'));
    }

    public function generate(DisciplinaryCase $case, FoGj03CitationService $service): RedirectResponse
    {
        Gate::authorize('generateFoGj03', $case);

        if ($this->shouldQueuePdf()) {
            $token = FoGj03PdfQueueStore::create('generate', (int) auth()->id(), (int) $case->id);
            ProcessFoGj03PdfJob::dispatch($token);

            return redirect()->route('disciplinary.cases.fo-gj-03.pdf-queue', [
                'case' => $case,
                'token' => $token,
            ]);
        }

        $service->generateAndStore($case->fresh(), auth()->user());

        return redirect()
            ->route('disciplinary.cases.show', $case)
            ->with('success', 'FO-GJ-03 generado y guardado en el expediente.');
    }

    public function pdfQueueWait(DisciplinaryCase $case, string $token, Request $request): View
    {
        $this->assertQueueAccess($case, $token, $request);

        $meta = FoGj03PdfQueueStore::meta($token);
        abort_if($meta === null, 404);

        return view('disciplinary.forms.fo-gj-03-pdf-queue-wait', [
            'case' => $case,
            'token' => $token,
            'intent' => (string) ($meta['intent'] ?? 'preview'),
            'inline' => (bool) ($meta['inline'] ?? $request->boolean('inline')),
            'statusUrl' => route('disciplinary.cases.fo-gj-03.pdf-queue.status', ['case' => $case, 'token' => $token]),
            'downloadUrl' => route('disciplinary.cases.fo-gj-03.pdf-queue.download', [
                'case' => $case,
                'token' => $token,
                'inline' => (bool) ($meta['inline'] ?? true) ? 1 : 0,
            ]),
            'caseUrl' => route('disciplinary.cases.show', $case),
        ]);
    }

    public function pdfQueueStatus(DisciplinaryCase $case, string $token, Request $request): JsonResponse
    {
        $this->assertQueueAccess($case, $token, $request);

        $meta = FoGj03PdfQueueStore::meta($token);
        abort_if($meta === null, 404);

        $status = (string) ($meta['status'] ?? FoGj03PdfQueueStore::STATUS_PENDING);
        $intent = (string) ($meta['intent'] ?? 'preview');

        return response()->json([
            'status' => $status,
            'error' => $meta['error'] ?? null,
            'intent' => $intent,
            'redirect_url' => ($status === FoGj03PdfQueueStore::STATUS_READY && $intent === 'generate')
                ? route('disciplinary.cases.fo-gj-03.pdf-queue.complete', ['case' => $case, 'token' => $token])
                : null,
        ]);
    }

    public function pdfQueueComplete(DisciplinaryCase $case, string $token, Request $request): RedirectResponse
    {
        $this->assertQueueAccess($case, $token, $request);

        $meta = FoGj03PdfQueueStore::meta($token);
        abort_if($meta === null
            || ($meta['status'] ?? '') !== FoGj03PdfQueueStore::STATUS_READY
            || ($meta['intent'] ?? '') !== 'generate', 404);

        return redirect()
            ->route('disciplinary.cases.show', $case)
            ->with('success', 'FO-GJ-03 generado y guardado en el expediente.');
    }

    public function pdfQueueDownload(DisciplinaryCase $case, string $token, Request $request): Response
    {
        $this->assertQueueAccess($case, $token, $request);

        $meta = FoGj03PdfQueueStore::meta($token);
        abort_if($meta === null || ($meta['status'] ?? '') !== FoGj03PdfQueueStore::STATUS_READY, 404);
        abort_if(($meta['intent'] ?? '') === 'generate', 404);

        $path = FoGj03PdfQueueStore::outputPath($token);
        abort_unless(is_readable($path), 404);

        $binary = (string) file_get_contents($path);
        $filename = 'FO-GJ-03-'.str_replace(['/', '\\', '"'], '-', $case->case_number).'.pdf';
        $inline = $request->boolean('inline', (bool) ($meta['inline'] ?? true));

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
        ]);
    }

    private function shouldQueuePdf(): bool
    {
        return LetterPdfDriver::shouldUseQueue();
    }

    private function dispatchQueuedPreview(DisciplinaryCase $case, Request $request): RedirectResponse
    {
        $inline = $request->boolean('inline');
        $token = FoGj03PdfQueueStore::create('preview', (int) $request->user()->id, (int) $case->id, [
            'inline' => $inline,
        ]);
        ProcessFoGj03PdfJob::dispatch($token);

        return redirect()->route('disciplinary.cases.fo-gj-03.pdf-queue', [
            'case' => $case,
            'token' => $token,
            'inline' => $inline ? 1 : null,
        ]);
    }

    private function respondPdfBinary(DisciplinaryCase $case, FoGj03CitationService $service, bool $inline): Response
    {
        $binary = $service->downloadPdf($case, auth()->user());
        $filename = 'FO-GJ-03-'.str_replace(['/', '\\', '"'], '-', $case->case_number).'.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
        ]);
    }

    private function assertQueueAccess(DisciplinaryCase $case, string $token, Request $request): void
    {
        abort_unless(FoGj03PdfQueueStore::belongsToUser($token, (int) $request->user()->id), 404);

        $meta = FoGj03PdfQueueStore::meta($token);
        abort_if($meta === null || (int) ($meta['case_id'] ?? 0) !== (int) $case->id, 404);
    }
}
