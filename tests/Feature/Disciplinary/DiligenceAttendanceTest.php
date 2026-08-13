<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DiligenceAttendanceService;
use App\Services\Disciplinary\DisciplinaryDiligenceWorkflowService;
use App\Services\Disciplinary\FoGj04DraftService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class DiligenceAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_attendance_registration_is_immutable(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $service = app(DiligenceAttendanceService::class);

        $service->register($case->fresh(), $lawyer, DiligenceAttendance::ATTENDED);

        $this->expectException(ValidationException::class);
        $service->register($case->fresh(), $lawyer, DiligenceAttendance::ABSENT);
    }

    public function test_advance_to_decision_blocked_without_fo_gj_04(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        app(DiligenceAttendanceService::class)->register($case->fresh(), $lawyer, DiligenceAttendance::ATTENDED);

        $workflow = app(DisciplinaryDiligenceWorkflowService::class);
        $missing = $workflow->missingAdvanceToDecisionRequirements($case->fresh());

        $this->assertContains('FO-GJ-04 generado y guardado en el expediente', $missing);
    }

    public function test_diligence_advance_requires_fo_gj_04_and_signature(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        app(DiligenceAttendanceService::class)->register($case->fresh(), $lawyer, DiligenceAttendance::ATTENDED);

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case->fresh()])
            ->call('requestAdvanceFromDiligencia')
            ->assertHasErrors('diligenceAdvance');
    }

    public function test_stage_c_shows_attendance_buttons_first(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case->fresh()])
            ->call('openStageCard', 'c')
            ->assertSee('Asistió')
            ->assertSee('No asistió')
            ->assertSee('Reprogramar diligencia')
            ->assertSee('Primer paso obligatorio');
    }

    /** @return array{case: DisciplinaryCase, lawyer: User} */
    private function makeDiligenceCase(): array
    {
        $lawyer = User::factory()->create([
            'email' => 'lawyer-att-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $lawyer->assignRole('nivel6');

        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Att',
            'document_number' => '9300'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-ATT-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::DILIGENCIA,
            'opened_at' => now()->toDateString(),
            'citation_confirmed_date' => now()->addDay()->toDateString(),
            'citation_confirmed_time' => '10:00:00',
        ]);

        return ['case' => $case, 'lawyer' => $lawyer];
    }
}
