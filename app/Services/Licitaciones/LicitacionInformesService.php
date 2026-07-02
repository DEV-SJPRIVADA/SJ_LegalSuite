<?php

namespace App\Services\Licitaciones;

use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionAdjunto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LicitacionInformesService
{
    /**
     * @param  array{fecha_desde?: string|null, fecha_hasta?: string|null, estado_proceso?: string|null, cumplimos?: string|null}  $filters
     * @return Collection<int, Licitacion>
     */
    public function licitacionesForReport(array $filters): Collection
    {
        return $this->licitacionesQuery($filters)
            ->with('responsablePrincipal:id,name')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array{fecha_desde?: string|null, fecha_hasta?: string|null, estado_proceso?: string|null, cumplimos?: string|null}  $filters
     */
    public function licitacionesQuery(array $filters): Builder
    {
        $query = Licitacion::query();

        if (! empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_cierre_oferta', '>=', $filters['fecha_desde']);
        }

        if (! empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_cierre_oferta', '<=', $filters['fecha_hasta']);
        }

        if (! empty($filters['estado_proceso']) && $filters['estado_proceso'] !== 'all') {
            $query->where('estado_proceso', $filters['estado_proceso']);
        }

        if (! empty($filters['cumplimos']) && $filters['cumplimos'] !== 'all') {
            $query->where('cumplimos', $filters['cumplimos']);
        }

        return $query;
    }

    /**
     * @param  array{licitacion_id?: int|null, fecha_desde?: string|null, fecha_hasta?: string|null, busqueda?: string|null}  $filters
     * @return Collection<int, LicitacionAdjunto>
     */
    public function adjuntosForReport(array $filters): Collection
    {
        $query = LicitacionAdjunto::query()
            ->with([
                'usuario:id,name',
                'solicitud:id,nombre,numero_radicado,licitacion_id',
                'solicitud.licitacion:id,numero_proceso,entidad_contratante',
                'licitacion:id,numero_proceso,entidad_contratante',
            ])
            ->orderByDesc('created_at');

        if (! empty($filters['licitacion_id'])) {
            $licitacionId = (int) $filters['licitacion_id'];
            $query->where(function (Builder $q) use ($licitacionId): void {
                $q->where('licitacion_id', $licitacionId)
                    ->orWhereHas('solicitud', fn (Builder $sq) => $sq->where('licitacion_id', $licitacionId));
            });
        }

        if (! empty($filters['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filters['fecha_desde']);
        }

        if (! empty($filters['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filters['fecha_hasta']);
        }

        if (! empty($filters['busqueda'])) {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($filters['busqueda'])).'%';
            $query->where('nombre_archivo', 'like', $like);
        }

        return $query->get();
    }

    /**
     * @return list<string>
     */
    public function distinctEstadosProceso(): array
    {
        return Licitacion::query()
            ->whereNotNull('estado_proceso')
            ->where('estado_proceso', '!=', '')
            ->distinct()
            ->orderBy('estado_proceso')
            ->pluck('estado_proceso')
            ->all();
    }

    /**
     * @param  Collection<int, Licitacion>  $licitaciones
     */
    public function writeExcelToPath(Collection $licitaciones, string $path): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Licitaciones');

        $headers = [
            'Entidad Contratante',
            'Responsable',
            'No. Proceso',
            'Objeto',
            'Cuantía',
            'Fecha Cierre Oferta',
            'Participación',
            'Cumplimos',
            'Estado Proceso',
            'Adjudicado',
            'Motivo de Pérdida',
            'Fecha Registro',
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValue([$col + 1, 1], $header);
        }

        $row = 2;
        foreach ($licitaciones as $licitacion) {
            $sheet->fromArray([
                $licitacion->entidad_contratante ?? '',
                $licitacion->responsablePrincipal?->name ?? 'No asignado',
                $licitacion->numero_proceso ?? '',
                $licitacion->objeto ?? '',
                $licitacion->cuantia ?? '',
                $licitacion->fecha_cierre_oferta?->format('d/m/Y') ?? '',
                $licitacion->participacion_tipo ?? '',
                $licitacion->cumplimos ?? '',
                $licitacion->estado_proceso ?? '',
                $licitacion->adjudicado ?? '',
                $licitacion->motivo_perdida ?? '',
                $licitacion->created_at?->format('d/m/Y H:i') ?? '',
            ], null, 'A'.$row);
            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);
    }
}
