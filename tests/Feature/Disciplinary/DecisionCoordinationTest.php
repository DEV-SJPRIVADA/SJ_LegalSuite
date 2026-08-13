<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use App\Livewire\Disciplinary\Coordinations\Index;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Disciplinary\DecisionCoordinationService;
use App\Services\Disciplinary\DisciplinaryAgendaThreadService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\FieldDisciplinaryTestHelpers;
use Tests\TestCase;

class DecisionCoordinationTest extends TestCase
{
    use FieldDisciplinaryTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_planning_options_then_lawyer_confirms_unlocks_draft_gate(): void
    {
        ['case' => $case, 'lawyer' => $lawyer, 'planner' => $planner, 'supervisor' => $supervisor] = $this->makeDecisionCase(Decision::AMONESTACION_ESCRITA);

        $message = app(DisciplinaryAgendaThreadService::class)->postDecisionPlanningMessage(
            $case->fresh(['agendaThread', 'employee']),
            $planner,
            'Opciones de notificación',
            [[
                'date' => '2026-08-20',
                'time' => '09:00',
                'notes' => 'Mañana',
                'zone' => 'Norte',
                'supervision_zone_id' => $supervisor->currentSupervisionZone()->id,
            ]],
        );

        $this->assertFalse(app(DecisionCoordinationService::class)->hasConfirmedNotification($case->fresh()));

        $confirmed = app(DecisionCoordinationService::class)->confirmOption(
            $case->fresh(['agendaThread', 'employee']),
            $lawyer,
            (int) $message->id,
            0,
        );

        $this->assertTrue(app(DecisionCoordinationService::class)->hasConfirmedNotification($confirmed));
        $this->assertSame('Mañana', $confirmed->decision_notification_shift);
        $this->assertSame(
            (int) $supervisor->currentSupervisionZone()->id,
            (int) $confirmed->decision_notification_supervision_zone_id,
        );
        $this->assertTrue($lawyer->can('editDecisionDraft', $confirmed));
    }

    public function test_confirm_works_for_suspension_and_termination(): void
    {
        foreach ([Decision::SUSPENSION, Decision::TERMINACION_CONTRATO] as $decision) {
            ['case' => $case, 'lawyer' => $lawyer, 'planner' => $planner, 'supervisor' => $supervisor] = $this->makeDecisionCase($decision);

            $slots = [[
                'date' => '2026-08-21',
                'time' => '10:00',
                'notes' => 'Tarde',
                'zone' => 'Centro',
                'supervision_zone_id' => $supervisor->currentSupervisionZone()->id,
            ]];
            $extras = $decision === Decision::SUSPENSION
                ? ['suspension_start' => '2026-08-25']
                : ['relief_notes' => 'Relevo operativo'];

            $message = app(DisciplinaryAgendaThreadService::class)->postDecisionPlanningMessage(
                $case->fresh(['agendaThread', 'employee']),
                $planner,
                'Programación',
                $slots,
                $extras,
            );

            $confirmed = app(DecisionCoordinationService::class)->confirmOption(
                $case->fresh(['agendaThread', 'employee']),
                $lawyer,
                (int) $message->id,
                0,
            );

            $this->assertTrue(app(DecisionCoordinationService::class)->hasConfirmedNotification($confirmed), $decision->value);
            if ($decision === Decision::SUSPENSION) {
                $this->assertSame('2026-08-25', $confirmed->decision_payload['suspension_start'] ?? null);
            }
        }
    }

    public function test_lawyer_can_request_new_options_and_republish_clears_confirmation(): void
    {
        ['case' => $case, 'lawyer' => $lawyer, 'planner' => $planner, 'supervisor' => $supervisor] = $this->makeDecisionCase(Decision::SUSPENSION);

        $message = app(DisciplinaryAgendaThreadService::class)->postDecisionPlanningMessage(
            $case->fresh(['agendaThread', 'employee']),
            $planner,
            'Primera tanda',
            [[
                'date' => '2026-08-22',
                'time' => '08:00',
                'notes' => 'Mañana',
                'zone' => 'Sur',
                'supervision_zone_id' => $supervisor->currentSupervisionZone()->id,
            ]],
            ['suspension_start' => '2026-08-28'],
        );

        app(DecisionCoordinationService::class)->confirmOption(
            $case->fresh(['agendaThread', 'employee']),
            $lawyer,
            (int) $message->id,
            0,
        );

        $cleared = app(DecisionCoordinationService::class)->requestNewOptions(
            $case->fresh(['agendaThread']),
            $lawyer,
            'Necesito otras ventanas',
        );

        $this->assertFalse(app(DecisionCoordinationService::class)->hasConfirmedNotification($cleared));

        app(DisciplinaryAgendaThreadService::class)->postDecisionPlanningMessage(
            $cleared->fresh(['agendaThread', 'employee']),
            $planner,
            'Segunda tanda',
            [[
                'date' => '2026-08-23',
                'time' => '14:00',
                'notes' => 'Tarde',
                'zone' => 'Norte',
                'supervision_zone_id' => $supervisor->currentSupervisionZone()->id,
            ]],
            ['suspension_start' => '2026-08-30'],
        );

        $this->assertFalse(app(DecisionCoordinationService::class)->hasConfirmedNotification($cleared->fresh()));
        $this->assertTrue(app(DecisionCoordinationService::class)->hasOpenOptions($cleared->fresh()));
    }

    public function test_livewire_confirm_decision_slot_from_case_detail(): void
    {
        ['case' => $case, 'lawyer' => $lawyer, 'planner' => $planner, 'supervisor' => $supervisor] = $this->makeDecisionCase(Decision::AMONESTACION_ESCRITA);

        $message = app(DisciplinaryAgendaThreadService::class)->postDecisionPlanningMessage(
            $case->fresh(['agendaThread', 'employee']),
            $planner,
            'Opciones',
            [[
                'date' => '2026-08-24',
                'time' => '11:00',
                'notes' => 'Mañana',
                'zone' => 'Este',
                'supervision_zone_id' => $supervisor->currentSupervisionZone()->id,
            ]],
        );

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->set('selectedDecisionSlotKey', $message->id.'-0')
            ->call('confirmDecisionSlot')
            ->assertHasNoErrors();

        $this->assertTrue(app(DecisionCoordinationService::class)->hasConfirmedNotification($case->fresh()));
    }

    public function test_coordinations_ui_does_not_require_second_notification_modal(): void
    {
        ['case' => $case, 'lawyer' => $lawyer, 'planner' => $planner, 'supervisor' => $supervisor] = $this->makeDecisionCase(Decision::AMONESTACION_ESCRITA);

        app(DisciplinaryAgendaThreadService::class)->postDecisionPlanningMessage(
            $case->fresh(['agendaThread', 'employee']),
            $planner,
            'Opciones',
            [[
                'date' => '2026-08-25',
                'time' => '09:30',
                'notes' => 'Mañana',
                'zone' => 'Oeste',
                'supervision_zone_id' => $supervisor->currentSupervisionZone()->id,
            ]],
        );

        Livewire::actingAs($planner)
            ->test(Index::class)
            ->set('selectedThread', (string) $case->agendaThread->id)
            ->assertDontSee('Notificación de decisión')
            ->assertSee('Reproponer opciones de decisión');
    }

    /** @return array{case: DisciplinaryCase, lawyer: User, planner: User, supervisor: User} */
    private function makeDecisionCase(Decision $decision): array
    {
        $lawyer = User::factory()->create([
            'email' => 'law-dec-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $lawyer->assignRole('nivel6');

        $planner = User::factory()->create([
            'email' => 'plan-dec-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $planner->assignRole('nivel3');

        $employee = $this->seedGuardaEmployee('9800'.random_int(100000, 999999));
        $supervisor = $this->seedFieldUserWithCities('nivel7', ['76001']);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-COORD-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'municipality_code' => $employee->municipality_code,
            'current_status' => CaseStatus::DECISION,
            'opened_at' => now()->toDateString(),
            'decision' => $decision,
            'decision_coordination_started_at' => now(),
            'decision_coordination_started_by' => $lawyer->id,
        ]);

        DisciplinaryAgendaThread::query()->create([
            'disciplinary_case_id' => $case->id,
            'opened_by' => $lawyer->id,
            'coordination_started_at' => now(),
            'coordination_status' => 'open',
        ]);

        return [
            'case' => $case->fresh(['agendaThread', 'employee']),
            'lawyer' => $lawyer,
            'planner' => $planner,
            'supervisor' => $supervisor,
        ];
    }
}
