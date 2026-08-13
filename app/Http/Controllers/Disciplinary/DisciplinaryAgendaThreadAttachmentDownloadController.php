<?php

namespace App\Http\Controllers\Disciplinary;

use App\Enums\PlatformLevel;
use App\Models\Disciplinary\DisciplinaryAgendaAttachment;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisciplinaryAgendaThreadAttachmentDownloadController
{
    public function __invoke(DisciplinaryAgendaThread $thread, DisciplinaryAgendaAttachment $attachment): BinaryFileResponse|StreamedResponse
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        $message = $attachment->message()->with('thread')->firstOrFail();
        if ((int) $message->thread->id !== (int) $thread->getKey()) {
            abort(404);
        }

        $case = $thread->case()->first();
        if (! $case) {
            abort(404);
        }

        $canView = $user->hasPlatformLevel(PlatformLevel::Nivel3)
            || (int) $case->assigned_lawyer_id === (int) $user->id
            || $user->hasPlatformLevel(PlatformLevel::Nivel1)
            || $user->hasPermissionTo('disciplinary.assign');

        if (! $canView) {
            abort(403);
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel3) && ! $thread->isOpen()) {
            abort(403);
        }

        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404);
        }

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
        );
    }
}
