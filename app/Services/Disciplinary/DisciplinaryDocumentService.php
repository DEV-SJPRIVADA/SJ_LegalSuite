<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\DocumentType;
use App\Models\Disciplinary\DisciplinaryAction;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryDocument;
use App\Models\Disciplinary\DisciplinaryStage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DisciplinaryDocumentService
{
    /**
     * Almacena un archivo asociado a un caso (y opcionalmente a una etapa).
     */
    public function upload(
        DisciplinaryCase $case,
        UploadedFile $file,
        DocumentType $type,
        User $uploader,
        ?DisciplinaryStage $stage = null,
        ?string $notes = null,
        string $disk = 'local',
    ): DisciplinaryDocument {
        return DB::transaction(function () use ($case, $file, $type, $uploader, $stage, $notes, $disk) {
            $relativeDir = "disciplinary/{$case->id}";
            $path = Storage::disk($disk)->putFile($relativeDir, $file);

            $checksum = hash_file('sha256', $file->getRealPath());

            $doc = DisciplinaryDocument::create([
                'disciplinary_case_id' => $case->id,
                'disciplinary_stage_id' => $stage?->id,
                'uploaded_by' => $uploader->id,
                'document_type' => $type,
                'form_code' => $this->formCodeFor($type),
                'original_name' => $file->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'checksum_sha256' => $checksum,
                'notes' => $notes,
            ]);

            DisciplinaryAction::create([
                'disciplinary_case_id' => $case->id,
                'disciplinary_stage_id' => $stage?->id,
                'user_id' => $uploader->id,
                'action_type' => ActionType::DOCUMENTO_CARGADO,
                'description' => "Documento cargado: {$file->getClientOriginalName()}",
                'metadata' => [
                    'document_id' => $doc->id,
                    'document_type' => $type->value,
                ],
                'performed_at' => now(),
            ]);

            return $doc;
        });
    }

    public function delete(DisciplinaryDocument $document, User $actor, ?string $reason = null): void
    {
        DB::transaction(function () use ($document, $actor, $reason) {
            DisciplinaryAction::create([
                'disciplinary_case_id' => $document->disciplinary_case_id,
                'disciplinary_stage_id' => $document->disciplinary_stage_id,
                'user_id' => $actor->id,
                'action_type' => ActionType::DOCUMENTO_ELIMINADO,
                'description' => $reason ?? "Documento eliminado: {$document->original_name}",
                'metadata' => ['document_id' => $document->id],
                'performed_at' => now(),
            ]);

            $document->delete();
        });
    }

    private function formCodeFor(DocumentType $type): ?string
    {
        return match ($type) {
            DocumentType::INFORME => 'FO-GJ-51',
            DocumentType::CITACION => 'FO-GJ-03',
            DocumentType::REPROGRAMACION => 'FO-GJ-54',
            DocumentType::ACTA_DILIGENCIA => 'FO-GJ-42',
            default => null,
        };
    }
}
