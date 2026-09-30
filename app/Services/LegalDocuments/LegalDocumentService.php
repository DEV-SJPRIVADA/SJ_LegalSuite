<?php

namespace App\Services\LegalDocuments;

use App\Models\LegalDocuments\LegalDocumentFile;
use App\Models\LegalDocuments\LegalDocumentFolder;
use App\Models\LegalDocuments\LegalDocumentItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LegalDocumentService
{
    public function __construct(
        private LegalDocumentActivityLogger $activityLogger,
    ) {}

    public function replaceFile(LegalDocumentItem $item, UploadedFile $upload, User $actor): LegalDocumentFile
    {
        return DB::transaction(function () use ($item, $upload, $actor) {
            $item->loadMissing('currentFile', 'folder');

            $disk = 'local';
            $dir = 'legal-documents/'.$item->folder_id.'/'.$item->id;
            $filename = Str::uuid()->toString().'.'.$upload->getClientOriginalExtension();
            $path = $upload->storeAs($dir, $filename, $disk);

            $previous = $item->currentFile;
            $wasReplace = $previous !== null;
            if ($previous) {
                $previous->deleteFromDisk();
                $previous->delete();
            }

            $file = LegalDocumentFile::query()->create([
                'item_id' => $item->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $upload->getClientOriginalName(),
                'mime' => $upload->getClientMimeType(),
                'size' => $upload->getSize() ?: 0,
                'uploaded_by_id' => $actor->id,
            ]);

            $renewAdvancedTo = $this->advanceRenewOnAfterUpload($item, $actor);

            if ($item->folder) {
                $this->activityLogger->log(
                    $item->folder,
                    $wasReplace ? 'file_replaced' : 'file_uploaded',
                    ($wasReplace ? 'Reemplazó' : 'Subió').' archivo de «'.$item->displayTitle().'»: '.$file->original_name
                    .($renewAdvancedTo ? ' · próxima renovación '.$renewAdvancedTo->format('d/m/Y') : ''),
                    $actor,
                    $item,
                    [
                        'file' => $file->original_name,
                        'renew_on' => $renewAdvancedTo?->toDateString(),
                    ],
                );
            }

            return $file->fresh() ?? $file;
        });
    }

    /**
     * Tras subir el archivo vigente: la carga cuenta como nueva expedición
     * y se programa la próxima renovación (expedición + frecuencia).
     */
    private function advanceRenewOnAfterUpload(LegalDocumentItem $item, User $actor): ?\Illuminate\Support\Carbon
    {
        $issued = now()->startOfDay();
        $beforeRenew = $item->renew_on?->format('d/m/Y') ?: '—';
        $beforeIssued = $item->issued_on?->format('d/m/Y') ?: '—';

        $next = $item->syncRenewalFromIssued(
            $issued->toDateString(),
            $item->frequency,
        );

        if ($item->folder) {
            $this->activityLogger->log(
                $item->folder,
                'renew_cycle_advanced',
                'Al cargar archivo, registró expedición '.$issued->format('d/m/Y')
                .' y programó renovación de «'.$item->displayTitle().'»'
                .($item->frequency ? ' ('.$item->frequency.')' : '')
                .': '.$beforeRenew.' → '.($next?->format('d/m/Y') ?: '—'),
                $actor,
                $item,
                [
                    'frequency' => $item->frequency,
                    'issued_before' => $beforeIssued,
                    'issued_after' => $issued->format('d/m/Y'),
                    'renew_before' => $beforeRenew,
                    'renew_after' => $next?->format('d/m/Y'),
                ],
            );
        }

        return $next;
    }

    public function addExtraRequest(
        LegalDocumentFolder $folder,
        User $actor,
        string $title,
        ?string $frequency = null,
        ?string $issuedOn = null,
        ?string $observations = null,
    ): LegalDocumentItem {
        $item = new LegalDocumentItem([
            'folder_id' => $folder->id,
            'code' => null,
            'group_title' => 'Solicitud adicional',
            'title' => trim($title),
            'issued_by' => null,
            'observations' => $observations,
            'source' => 'manual',
            'is_active' => true,
            'created_by_id' => $actor->id,
        ]);
        $item->syncRenewalFromIssued($issuedOn, $frequency);

        $this->activityLogger->log(
            $folder,
            'item_created',
            'Agregó documento «'.$item->displayTitle().'»',
            $actor,
            $item,
            [
                'frequency' => $item->frequency,
                'issued_on' => $item->issued_on?->toDateString(),
                'renew_on' => $item->renew_on?->toDateString(),
            ],
        );

        return $item;
    }

    public function discardCurrentFile(LegalDocumentItem $item): void
    {
        $file = $item->currentFile;
        if (! $file) {
            return;
        }

        $file->deleteFromDisk();
        $file->delete();
    }

    public function absolutePath(LegalDocumentFile $file): string
    {
        return Storage::disk($file->disk)->path($file->path);
    }
}
