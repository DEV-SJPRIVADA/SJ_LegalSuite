<?php

namespace Database\Seeders;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Models\Disciplinary\Fault;
use App\Models\Personnel;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryCaseService;
use App\Services\Disciplinary\DisciplinaryDashboardService;
use App\Services\Disciplinary\DisciplinaryWorkflowService;
use Illuminate\Database\Seeder;

/**
 * Smoke test del workflow disciplinario:
 *  1. Crear personnel y faltas
 *  2. Crear caso (BORRADOR)
 *  3. Recorrer todo el workflow hasta FINALIZADO
 *  4. Validar dashboard KPIs
 *
 * No se incluye en DatabaseSeeder, se invoca con:
 *   php artisan db:seed --class=Database\\Seeders\\WorkflowSmokeTest
 */
class WorkflowSmokeTest extends Seeder
{
    public function run(): void
    {
        $reporter = User::where('email', 'operaciones@sjlegalsuite.local')->firstOrFail();
        $coordinador = User::where('email', 'admin@sjlegalsuite.local')->firstOrFail();
        $lawyer = User::where('email', 'abogado@sjlegalsuite.local')->firstOrFail();

        $personnel = Personnel::firstOrCreate(
            ['document_number' => '99999999'],
            [
                'document_type' => 'CC',
                'first_name' => 'Personal',
                'last_name' => 'Smoke',
                'city' => 'Bogotá',
                'sede' => 'Sede Norte',
                'is_active' => true,
            ]
        );

        $fault1 = Fault::where('code', 'F-001')->firstOrFail();
        $fault2 = Fault::where('code', 'F-006')->firstOrFail();

        /** @var DisciplinaryCaseService $cases */
        $cases = app(DisciplinaryCaseService::class);
        /** @var DisciplinaryWorkflowService $wf */
        $wf = app(DisciplinaryWorkflowService::class);
        /** @var DisciplinaryDashboardService $dash */
        $dash = app(DisciplinaryDashboardService::class);

        $case = $cases->create(
            $reporter,
            [
                'personnel_id' => $personnel->id,
                'city' => 'Bogotá',
                'sede' => 'Sede Norte',
                'summary' => 'Llegó tarde y desobedeció instrucciones.',
            ],
            [
                ['fault_id' => $fault1->id],
                ['fault_id' => $fault2->id],
            ],
        );

        $cases->assignLawyer($case, $lawyer, $coordinador);

        // BORRADOR -> INFORME -> CITACION -> NO_ASISTIO -> JUSTIFICACION -> COMITE -> DILIGENCIA -> DECISION -> FINALIZADO
        $case = $wf->transition($case, CaseStatus::INFORME, $lawyer, 'Informe radicado');
        $case = $wf->scheduleCitation($case, $lawyer, now()->addDays(3), 'Sede Norte');
        $case = $wf->markCitationNoShow($case, $lawyer, 'No se presentó a la diligencia');
        $case = $wf->rejectJustification($case, $lawyer, 'No allegó pruebas en tiempo');
        $case = $wf->transition($case, CaseStatus::DILIGENCIA, $lawyer, 'Inicia diligencia descargos');
        $case = $wf->recordDecision($case, $lawyer, Decision::AMONESTACION_ESCRITA, 'Amonestación por reincidencia');
        $case = $wf->finalize($case, $lawyer, 'Cierre de caso');

        $this->command->info('Caso creado: '.$case->case_number);
        $this->command->info('Estado final: '.$case->current_status->label());
        $this->command->info('Decisión: '.$case->decision?->label());
        $this->command->info('Cantidad de etapas registradas: '.$case->stages()->count());
        $this->command->info('Cantidad de actuaciones (audit log): '.$case->actions()->count());

        $kpis = $dash->kpis();
        $this->command->info('KPIs - total: '.$kpis['total'].' | finalizados: '.$kpis['finalizados']);
    }
}
