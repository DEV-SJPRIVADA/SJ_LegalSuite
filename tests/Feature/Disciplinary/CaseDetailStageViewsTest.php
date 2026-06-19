<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Enums\Disciplinary\DocumentType;
use App\Livewire\Disciplinary\Cases\CaseDetail;
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
            ->test(CaseDetail::class, ['case' => $case])
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
            'diligence_attendance' => DiligenceAttendance::ATTENDED,
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
            ->test(CaseDetail::class, ['case' => $case->fresh(['agendaThread'])])
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
            ->test(CaseDetail::class, ['case' => $case->fresh(['agendaThread'])])
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
            'diligence_attendance' => DiligenceAttendance::ATTENDED,
            'diligence_attendance_registered_at' => now(),
            'diligence_attendance_registered_by' => $lawyer->id,
            'fo_gj_04_generated_at' => now(),
            'fo_gj_04_generated_by' => $lawyer->id,
            'fo_gj_04_payload' => ['worker_signature_data_uri' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='],
        ])->save();

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
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
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->call('requestAdvanceFromDiligencia')
            ->assertHasErrors('diligenceAdvance');
    }

    public function test_comite_stage_shows_diligenciar_buttons_not_only_fo_gj_44(): void
    {
        $lawyer = $this->user('abogado', 'stage-comite@test.local');
        $case = $this->caseInStatus($lawyer, CaseStatus::COMITE_DISCIPLINARIO);
        $case->forceFill([
            'citation_confirmed_date' => now()->subDays(5)->toDateString(),
            'coordination_started_at' => now()->subDays(10),
            'diligence_attendance' => DiligenceAttendance::ABSENT,
            'diligence_attendance_registered_at' => now()->subDays(5),
            'diligence_attendance_registered_by' => $lawyer->id,
            'fo_gj_03_generated_at' => now()->subDays(8),
            'fo_gj_03_generated_by' => $lawyer->id,
            'fo_gj_44_generated_at' => now()->subDays(4),
            'fo_gj_44_generated_by' => $lawyer->id,
            'fo_gj_44_draft_completed_at' => now()->subDays(4),
            'fo_gj_44_payload' => [
                'sign_time' => '10:00 AM',
                'sign_day' => '15',
                'sign_month' => 'junio',
                'sign_year_suffix' => '6',
                'witness1_name' => 'Testigo Uno',
                'witness1_cargo' => 'Supervisor',
                'witness1_date' => '15/06/2026',
                'witness2_name' => 'Testigo Dos',
                'witness2_cargo' => 'Operador',
                'witness2_date' => '15/06/2026',
            ],
        ])->save();

        $stack = app(CaseOverviewStageStack::class);
        $this->assertSame(['c', 'a'], $stack->stagesForCase($case->fresh()));

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->assertSee('Etapa C · Comité disciplinario')
            ->assertSee('Diligenciar comité')
            ->assertDontSee('Etapa B · Citación a diligencia (FO-GJ-03)');
    }

    public function test_comite_advance_transitions_to_decision(): void
    {
        $lawyer = $this->user('abogado', 'stage-comite-advance@test.local');
        $case = $this->caseInStatus($lawyer, CaseStatus::COMITE_DISCIPLINARIO);
        $case->forceFill([
            'citation_confirmed_date' => now()->subDays(5)->toDateString(),
            'diligence_attendance' => DiligenceAttendance::ABSENT,
            'diligence_attendance_registered_at' => now()->subDays(5),
            'diligence_attendance_registered_by' => $lawyer->id,
            'comite_generated_at' => now(),
            'comite_generated_by' => $lawyer->id,
            'comite_draft_completed_at' => now()->subHour(),
            'comite_payload' => [
                'decision_narrative' => 'Decisión del comité.',
                'attendees' => [
                    ['name' => 'Integrante Uno', 'cargo' => 'Director', 'signature_data_uri' => null],
                ],
            ],
        ])->save();

        DisciplinaryDocument::query()->create([
            'disciplinary_case_id' => $case->id,
            'uploaded_by' => $lawyer->id,
            'document_type' => DocumentType::ACTA_COMITE,
            'original_name' => 'Acta-comite-test.pdf',
            'disk' => 'local',
            'path' => 'disciplinary/test/acta-comite.pdf',
            'mime_type' => 'application/pdf',
            'notes' => DisciplinaryCase::NOTE_COMITE_ACTA_GENERATED,
        ]);

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->assertSee('Siguiente etapa')
            ->call('requestAdvanceFromDiligencia')
            ->call('confirmAdvanceFromDiligencia')
            ->assertHasNoErrors();

        $this->assertSame(CaseStatus::DECISION, $case->fresh()->current_status);
    }

    public function test_comite_advance_blocked_without_acta(): void
    {
        $lawyer = $this->user('abogado', 'stage-comite-blocked@test.local');
        $case = $this->caseInStatus($lawyer, CaseStatus::COMITE_DISCIPLINARIO);
        $case->forceFill([
            'citation_confirmed_date' => now()->subDays(5)->toDateString(),
            'diligence_attendance' => DiligenceAttendance::ABSENT,
            'diligence_attendance_registered_at' => now()->subDays(5),
            'diligence_attendance_registered_by' => $lawyer->id,
        ])->save();

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
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
