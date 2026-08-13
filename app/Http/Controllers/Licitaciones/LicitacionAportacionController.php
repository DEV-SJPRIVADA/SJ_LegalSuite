<?php

namespace App\Http\Controllers\Licitaciones;

use App\Enums\Licitaciones\DocumentRevisionStatus;
use App\Http\Controllers\Controller;
use App\Models\Licitaciones\LicitacionAdjunto;
use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use App\Services\Licitaciones\LicitacionDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LicitacionAportacionController extends Controller
{
    private const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx,zip,jpg,jpeg,png';

    public function show(string $token): View
    {
        $invitado = $this->findInvitado($token);
        $invitado->update(['ultimo_acceso_at' => now()]);

        return view('licitaciones.aportacion', [
            'invitado' => $invitado,
            'solicitud' => $invitado->solicitud,
            'enviado' => (bool) session('aportacion_enviada'),
            'archivoEnviado' => session('aportacion_archivo'),
        ]);
    }

    public function store(Request $request, string $token, LicitacionDocumentService $documents): RedirectResponse
    {
        $invitado = $this->findInvitado($token);

        try {
            $validated = $request->validate(
                [
                    'archivo' => ['required', 'file', 'max:51200', 'mimes:'.self::ALLOWED_MIMES],
                    'reemplaza_adjunto_id' => ['nullable', 'integer'],
                ],
                [
                    'archivo.required' => 'Seleccione un archivo para enviar.',
                    'archivo.file' => 'El archivo no es válido.',
                    'archivo.max' => 'El archivo no puede superar los 50 MB.',
                    'archivo.mimes' => 'Solo se permiten PDF, Word, Excel, ZIP o imágenes (JPG/PNG).',
                ],
            );

            $reemplaza = null;
            if (! empty($validated['reemplaza_adjunto_id'])) {
                $reemplaza = LicitacionAdjunto::query()
                    ->where('id', $validated['reemplaza_adjunto_id'])
                    ->where('invitado_id', $invitado->id)
                    ->where('revision_estado', DocumentRevisionStatus::Rechazado->value)
                    ->first();

                if (! $reemplaza) {
                    throw ValidationException::withMessages([
                        'archivo' => 'No se encontró el documento a corregir.',
                    ]);
                }
            }

            $adjunto = $documents->uploadFromInvitado(
                $invitado,
                $request->file('archivo'),
                $reemplaza,
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['archivo' => 'No se pudo enviar el documento. Intente de nuevo.']);
        }

        return redirect()
            ->route('licitaciones.aportacion', $token)
            ->with('aportacion_enviada', true)
            ->with('aportacion_archivo', $adjunto->nombre_archivo);
    }

    private function findInvitado(string $token): LicitacionSolicitudInvitado
    {
        return LicitacionSolicitudInvitado::query()
            ->where('token', $token)
            ->with([
                'solicitud.licitacion',
                'adjuntos' => fn ($q) => $q->orderByDesc('created_at'),
            ])
            ->firstOrFail();
    }
}
