<?php

namespace App\Http\Controllers\Disciplinary;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve un PDF temporal de Livewire (aún en «livewire-tmp») con
 * «Content-Disposition: inline» para previsualizarlo dentro de un iframe.
 *
 * Livewire entrega sus archivos temporales como «attachment», lo que provoca
 * descargas en lugar de vista previa. Este endpoint reutiliza la configuración
 * de disco/directorio de Livewire, pero fuerza «inline». La URL va firmada.
 */
class SupervisorEvidenceUploadPreviewController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 401);
        abort_unless(auth()->check(), 403);

        $filename = (string) $request->query('filename', '');

        // Defensa en profundidad: sólo un nombre de archivo plano (sin rutas) y
        // extensión PDF. La URL va firmada, así que el nombre no es manipulable.
        abort_if($filename === '' || basename($filename) !== $filename, 404);
        abort_unless(str_ends_with(strtolower($filename), '.pdf'), 404);

        $storage = FileUploadConfiguration::storage();
        $path = FileUploadConfiguration::path($filename);

        abort_unless($storage->exists($path), 404);

        return response()->stream(function () use ($storage, $path): void {
            $stream = $storage->readStream($path);
            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="evidencia.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
