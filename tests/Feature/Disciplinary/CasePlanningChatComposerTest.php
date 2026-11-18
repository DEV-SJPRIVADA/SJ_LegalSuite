<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use App\Models\Disciplinary\DisciplinaryAgendaMessage;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Caja de texto del chat de coordinación en el detalle del expediente.
 *
 * El modal atiende a los dos lados del hilo: el abogado titular (postAgendaLawyer)
 * y planeación / admin (postAgendaPlanning). Antes solo se renderizaba el compositor
 * del abogado, por lo que admin y planeación abrían el chat sin poder escribir.
 */
class CasePlanningChatComposerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_write_in_case_chat_as_planning(): void
    {
        ['case' => $case, 'thread' => $thread] = $this->makeCoordinationCase();
        $admin = $this->makeUser('nivel1');

        Livewire::actingAs($admin)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->call('openPlanningChatModal')
            ->assertSeeHtml('planning-chat-planning-'.$case->id)
            ->assertDontSeeHtml('planning-chat-lawyer-'.$case->id)
            ->set('agendaPlanningBody', 'Reviso disponibilidad de turnos')
            ->call('postAgendaPlanning')
            ->assertHasNoErrors();

        $this->assertSame(2, $thread->messages()->count());
        $this->assertSame(
            'Reviso disponibilidad de turnos',
            DisciplinaryAgendaMessage::query()->where('thread_id', $thread->id)->orderByDesc('id')->value('body'),
        );
    }

    public function test_planning_role_writes_from_coordinations_index_not_case_detail(): void
    {
        ['case' => $case] = $this->makeCoordinationCase();
        $planning = $this->makeUser('nivel3');

        // Planeación no abre el detalle del expediente: su bandeja es Coordinaciones.
        $this->assertFalse($planning->can('view', $case->fresh()));
        $this->assertTrue($planning->can('postAgendaPlanning', $case->fresh()));
    }

    public function test_assigned_lawyer_keeps_own_composer(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeCoordinationCase();

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->call('openPlanningChatModal')
            ->assertSeeHtml('planning-chat-lawyer-'.$case->id)
            ->assertDontSeeHtml('planning-chat-planning-'.$case->id)
            ->set('agendaLawyerBody', 'Solicito fecha disponible')
            ->call('postAgendaLawyer')
            ->assertHasNoErrors();
    }

    public function test_user_without_agenda_permission_sees_thread_read_only(): void
    {
        ['case' => $case] = $this->makeCoordinationCase();
        $administrativa = $this->makeUser('nivel4');

        Livewire::actingAs($administrativa)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->call('openPlanningChatModal')
            ->assertSee('Mensaje inicial del abogado')
            ->assertDontSeeHtml('planning-chat-lawyer-'.$case->id)
            ->assertDontSeeHtml('planning-chat-planning-'.$case->id);
    }

    public function test_closed_coordination_explains_it_is_read_only(): void
    {
        ['case' => $case, 'thread' => $thread, 'lawyer' => $lawyer] = $this->makeCoordinationCase();

        $thread->forceFill(['coordination_status' => 'closed', 'closed_at' => now(), 'closed_by' => $lawyer->id])->save();
        $case->forceFill(['current_status' => CaseStatus::DILIGENCIA])->save();

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->call('openPlanningChatModal')
            ->assertSee('Coordinación cerrada — solo lectura')
            ->assertDontSeeHtml('planning-chat-lawyer-'.$case->id);
    }

    public function test_closed_coordination_never_shows_composer_to_admin(): void
    {
        ['case' => $case, 'thread' => $thread, 'lawyer' => $lawyer] = $this->makeCoordinationCase();
        $admin = $this->makeUser('nivel1');

        $thread->forceFill(['coordination_status' => 'closed', 'closed_at' => now(), 'closed_by' => $lawyer->id])->save();
        $case->forceFill(['current_status' => CaseStatus::DILIGENCIA])->save();

        Livewire::actingAs($admin)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->call('openPlanningChatModal')
            ->assertSee('Coordinación cerrada — solo lectura')
            ->assertDontSeeHtml('planning-chat-planning-'.$case->id);
    }

    /** @return array{case: DisciplinaryCase, thread: DisciplinaryAgendaThread, lawyer: User} */
    private function makeCoordinationCase(): array
    {
        $lawyer = $this->makeUser('nivel6');

        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Chat',
            'document_number' => '81'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-CHAT-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::CITACION_PROGRAMADA,
            'opened_at' => now()->toDateString(),
            'citation_confirmed_date' => now()->addDays(2)->toDateString(),
            'citation_confirmed_time' => '09:00:00',
            'coordination_started_at' => now()->subDay(),
        ]);

        $thread = DisciplinaryAgendaThread::create([
            'disciplinary_case_id' => $case->id,
            'organizational_area_id' => null,
            'opened_by' => $lawyer->id,
            'coordination_started_at' => now()->subDay(),
            'coordination_status' => 'open',
        ]);

        DisciplinaryAgendaMessage::create([
            'thread_id' => $thread->id,
            'user_id' => $lawyer->id,
            'message_kind' => 'general',
            'body' => 'Mensaje inicial del abogado',
        ]);

        return ['case' => $case->fresh(['agendaThread']), 'thread' => $thread, 'lawyer' => $lawyer];
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create([
            'email' => $role.'-chat-'.random_int(1000, 999999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
