<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryAgendaThreadService;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DisciplinaryCoordinationsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_planeacion_sees_register_notification_after_posting_diligence_slots(): void
    {
        $planner = $this->makeUser('planeacion', 'planner-coord@test.local');
        $lawyer = $this->makeUser('abogado', 'lawyer-coord@test.local');
        $case = $this->makeCaseWithOpenThread($lawyer, CaseStatus::CITACION_PROGRAMADA);

        app(DisciplinaryAgendaThreadService::class)->postPlanningMessage(
            $case->fresh(['agendaThread']),
            $planner,
            'Fechas disponibles',
            [['date' => now()->addDays(5)->toDateString(), 'time' => '10:00', 'notes' => null]],
            [],
        );

        Livewire::actingAs($planner)
            ->test(\App\Livewire\Disciplinary\Coordinations\Index::class)
            ->assertOk()
            ->assertSee('Registrar notificación y supervisor');
    }

    public function test_planeacion_can_view_coordinations_when_case_left_citacion_stage(): void
    {
        $planner = $this->makeUser('planeacion', 'planner-stale@test.local');
        $lawyer = $this->makeUser('abogado', 'lawyer-stale@test.local');
        $case = $this->makeCaseWithOpenThread($lawyer, CaseStatus::DILIGENCIA);

        $case->forceFill([
            'notification_requested_at' => now(),
            'notification_requested_by' => $lawyer->id,
        ])->save();

        Livewire::actingAs($planner)
            ->test(\App\Livewire\Disciplinary\Coordinations\Index::class)
            ->assertOk()
            ->assertSee('Coordinaciones abiertas');
    }

    private function makeUser(string $role, string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
            'read_only' => false,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeCaseWithOpenThread(User $lawyer, CaseStatus $status): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Coord',
            'document_number' => '9200'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-COORD-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => $status,
            'opened_at' => now()->toDateString(),
            'coordination_started_at' => now(),
            'citation_confirmed_date' => now()->addDays(2)->toDateString(),
        ]);

        DisciplinaryAgendaThread::query()->create([
            'disciplinary_case_id' => $case->id,
            'opened_by' => $lawyer->id,
            'coordination_started_at' => now(),
            'coordination_status' => 'open',
        ]);

        return $case;
    }
}
