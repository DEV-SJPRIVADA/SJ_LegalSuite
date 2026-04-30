<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseBucket;
use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\Fault;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Genera todas las métricas del dashboard de un solo viaje a la BD por sección,
 * usando agregaciones eficientes (GROUP BY + COUNT) sobre columnas indexadas.
 *
 * Las consultas se basan en disciplinary_cases.current_status (denormalizado e
 * indexado) para evitar joins costosos contra disciplinary_stages en tiempo real.
 */
class DisciplinaryDashboardService
{
    /**
     * KPIs principales en un único query agrupado.
     *
     * @return array{total:int, pendientes:int, en_proceso:int, finalizados:int, por_estado:array<string,int>}
     */
    public function kpis(): array
    {
        $rows = DisciplinaryCase::query()
            ->select('current_status', DB::raw('COUNT(*) as total'))
            ->groupBy('current_status')
            ->pluck('total', 'current_status');

        $byStatus = $rows->all();
        $totals = ['pendientes' => 0, 'en_proceso' => 0, 'finalizados' => 0];

        foreach (CaseStatus::cases() as $status) {
            $count = (int) ($byStatus[$status->value] ?? 0);
            $totals[match ($status->bucket()) {
                CaseBucket::PENDIENTE => 'pendientes',
                CaseBucket::EN_PROCESO => 'en_proceso',
                CaseBucket::FINALIZADO => 'finalizados',
            }] += $count;
        }

        return [
            'total' => array_sum($totals),
            'pendientes' => $totals['pendientes'],
            'en_proceso' => $totals['en_proceso'],
            'finalizados' => $totals['finalizados'],
            'por_estado' => $byStatus,
        ];
    }

    /**
     * Distribución por tipo de falta (ranking para gráfica).
     *
     * @return list<array{fault_id:int, code:string, name:string, total:int}>
     */
    public function casesByFault(int $limit = 10): array
    {
        return Fault::query()
            ->select('faults.id as fault_id', 'faults.code', 'faults.name')
            ->selectRaw('COUNT(disciplinary_case_fault.disciplinary_case_id) as total')
            ->leftJoin('disciplinary_case_fault', 'disciplinary_case_fault.fault_id', '=', 'faults.id')
            ->groupBy('faults.id', 'faults.code', 'faults.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'fault_id' => (int) $r->fault_id,
                'code' => $r->code,
                'name' => $r->name,
                'total' => (int) $r->total,
            ])
            ->all();
    }

    /**
     * Distribución por ciudad.
     *
     * @return list<array{city:string, total:int}>
     */
    public function casesByCity(): array
    {
        return DisciplinaryCase::query()
            ->select('city', DB::raw('COUNT(*) as total'))
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['city' => (string) $r->city, 'total' => (int) $r->total])
            ->all();
    }

    /**
     * Resumen de carga por abogado: total / pendientes / en_proceso / finalizados.
     * Una sola consulta usando CASE/WHEN para no recorrer la tabla varias veces.
     *
     * @return list<array{lawyer_id:int, lawyer_name:string, total:int, pendientes:int, en_proceso:int, finalizados:int}>
     */
    public function lawyerWorkload(): array
    {
        $pendingValues = $this->statusListByBucket(CaseBucket::PENDIENTE);
        $inProgressValues = $this->statusListByBucket(CaseBucket::EN_PROCESO);
        $finishedValues = $this->statusListByBucket(CaseBucket::FINALIZADO);

        $bind = function (array $values) {
            return implode(',', array_map(fn ($v) => "'".addslashes($v)."'", $values));
        };

        return User::query()
            ->select('users.id as lawyer_id', 'users.name as lawyer_name')
            ->selectRaw('COUNT(dc.id) as total')
            ->selectRaw("SUM(CASE WHEN dc.current_status IN ({$bind($pendingValues)}) THEN 1 ELSE 0 END) as pendientes")
            ->selectRaw("SUM(CASE WHEN dc.current_status IN ({$bind($inProgressValues)}) THEN 1 ELSE 0 END) as en_proceso")
            ->selectRaw("SUM(CASE WHEN dc.current_status IN ({$bind($finishedValues)}) THEN 1 ELSE 0 END) as finalizados")
            ->leftJoin('disciplinary_cases as dc', 'dc.assigned_lawyer_id', '=', 'users.id')
            ->whereNull('dc.deleted_at')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'lawyer_id' => (int) $r->lawyer_id,
                'lawyer_name' => (string) $r->lawyer_name,
                'total' => (int) $r->total,
                'pendientes' => (int) $r->pendientes,
                'en_proceso' => (int) $r->en_proceso,
                'finalizados' => (int) $r->finalizados,
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    private function statusListByBucket(CaseBucket $bucket): array
    {
        return collect(CaseStatus::cases())
            ->filter(fn (CaseStatus $s) => $s->bucket() === $bucket)
            ->map(fn (CaseStatus $s) => $s->value)
            ->values()
            ->all();
    }
}
