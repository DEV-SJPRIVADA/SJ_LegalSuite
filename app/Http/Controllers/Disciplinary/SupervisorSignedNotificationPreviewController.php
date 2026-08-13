<?php

namespace App\Http\Controllers\Disciplinary;

use App\Enums\PlatformLevel;
use App\Http\Controllers\Controller;
use App\Support\Disciplinary\SupervisorSignedNotificationPreviewStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupervisorSignedNotificationPreviewController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        SupervisorSignedNotificationPreviewStore $store,
    ): StreamedResponse {
        abort_unless(auth()->check() && auth()->user()->hasPlatformLevel(PlatformLevel::Nivel7), 403);

        $meta = $store->resolve($token, (int) auth()->id());
        abort_if($meta === null, 404);

        $path = (string) $meta['path'];
        $filename = (string) $meta['filename'];
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response()->stream(function () use ($path): void {
            $stream = Storage::disk('local')->readStream($path);
            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
