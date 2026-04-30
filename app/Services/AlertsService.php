<?php

namespace App\Services;

use App\Enums\Disciplinary\StageStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryStage;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Genera alertas globales agregadas de TODOS los módulos del sistema.
 *
 * Hoy solo agrega del módulo Disciplinario. Cuando se sumen Licitaciones,
 * Tutelas, Demandas, etc., cada uno aportará sus propias alertas a los buckets
 * (vencidos, próximos, sin asignar, pendientes de decisión).
 */
class AlertsService
{
    /**
     * @return array{
     *   vencidos: array{count:int, items:list<array{id:int, label:string, module:string, due_at:?string}>},
     *   proximos: array{count:int, items:list<array{id:int, label:string, module:string, due_at:?string}>},
     *   sin_asignar: array{count:int, items:list<array{id:int, label:string, module:string}>},
     *   pendientes_decision: array{count:int, items:list<array{id:int, label:string, module:string}>}
     * }
     */
    public function summary(int $itemsPerBucket = 5, ?User $user = null): array
    {
        return [
            'vencidos' => $this->vencidos($itemsPerBucket, $user),
            'proximos' => $this->proximos($itemsPerBucket, $user),
            'sin_asignar' => $this->sinAsignar($itemsPerBucket, $user),
            'pendientes_decision' => $this->pendientesDecision($itemsPerBucket, $user),
        ];
    }

    /**
     * Casos con etapas cuyo plazo legal ya venció (deadline_at < hoy).
     */
    private function vencidos(int $limit, ?User $user): array
    {
        $stages = DisciplinaryStage::query()
            ->with(['case:id,case_number,personnel_id', 'case.personnel:id,first_name,last_name'])
            ->whereHas('case', fn ($q) => $q->when($user, fn ($qq) => $qq->forDisciplinaryActor($user)))
            ->whereIn('status', [StageStatus::PENDIENTE->value, StageStatus::EN_CURSO->value])
            ->whereNotNull('deadline_at')
            ->whereDate('deadline_at', '<', now())
            ->orderBy('deadline_at')
            ->get();

        return [
            'count' => $stages->count(),
            'items' => $stages->take($limit)->map(fn ($s) => [
                'id' => $s->case->id,
                'label' => "{$s->case->case_number} · {$s->case->personnel?->first_name} {$s->case->personnel?->last_name}",
                'module' => 'Disciplinario',
                'route' => route('disciplinary.cases.show', $s->case),
                'due_at' => $s->deadline_at?->format('Y-m-d'),
            ])->all(),
        ];
    }

    /**
     * Citaciones / etapas con plazo en los próximos 3 días.
     */
    private function proximos(int $limit, ?User $user): array
    {
        $stages = DisciplinaryStage::query()
            ->with(['case:id,case_number,personnel_id', 'case.personnel:id,first_name,last_name'])
            ->whereHas('case', fn ($q) => $q->when($user, fn ($qq) => $qq->forDisciplinaryActor($user)))
            ->whereIn('status', [StageStatus::PENDIENTE->value, StageStatus::EN_CURSO->value])
            ->whereNotNull('deadline_at')
            ->whereDate('deadline_at', '>=', now())
            ->whereDate('deadline_at', '<=', now()->addDays(3))
            ->orderBy('deadline_at')
            ->get();

        return [
            'count' => $stages->count(),
            'items' => $stages->take($limit)->map(fn ($s) => [
                'id' => $s->case->id,
                'label' => "{$s->case->case_number} · {$s->case->personnel?->first_name} {$s->case->personnel?->last_name}",
                'module' => 'Disciplinario',
                'route' => route('disciplinary.cases.show', $s->case),
                'due_at' => $s->deadline_at?->format('Y-m-d'),
            ])->all(),
        ];
    }

    /**
     * Casos abiertos sin abogado asignado.
     */
    private function sinAsignar(int $limit, ?User $user): array
    {
        if ($user && $user->hasRole('abogado') && ! $user->hasRole('admin')) {
            return ['count' => 0, 'items' => []];
        }

        $cases = DisciplinaryCase::query()
            ->with('personnel:id,first_name,last_name')
            ->when($user, fn ($q) => $q->forDisciplinaryActor($user))
            ->whereNull('assigned_lawyer_id')
            ->whereNotIn('current_status', ['finalizado', 'archivado'])
            ->orderByDesc('opened_at')
            ->get();

        return [
            'count' => $cases->count(),
            'items' => $cases->take($limit)->map(fn ($c) => [
                'id' => $c->id,
                'label' => "{$c->case_number} · {$c->personnel?->first_name} {$c->personnel?->last_name}",
                'module' => 'Disciplinario',
                'route' => route('disciplinary.cases.show', $c),
            ])->all(),
        ];
    }

    /**
     * Casos en estado DECISION o COMITE_DISCIPLINARIO esperando resolución.
     */
    private function pendientesDecision(int $limit, ?User $user): array
    {
        $cases = DisciplinaryCase::query()
            ->with('personnel:id,first_name,last_name')
            ->when($user, fn ($q) => $q->forDisciplinaryActor($user))
            ->whereIn('current_status', ['decision', 'comite_disciplinario'])
            ->orderByDesc('opened_at')
            ->get();

        return [
            'count' => $cases->count(),
            'items' => $cases->take($limit)->map(fn ($c) => [
                'id' => $c->id,
                'label' => "{$c->case_number} · {$c->personnel?->first_name} {$c->personnel?->last_name}",
                'module' => 'Disciplinario',
                'route' => route('disciplinary.cases.show', $c),
            ])->all(),
        ];
    }

    /**
     * Tendencia mensual de casos abiertos (últimos 6 meses) — alimenta gráfica.
     *
     * @return list<array{month:string, total:int}>
     */
    public function monthlyTrend(int $months = 6, ?User $user = null): array
    {
        $start = Carbon::now()->subMonths($months - 1)->startOfMonth();

        $rows = DisciplinaryCase::query()
            ->when($user, fn ($q) => $q->forDisciplinaryActor($user))
            ->selectRaw("DATE_FORMAT(opened_at, '%Y-%m') as month, COUNT(*) as total")
            ->where('opened_at', '>=', $start->toDateString())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $result = [];
        for ($i = 0; $i < $months; $i++) {
            $m = $start->copy()->addMonths($i)->format('Y-m');
            $result[] = [
                'month' => Carbon::createFromFormat('Y-m', $m)->locale('es')->translatedFormat('M Y'),
                'total' => (int) ($rows[$m] ?? 0),
            ];
        }

        return $result;
    }
}
