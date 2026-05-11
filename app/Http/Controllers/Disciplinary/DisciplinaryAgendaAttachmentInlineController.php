<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryAgendaAttachment;
use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisciplinaryAgendaAttachmentInlineController
{
    public function __invoke(DisciplinaryCase $case, DisciplinaryAgendaAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $case);

        $message = $attachment->message()->with('thread')->firstOrFail();
        if ((int) $message->thread->disciplinary_case_id !== (int) $case->getKey()) {
            abort(404);
        }

        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404);
        }

        $mime = $attachment->inferredImageMimeType();
        if ($mime === null) {
            abort(404);
        }

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $mime],
            'inline',
        );
    }
}
