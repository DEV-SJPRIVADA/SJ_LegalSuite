<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseBucket;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\StageType;
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
 *
 * La distribución por etapa de flujo (A–F) usa current_stage_type (StageType).
 */
class DisciplinaryDashboardService
{
    /**
     * KPIs principales en un único query agrupado.
     *
     * @return array{total:int, pendientes:int, en_proceso:int, finalizados:int, por_estado:array<string,int>}
     */
    public function kpis(?User $actor = null): array
    {
        $rows = DisciplinaryCase::query()
            ->when($actor, fn ($q) => $q->forDisciplinaryActor($actor))
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
     * Métricas para las donas «Casos por etapa»: total del alcance y seis buckets
     * A–F sobre current_stage_type. B incluye citación, reprogramación y justificación;
     * C incluye comité y diligencia/acta.
     *
     * @return array{
     *     total: int,
     *     stages: list<array{
     *         letter: string,
     *         title: string,
     *         count: int,
     *         rest: int,
     *         percent: float,
     *         percent_label: string
     *     }>
     * }
     */
    public function workflowStageDonuts(?User $actor = null): array
    {
        $byStage = DisciplinaryCase::query()
            ->when($actor, fn ($q) => $q->forDisciplinaryActor($actor))
            ->select('current_stage_type', DB::raw('COUNT(*) as c'))
            ->groupBy('current_stage_type')
            ->get()
            ->mapWithKeys(function ($row) {
                $raw = $row->current_stage_type;
                $key = match (true) {
                    $raw instanceof StageType => $raw->value,
                    $raw === null, $raw === '' => '',
                    default => (string) $raw,
                };

                return [$key => (int) $row->c];
            });

        $total = (int) $byStage->sum();

        $definitions = [
            [
                'letter' => 'A',
                'title' => 'Informe disciplinario',
                'types' => [StageType::INFORME],
            ],
            [
                'letter' => 'B',
                'title' => 'Citación a diligencia',
                'types' => [StageType::CITACION, StageType::REPROGRAMACION, StageType::JUSTIFICACION],
            ],
            [
                'letter' => 'C',
                'title' => 'Diligencia y acta',
                'types' => [StageType::COMITE, StageType::DILIGENCIA],
            ],
            [
                'letter' => 'D',
                'title' => 'Decisión / cierre',
                'types' => [StageType::DECISION],
            ],
            [
                'letter' => 'E',
                'title' => 'Apelación',
                'types' => [StageType::APELACION],
            ],
            [
                'letter' => 'F',
                'title' => 'Segunda instancia',
                'types' => [StageType::SEGUNDA_INSTANCIA],
            ],
        ];

        $stages = [];
        foreach ($definitions as $def) {
            $count = 0;
            foreach ($def['types'] as $type) {
                $count += (int) ($byStage[$type->value] ?? 0);
            }
            $rest = max(0, $total - $count);
            $percent = $total > 0 ? round(100 * $count / $total, 1) : 0.0;
            $percentLabel = $this->formatPercentLabel($percent);

            $stages[] = [
                'letter' => $def['letter'],
                'title' => $def['title'],
                'count' => $count,
                'rest' => $rest,
                'percent' => $percent,
                'percent_label' => $percentLabel,
            ];
        }

        return [
            'total' => $total,
            'stages' => $stages,
        ];
    }

    private function formatPercentLabel(float $percent): string
    {
        $s = number_format($percent, 1, '.', '');

        return rtrim(rtrim($s, '0'), '.');
    }

    /**
     * Distribución por tipo de falta (ranking para gráfica).
     *
     * @return list<array{fault_id:int, code:string, name:string, total:int}>
     */
    public function casesByFault(int $limit = 10, ?User $actor = null): array
    {
        return Fault::query()
            ->select(['faults.id', 'faults.code', 'faults.name'])
            ->withCount([
                'disciplinaryCases as total' => function ($q) use ($actor) {
                    if ($actor) {
                        $q->forDisciplinaryActor($actor);
                    }
                },
            ])
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'fault_id' => (int) $r->id,
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
    public function casesByCity(?User $actor = null): array
    {
        return DisciplinaryCase::query()
            ->when($actor, fn ($q) => $q->forDisciplinaryActor($actor))
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
    public function lawyerWorkload(?User $actor = null): array
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
            ->when(
                $actor && $actor->hasRole('abogado') && ! $actor->hasRole('admin'),
                fn ($q) => $q->where('users.id', $actor->id),
            )
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
