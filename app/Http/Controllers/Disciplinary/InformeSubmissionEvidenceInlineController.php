<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\InformeSubmission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeSubmissionEvidenceInlineController
{
    public function __invoke(InformeSubmission $submission, int $index): StreamedResponse
    {
        Gate::authorize('review', $submission);

        $paths = $submission->evidence_paths ?? [];
        if (! isset($paths[$index])) {
            abort(404);
        }

        $disk = $submission->storage_disk ?: 'local';
        $path = $paths[$index];

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($disk)->response($path, null, [], 'inline');
    }
}
