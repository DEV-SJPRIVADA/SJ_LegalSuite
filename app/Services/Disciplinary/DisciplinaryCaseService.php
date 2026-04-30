<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryAction;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\Fault;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Orquestador de casos disciplinarios.
 * Sólo este servicio (o el WorkflowService) deben crear/mutar DisciplinaryCase.
 */
class DisciplinaryCaseService
{
    public function __construct(
        private readonly DisciplinaryWorkflowService $workflow,
    ) {}

    /**
     * Crea un caso en estado BORRADOR y opcionalmente le adjunta faltas.
     *
     * @param  array<int, array{fault_id:int, extra_info?:string|null}>  $faults
     * @param  array<string,mixed>  $attributes
     */
    public function create(User $reporter, array $attributes, array $faults = []): DisciplinaryCase
    {
        return DB::transaction(function () use ($reporter, $attributes, $faults) {
            $case = new DisciplinaryCase($attributes);
            $case->reporter_id = $reporter->id;
            $case->case_number = $this->nextCaseNumber();
            $case->current_status = CaseStatus::BORRADOR;
            $case->opened_at = $attributes['opened_at'] ?? now()->toDateString();
            $case->save();

            $this->syncFaults($case, $faults);

            DisciplinaryAction::create([
                'disciplinary_case_id' => $case->id,
                'user_id' => $reporter->id,
                'action_type' => ActionType::CASO_CREADO,
                'to_status' => $case->current_status,
                'description' => 'Caso creado',
                'performed_at' => now(),
            ]);

            return $case;
        });
    }

    public function assignLawyer(DisciplinaryCase $case, User $lawyer, User $actor): DisciplinaryCase
    {
        return DB::transaction(function () use ($case, $lawyer, $actor) {
            $case->forceFill(['assigned_lawyer_id' => $lawyer->id])->save();

            DisciplinaryAction::create([
                'disciplinary_case_id' => $case->id,
                'user_id' => $actor->id,
                'action_type' => ActionType::CASO_ASIGNADO,
                'description' => "Asignado a {$lawyer->name}",
                'metadata' => ['lawyer_id' => $lawyer->id],
                'performed_at' => now(),
            ]);

            return $case->fresh();
        });
    }

    /**
     * Sincroniza el set completo de faltas del caso.
     * Valida que las faltas con requires_extra_info traigan extra_info.
     *
     * @param  array<int, array{fault_id:int, extra_info?:string|null}>  $faults
     */
    public function syncFaults(DisciplinaryCase $case, array $faults): void
    {
        $faultIds = collect($faults)->pluck('fault_id')->unique()->all();
        $catalog = Fault::whereIn('id', $faultIds)->get()->keyBy('id');

        $sync = [];
        foreach ($faults as $row) {
            $fault = $catalog->get($row['fault_id']);
            if (! $fault) {
                continue;
            }
            $extra = $row['extra_info'] ?? null;
            if ($fault->requires_extra_info && empty($extra)) {
                throw new \InvalidArgumentException(
                    "La falta '{$fault->name}' requiere descripción adicional."
                );
            }
            $sync[$fault->id] = ['extra_info' => $extra];
        }

        $case->faults()->sync($sync);
    }

    /**
     * Genera el siguiente case_number atómicamente.
     * Formato: DISC-YYYY-NNNNNN.
     */
    private function nextCaseNumber(): string
    {
        $year = now()->year;
        $prefix = "DISC-{$year}-";

        $last = DisciplinaryCase::withTrashed()
            ->where('case_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('case_number');

        $next = $last
            ? ((int) substr($last, strlen($prefix))) + 1
            : 1;

        return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
