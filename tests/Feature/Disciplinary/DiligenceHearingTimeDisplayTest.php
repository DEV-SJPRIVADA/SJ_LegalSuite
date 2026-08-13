<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DiligenceHearingTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_prefers_fo_gj_03_payload_hearing_time_over_null_confirmed_time(): void
    {
        $case = $this->caseWithConfirmedDate(hearingTimePayload: '14:30');

        $this->assertSame('02:30 PM', $case->resolvedDiligenceHearingTimeLabel());
    }

    public function test_falls_back_to_citation_confirmed_time_when_no_payload(): void
    {
        $case = $this->caseWithConfirmedDate(confirmedTime: '09:15:00');

        $this->assertSame('09:15 AM', $case->resolvedDiligenceHearingTimeLabel());
    }

    public function test_payload_hearing_time_wins_when_lawyer_edited_after_slot(): void
    {
        $case = $this->caseWithConfirmedDate(
            confirmedTime: '09:00:00',
            hearingTimePayload: '11:45',
        );

        $this->assertSame('11:45 AM', $case->resolvedDiligenceHearingTimeLabel());
    }

    public function test_case_detail_bar_shows_resolved_hearing_time(): void
    {
        $lawyer = User::factory()->create([
            'email' => 'hearing-bar@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $lawyer->assignRole('nivel6');

        $case = $this->caseWithConfirmedDate(
            lawyerId: $lawyer->id,
            status: CaseStatus::DILIGENCIA,
            hearingTimePayload: '16:00',
            withCoordination: true,
        );

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case])
            ->call('openStageCard', 'b')
            ->assertSee('03/06/2026')
            ->assertSee('04:00 PM');
    }

    private function caseWithConfirmedDate(
        ?string $confirmedTime = null,
        ?string $hearingTimePayload = null,
        ?int $lawyerId = null,
        CaseStatus $status = CaseStatus::CITACION_PROGRAMADA,
        bool $withCoordination = false,
    ): DisciplinaryCase {
        $employee = Employee::query()->create([
            'first_name' => 'Hearing',
            'last_name' => 'Time',
            'document_number' => '9600'.random_int(100000, 999999),
        ]);

        $payload = $hearingTimePayload !== null
            ? ['hearing_time' => $hearingTimePayload, 'modality' => 'presencial']
            : null;

        return DisciplinaryCase::query()->create([
            'case_number' => 'DISC-HT-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyerId ?? User::factory()->create()->id,
            'current_status' => $status,
            'opened_at' => now()->toDateString(),
            'coordination_started_at' => $withCoordination ? now() : null,
            'citation_confirmed_date' => '2026-06-03',
            'citation_confirmed_time' => $confirmedTime,
            'fo_gj_03_payload' => $payload,
            'fo_gj_03_draft_completed_at' => $payload !== null ? now() : null,
        ]);
    }
}
