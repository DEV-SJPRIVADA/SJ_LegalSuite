<?php

namespace App\Http\Controllers\Disciplinary;

use App\Enums\Disciplinary\InformeSubmissionStatus;
use App\Http\Requests\Disciplinary\FoGj51ProcessRequest;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\InformeSubmission;
use App\Services\Disciplinary\DisciplinaryInformeSubmissionService;
use App\Services\Personnel\PersonnelFromInformIdentity;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FoGj51InformeController
{
    public function show(Request $request): RedirectResponse|View
    {
        if (! Gate::allows('create', DisciplinaryCase::class)
            && ! Gate::allows('generateFo51Inform', DisciplinaryCase::class)) {
            abort(403);
        }

        if ($request->boolean('vista_completa')) {
            return view('disciplinary.forms.fo-gj-51-fill', [
                'prefillWorkerName' => $request->string('nombre')->trim()->toString() ?: null,
                'prefillWorkerDocument' => $request->string('cedula')->trim()->toString() ?: null,
                'openPdfUploadModal' => $request->boolean('cargar_pdf'),
            ]);
        }

        $query = array_filter([
            'informe_modal' => 1,
            'cargar_pdf' => $request->boolean('cargar_pdf') ? 1 : null,
            'nombre' => $request->string('nombre')->trim()->toString() ?: null,
            'cedula' => $request->string('cedula')->trim()->toString() ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        return Redirect::route('disciplinary.cases.index', $query);
    }

    public function process(FoGj51ProcessRequest $request): Response|RedirectResponse
    {
        return match ($request->validated('fo51_action')) {
            'pdf' => $this->respondPdfDownload($request),
            'enviar' => $this->submitToRevisionQueue($request),
            'cargar' => $this->uploadToRevisionQueue($request),
        };
    }

    public function pendingPdf(Request $request, InformeSubmission $submission)
    {
        Gate::authorize('view', $submission);

        if ($submission->status !== InformeSubmissionStatus::PENDIENTE_REVISION
            || $submission->storage_path === '') {
            abort(404);
        }

        $disk = $submission->storage_disk;
        $path = $submission->storage_path;

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        if ($request->boolean('inline')) {
            $absolute = Storage::disk($disk)->path($path);
            if (! is_readable($absolute)) {
                abort(404);
            }

            $filename = basename((string) ($submission->original_filename ?: 'FO-GJ-51-informe.pdf'));
            $filename = str_replace(["\r", "\n", '"'], '', $filename) ?: 'FO-GJ-51-informe.pdf';

            return response()->file($absolute, [
                'Content-Type' => $submission->mime_type ?? 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        return Storage::disk($disk)->download(
            $path,
            $submission->original_filename ?? 'FO-GJ-51-informe.pdf',
            ['Content-Type' => $submission->mime_type ?? 'application/pdf']
        );
    }

    private function submitToRevisionQueue(FoGj51ProcessRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $resolver = app(PersonnelFromInformIdentity::class);
        try {
            $personnel = $resolver->resolve(
                (string) ($validated['fo51_worker_name'] ?? ''),
                (string) ($validated['fo51_worker_document'] ?? ''),
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('disciplinary.cases.index', ['informe_modal' => 1])
                ->withInput()
                ->withErrors(['fo51_worker_document' => $e->getMessage()]);
        }

        $v = $this->onlyFo51FormFields($validated);
        $binary = $this->buildFilledPdfBinary($v);

        $path = tempnam(sys_get_temp_dir(), 'fo51_');
        if ($path === false) {
            abort(500, 'No se pudo preparar el archivo temporal del informe.');
        }

        file_put_contents($path, $binary);

        try {
            $uploaded = new UploadedFile(
                $path,
                'FO-GJ-51-informe-disciplinario.pdf',
                'application/pdf',
                UPLOAD_ERR_OK,
                true
            );
            app(DisciplinaryInformeSubmissionService::class)->storePending(
                $uploaded,
                $request->user(),
                $personnel->id,
                $v,
                isset($v['fo51_observations']) ? mb_substr((string) $v['fo51_observations'], 0, 5000) : null,
                collect($request->file('evidence_images', []))
                    ->filter(fn ($f) => $f instanceof UploadedFile && $f->isValid())
                    ->take(10)
                    ->values()
                    ->all(),
            );
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        return redirect()
            ->route('disciplinary.cases.index')
            ->with('success', 'Su informe quedó en cola para revisión de dirección. Cuando sea autorizado se creará el expediente.');
    }

    private function uploadToRevisionQueue(FoGj51ProcessRequest $request): RedirectResponse
    {
        $file = $request->file('informe_file');
        if (! $file instanceof UploadedFile) {
            abort(400);
        }

        $validated = $request->validated();
        $resolver = app(PersonnelFromInformIdentity::class);
        try {
            $personnel = $resolver->resolve(
                (string) ($validated['informe_worker_name'] ?? ''),
                (string) ($validated['informe_worker_document'] ?? ''),
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('disciplinary.cases.index', ['informe_modal' => 1, 'cargar_pdf' => 1])
                ->withInput()
                ->withErrors(['informe_worker_document' => $e->getMessage()]);
        }

        $v = array_merge($this->onlyFo51FormFields($validated), [
            'informe_declared_worker_name' => trim((string) ($validated['informe_worker_name'] ?? '')),
            'informe_declared_worker_document' => $resolver->normalizeDocument((string) ($validated['informe_worker_document'] ?? '')),
        ]);

        app(DisciplinaryInformeSubmissionService::class)->storePending(
            $file,
            $request->user(),
            $personnel->id,
            $v,
            isset($validated['informe_worker_name'])
                ? mb_substr(trim((string) $validated['informe_worker_name']), 0, 120)
                : null,
        );

        return redirect()
            ->route('disciplinary.cases.index')
            ->with('success', 'El PDF se envió a revisión de dirección. Cuando sea autorizado se creará el expediente.');
    }

    private function respondPdfDownload(FoGj51ProcessRequest $request): Response
    {
        $v = $this->onlyFo51FormFields($request->validated());
        $binary = $this->buildFilledPdfBinary($v);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="FO-GJ-51-informe-disciplinario.pdf"',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function onlyFo51FormFields(array $validated): array
    {
        return Arr::except($validated, [
            'fo51_action',
            'informe_file',
            'informe_worker_name',
            'informe_worker_document',
        ]);
    }

    /**
     * @param  array<string, mixed>  $v
     */
    private function buildFilledPdfBinary(array $v): string
    {
        $embeddedLogo = EmbeddedPublicAsset::disciplinaryLogoDataUri();

        return HtmlLetterPdfGenerator::fromView('disciplinary.forms.fo-gj-51-filled-download', [
            'embeddedLogoSrc' => $embeddedLogo,
            'workerName' => $v['fo51_worker_name'] ?? '',
            'workerDocument' => $v['fo51_worker_document'] ?? '',
            'city' => $v['fo51_city'] ?? '',
            'shift' => $v['fo51_shift'] ?? '',
            'position' => $v['fo51_position'] ?? '',
            'faultOtherDetail' => $v['fo51_fault_other_detail'] ?? '',
            'observations' => $v['fo51_observations'] ?? '',
            'preparerName' => $v['fo51_preparer_name'] ?? '',
            'preparerRole' => $v['fo51_preparer_role'] ?? '',
            'preparerSignature' => $v['fo51_preparer_signature'] ?? '',
            'reportDay' => $v['fo51_report_dd'] ?? null,
            'reportMonth' => $v['fo51_report_mm'] ?? null,
            'reportYear' => $v['fo51_report_yyyy'] ?? null,
            'faultLeftChecked' => $v['fo51_fault_left'] ?? [],
            'faultRightChecked' => $v['fo51_fault_right'] ?? [],
            'faultOtherChecked' => (bool) ($v['fo51_fault_other_chk'] ?? false),
            'jurPd' => $v['fo51_jur_pd'] ?? '',
            'entregaGh' => $v['fo51_entrega_gh'] ?? '',
            'jurDd' => $v['fo51_jur_dd'] ?? '',
            'jurMm' => $v['fo51_jur_mm'] ?? '',
            'jurYyyy' => $v['fo51_jur_yyyy'] ?? '',
        ]);
    }
}
