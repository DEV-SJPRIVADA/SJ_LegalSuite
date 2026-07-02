<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisciplinaryCaseDocumentInlineController
{
    public function __invoke(Request $request, DisciplinaryCase $case, DisciplinaryDocument $document): BinaryFileResponse|StreamedResponse
    {
        Gate::authorize('view', $case);

        if ((int) $document->disciplinary_case_id !== (int) $case->getKey()) {
            abort(404);
        }

        if ($document->path === '' || $document->trashed()) {
            abort(404);
        }

        $disk = $document->disk ?: 'local';

        if (! Storage::disk($disk)->exists($document->path)) {
            abort(404);
        }

        $filename = str_replace(["\r", "\n", '"'], '', $document->displayName()) ?: 'documento';

        if ($request->boolean('download')) {
            return Storage::disk($disk)->download(
                $document->path,
                $filename,
                ['Content-Type' => $document->mime_type ?? 'application/octet-stream']
            );
        }

        $absolute = Storage::disk($disk)->path($document->path);
        if (! is_readable($absolute)) {
            abort(404);
        }

        return response()->file($absolute, [
            'Content-Type' => $document->mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
