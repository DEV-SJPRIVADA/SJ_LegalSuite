<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\InformeSubmissionStatus;
use App\Livewire\Disciplinary\InformesPendientes;
use App\Models\Disciplinary\InformeSubmission;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InformesPendientesUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_reviewer_sees_pending_queue_with_display_name(): void
    {
        $reviewer = User::factory()->create([
            'email' => 'ops-review-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $reviewer->assignRole('nivel2');
        $reviewer->givePermissionTo('disciplinary.review-inform');

        $submitter = User::factory()->create([
            'email' => 'sup-'.random_int(1000, 9999).'@test.local',
            'name' => 'Supervisor Campo',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);

        $employee = Employee::query()->create([
            'first_name' => 'ADOLFO',
            'last_name' => 'GOMEZ ROMERO',
            'document_type' => 'CC',
            'document_number' => '9103668',
            'is_active' => true,
        ]);

        InformeSubmission::query()->create([
            'submitted_by' => $submitter->id,
            'assigned_reviewer_id' => $reviewer->id,
            'employee_id' => $employee->id,
            'status' => InformeSubmissionStatus::PENDIENTE_REVISION,
            'storage_disk' => 'local',
            'storage_path' => 'disciplinary/test.pdf',
            'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ]);

        Livewire::actingAs($reviewer)
            ->test(InformesPendientes::class)
            ->assertSee('Revisión FO-GJ-51')
            ->assertSee('Adolfo')
            ->assertSee('9103668')
            ->assertSee('Supervisor Campo')
            ->assertSet('pendingCount', 1);
    }
}
