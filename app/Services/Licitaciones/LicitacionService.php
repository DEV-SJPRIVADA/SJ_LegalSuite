<?php

namespace App\Services\Licitaciones;

use App\Enums\Licitaciones\RequestType;
use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LicitacionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Licitacion
    {
        return DB::transaction(function () use ($data, $actor) {
            $licitacion = Licitacion::create([
                ...$data,
                'responsable_principal_id' => $data['responsable_principal_id'] ?? $actor->id,
            ]);

            LicitacionSolicitud::query()
                ->where('tipo_solicitud', RequestType::Fija->value)
                ->whereNull('licitacion_id')
                ->update(['licitacion_id' => $licitacion->id]);

            return $licitacion->fresh(['responsablePrincipal']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Licitacion $licitacion, array $data): Licitacion
    {
        $licitacion->update($data);

        return $licitacion->fresh(['responsablePrincipal']);
    }

    public function delete(Licitacion $licitacion): void
    {
        if ($licitacion->solicitudes()->exists()) {
            throw new \InvalidArgumentException('No se puede eliminar una licitación con solicitudes asociadas.');
        }

        $licitacion->delete();
    }
}
