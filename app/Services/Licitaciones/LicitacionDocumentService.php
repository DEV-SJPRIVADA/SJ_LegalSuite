<?php

namespace App\Services\Licitaciones;

use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionAdjunto;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LicitacionDocumentService
{
    public function uploadForSolicitud(LicitacionSolicitud $solicitud, UploadedFile $file, User $actor): LicitacionAdjunto
    {
        return DB::transaction(function () use ($solicitud, $file, $actor) {
            $path = $file->store('licitaciones/solicitudes/'.$solicitud->id, 'local');

            return LicitacionAdjunto::create([
                'solicitud_id' => $solicitud->id,
                'licitacion_id' => $solicitud->licitacion_id,
                'user_id' => $actor->id,
                'nombre_archivo' => $file->getClientOriginalName(),
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'tamaño_archivo' => $file->getSize(),
            ]);
        });
    }

    public function uploadForLicitacion(Licitacion $licitacion, UploadedFile $file, User $actor): LicitacionAdjunto
    {
        return DB::transaction(function () use ($licitacion, $file, $actor) {
            $path = $file->store('licitaciones/'.$licitacion->id, 'local');

            return LicitacionAdjunto::create([
                'licitacion_id' => $licitacion->id,
                'user_id' => $actor->id,
                'nombre_archivo' => $file->getClientOriginalName(),
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'tamaño_archivo' => $file->getSize(),
            ]);
        });
    }

    public function delete(LicitacionAdjunto $adjunto): void
    {
        if ($adjunto->path && Storage::disk($adjunto->disk)->exists($adjunto->path)) {
            Storage::disk($adjunto->disk)->delete($adjunto->path);
        }

        $adjunto->delete();
    }
}
