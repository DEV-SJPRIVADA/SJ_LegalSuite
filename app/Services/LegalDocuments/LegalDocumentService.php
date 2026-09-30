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

            if ($item->folder) {
                $this->activityLogger->log(
                    $item->folder,
                    $wasReplace ? 'file_replaced' : 'file_uploaded',
                    ($wasReplace ? 'Reemplazó' : 'Subió').' archivo de «'.$item->displayTitle().'»: '.$file->original_name,
                    $actor,
                    $item,
                    ['file' => $file->original_name],
                );
            }

            return $file;
        });
    }

    public function addExtraRequest(
        LegalDocumentFolder $folder,
        User $actor,
        string $title,
        ?string $frequency = null,
        ?string $renewOn = null,
        ?string $observations = null,
    ): LegalDocumentItem {
        $item = LegalDocumentItem::query()->create([
            'folder_id' => $folder->id,
            'code' => null,
            'group_title' => 'Solicitud adicional',
            'title' => trim($title),
            'issued_by' => null,
            'issued_on' => null,
            'frequency' => $frequency,
            'renew_on' => $renewOn,
            'renew_label' => null,
            'observations' => $observations,
            'source' => 'manual',
            'is_active' => true,
            'created_by_id' => $actor->id,
        ]);

        $this->activityLogger->log(
            $folder,
            'item_created',
            'Agregó documento «'.$item->displayTitle().'»',
            $actor,
            $item,
            [
                'frequency' => $frequency,
                'renew_on' => $renewOn,
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
