<?php

namespace App\Http\Controllers\LegalDocuments;

use App\Models\LegalDocuments\LegalDocumentFile;
use App\Services\LegalDocuments\LegalDocumentService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegalDocumentFileController
{
    public function __invoke(LegalDocumentFile $file, LegalDocumentService $service): BinaryFileResponse
    {
        $file->loadMissing('item.folder');
        Gate::authorize('view', $file->item->folder);

        $path = $service->absolutePath($file);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => $file->mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$file->original_name.'"',
        ]);
    }
}
