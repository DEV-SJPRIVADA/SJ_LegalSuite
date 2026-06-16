<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryDocument;
use App\Models\Employee;
use App\Models\User;
use App\Support\Disciplinary\CaseOverviewStageStack;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CaseDetailStageViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_citacion_stage_shows_active_b_without_c(): void
    {
        $lawyer = $this->user('abogado', 'stage-b-active@test.local');
        $case = $this->caseInStatus($lawyer, CaseStatus::CITACION_PROGRAMADA);

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case])
            ->assertSee('Etapa B · Citación a diligencia (FO-GJ-03)')
            ->assertDontSee('Completada · Solo lectura')
            ->assertDontSee('Etapa C · Diligencia disciplinaria (FO-GJ-04)');
    }

    public function test_overview_stage_stack_orders_newest_first_a_last(): void
    {
        $stack = app(CaseOverviewStageStack::class);

        $employee = Employee::query()->create([
            'first_name' => 'Stack',
            'last_name' => 'Order',
            'document_number' => '9500'.random_int(100000, 999999),
        ]);

        $citacionCase = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-STACK-B',
            'employee_id' => $employee->id,
            'current_status' => CaseStatus::CITACION_PROGRAMADA,
            'opened_at' => now()->toDateString(),
        ]);

        $diligenciaCase = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-STACK-C',
            'employee_id' => $employee->id,
            'current_status' => CaseStatus::DILIGENCIA,
            'opened_at' => now()->toDateString(),
            'coordination_started_at' => now(),
        ]);

        $this->assertSame(['b', 'a'], $stack->stagesForCase($citacionCase));
        $this->assertSame(['c', 'b', 'a'], $stack->stagesForCase($diligenciaCase));
    }

    public function test_diligencia_stage_shows_b_readonly_and_active_c(): void
    {
        $lawyer = $this->user('abogado', 'stage-c-active@test.local');
        $case = $this->caseInStatus($lawyer, CaseStatus::DILIGENCIA);
        $case->forceFill([
            'citation_confirmed_date' => now()->addDays(2)->toDateString(),
            'citation_confirmed_time' => '10:00:00',
            'diligence_attendance' => \App\Enums\Disciplinary\DiligenceAttendance::ATTENDED,
            'diligence_attendance_registered_at' => now(),
            'diligence_attendance_registered_by' => $lawyer->id,
            'fo_gj_03_generated_at' => now(),
            'fo_gj_03_generated_by' => $lawyer->id,
            'citation_evidence_uploaded_at' => now(),
            'citation_evidence_type' => 'signed',
        ])->save();

        DisciplinaryDocument::query()->create([
            'disciplinary_case_id' => $case->id,
            'uploaded_by' => $lawyer->id,
            'document_type' => DocumentType::CITACION,
            'original_name' => 'fo-gj-03.pdf',
            'disk' => 'local',
            'path' => 'disciplinary/test/fo-gj-03.pdf',
            'mime_type' => 'application/pdf',
            'notes' => DisciplinaryCase::NOTE_FO_GJ_03_GENERATED,
        ]);

        $case->agendaThread?->update(['coordination_status' => 'closed']);

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case->fresh(['agendaThread'])])
            ->assertSee('Completada · Solo lectura')
            ->assertSee('Etapa B · Citación a diligencia (FO-GJ-03)')
            ->assertSee('Etapa C · Diligencia disciplinaria (FO-GJ-04)')
            ->assertSee('Diligenciar FO-GJ-04')
            ->assertDontSee('Plantilla FO-GJ-04')
            ->assertDontSee('Borrador diligenciado')
            ->assertDontSee('Iniciar coordinación')
            ->assertDontSee('Cargar evidencia PDF')
            ->assertDontSee('FO-GJ-03 · Citación')
            ->assertDontSee('Citación firmada o acta de rechazo con testigos');

        $html = Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case->fresh(['agendaThread'])])
            ->html();

        $posC = strpos($html, 'Etapa C · Diligencia disciplinaria (FO-GJ-04)');
        $posB = strpos($html, 'Etapa B · Citación a diligencia (FO-GJ-03)');
        $posA = strpos($html, 'data-stage-block="a"');

        $this->assertNotFalse($posC);
        $this->assertNotFalse($posB);
        $this->assertNotFalse($posA);
        $this->assertLessThan($posB, $posC, 'Etapa C debe aparecer antes que B en el HTML');
        $this->assertLessThan($posA, $posB, 'Etapa B debe aparecer antes que A en el HTML');
    }

    public function test_diligence_advance_transitions_to_decision(): void
    {
        $lawyer = $this->user('abogado', 'stage-c-advance@test.local');
        $case = $this->caseInStatus($lawyer, CaseStatus::DILIGENCIA);
        $case->forceFill([
            'citation_confirmed_date' => now()->addDay()->toDateString(),
            'coordination_started_at' => now(),
            'diligence_attendance' => \App\Enums\Disciplinary\DiligenceAttendance::ATTENDED,
            'diligence_attendance_registered_at' => now(),
            'diligence_attendance_registered_by' => $lawyer->id,
            'fo_gj_04_generated_at' => now(),
            'fo_gj_04_generated_by' => $lawyer->id,
            'fo_gj_04_payload' => ['worker_signature_data_uri' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='],
        ])->save();

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case->fresh()])
            ->call('requestAdvanceFromDiligencia')
            ->call('confirmAdvanceFromDiligencia')
            ->assertHasNoErrors();

        $this->assertSame(CaseStatus::DECISION, $case->fresh()->current_status);
    }

    public function test_diligence_advance_blocked_without_requirements(): void
    {
        $lawyer = $this->user('abogado', 'stage-c-blocked@test.local');
        $case = $this->caseInStatus($lawyer, CaseStatus::DILIGENCIA);
        $case->forceFill([
            'citation_confirmed_date' => now()->addDay()->toDateString(),
            'coordination_started_at' => now(),
        ])->save();

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case->fresh()])
            ->call('requestAdvanceFromDiligencia')
            ->assertHasErrors('diligenceAdvance');
    }

    private function user(string $role, string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function caseInStatus(User $lawyer, CaseStatus $status): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'Stage',
            'last_name' => 'View',
            'document_number' => '9400'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-STAGE-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => $status,
            'opened_at' => now()->toDateString(),
            'coordination_started_at' => now(),
        ]);

        DisciplinaryAgendaThread::query()->create([
            'disciplinary_case_id' => $case->id,
            'opened_by' => $lawyer->id,
            'coordination_started_at' => now(),
            'coordination_status' => $status === CaseStatus::DILIGENCIA ? 'closed' : 'open',
        ]);

        return $case->fresh(['agendaThread']);
    }
}
