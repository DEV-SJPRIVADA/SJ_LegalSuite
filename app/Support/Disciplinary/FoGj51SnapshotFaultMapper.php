<?php

namespace App\Support\Disciplinary;

use App\Models\Disciplinary\Fault;
use Illuminate\Support\Str;

/**
 * Convierte las casillas marcadas del informe FO-GJ-51 (etiquetas del formulario)
 * en filas pivot para {@see DisciplinaryCaseService::create()}.
 *
 * Las claves deben coincidir exactamente con {@see FoGj51Catalog::faultLeft()}
 * y {@see FoGj51Catalog::faultRight()} (valores enviados en POST).
 */
final class FoGj51SnapshotFaultMapper
{
    /**
     * Etiqueta del formulario → código en `faults` (FaultsCatalogSeeder).
     *
     * @var array<string, string>
     */
    private const LABEL_TO_CODE = [
        'Retardo al Servicio' => 'F-001',
        'Actitud poco alerta y vigilante (dormido)' => 'F-003',
        'No porta uniforme de dotación adecuadamente' => 'F-005',
        'Ausencia al servicio' => 'F-007',
        'Cambio por solicitud del cliente' => 'F-009',
        'Descuido con elementos de puesto y/o dotación' => 'F-011',
        'Irrespeto a superiores, compañeros y/o clientes' => 'F-013',
        'Incautación o decomiso de arma de dotación' => 'F-014',
        'Abandono del puesto' => 'F-002',
        'Síntomas de alicoramiento' => 'F-004',
        'Incumplimiento de consignas' => 'F-006',
        'Daño con elementos de puesto y/o dotación' => 'F-008',
        'Mala presentación personal' => 'F-010',
        'Incumplimiento de instrucciones' => 'F-012',
    ];

    /**
     * @param  array<string, mixed>  $snapshot
     * @return list<array{fault_id: int, extra_info?: string|null}>
     */
    public static function pivotRowsFromSnapshot(array $snapshot, string $observationsFallback): array
    {
        $codes = [];

        foreach (array_merge(
            array_values($snapshot['fo51_fault_left'] ?? []),
            array_values($snapshot['fo51_fault_right'] ?? [])
        ) as $label) {
            if (! is_string($label) || $label === '') {
                continue;
            }
            $code = self::LABEL_TO_CODE[$label] ?? null;
            if ($code !== null && ! in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        if (! empty($snapshot['fo51_fault_other_chk'])) {
            if (! in_array('F-999', $codes, true)) {
                $codes[] = 'F-999';
            }
        }

        $trimmedObs = trim($observationsFallback);
        $defaultExtra = Str::limit($trimmedObs, 900) ?: 'Informe disciplinario FO-GJ-51 — detalle en documento adjunto.';

        if ($codes === []) {
            $fault = Fault::query()->where('code', 'F-006')->firstOrFail();

            return [['fault_id' => $fault->id, 'extra_info' => $defaultExtra]];
        }

        $faultsByCode = Fault::query()->whereIn('code', $codes)->get()->keyBy('code');
        $rows = [];

        foreach ($codes as $code) {
            $fault = $faultsByCode->get($code);
            if (! $fault instanceof Fault) {
                continue;
            }

            $extra = null;
            if ($code === 'F-999') {
                $detail = trim((string) ($snapshot['fo51_fault_other_detail'] ?? ''));
                $extra = Str::limit(
                    $detail !== '' ? $detail : ($trimmedObs !== '' ? $observationsFallback : 'Otros'),
                    900
                );
            }

            $rows[] = ['fault_id' => $fault->id, 'extra_info' => $extra];
        }

        if ($rows === []) {
            $fault = Fault::query()->where('code', 'F-006')->firstOrFail();

            return [['fault_id' => $fault->id, 'extra_info' => $defaultExtra]];
        }

        return $rows;
    }
}
