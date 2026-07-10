<?php

namespace Tests\Feature\Users;

use App\Enums\EmployeeContractType;
use App\Livewire\Employees\EmployeesIndex;
use App\Livewire\Users\OrganizationCatalog;
use App\Models\Employee;
use App\Models\EmployeeJobPosition;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\FieldDisciplinaryTestHelpers;
use Tests\TestCase;

class EmployeeJobPositionCatalogTest extends TestCase
{
    use FieldDisciplinaryTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_create_employee_job_position_from_organization_catalog(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-catalog-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('nivel1');

        Livewire::actingAs($admin)
            ->test(OrganizationCatalog::class)
            ->set('employeePositionName', 'Guarda auxiliar')
            ->set('employeePositionSlug', 'guarda-auxiliar')
            ->set('employeePositionScope', 'operativo')
            ->set('employeePositionIsGuarda', true)
            ->call('saveEmployeePosition')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_job_positions', [
            'name' => 'Guarda auxiliar',
            'slug' => 'guarda-auxiliar',
            'is_guarda' => 1,
            'employee_scope' => 'operativo',
            'is_active' => 1,
        ]);
    }

    public function test_employees_index_filters_incomplete_profiles(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-emp-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('nivel1');

        $this->seedMunicipality('76001', 'Cali');
        $guardaId = $this->guardaJobPositionId();

        Employee::query()->create([
            'first_name' => 'Completo',
            'last_name' => 'Perfil',
            'document_number' => '880020001',
            'document_type' => 'CC',
            'residence_municipality_code' => '76001',
            'residence_department_code' => '76',
            'municipality_code' => '76001',
            'work_department_code' => '76',
            'employee_job_position_id' => $guardaId,
            'employee_scope' => 'operativo',
            'job_title' => 'GUARDA DE SEGURIDAD',
            'hired_at' => '2024-01-01',
            'contract_type' => EmployeeContractType::TerminoIndefinido->value,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'first_name' => 'Sin',
            'last_name' => 'Ciudad',
            'document_number' => '880020002',
            'document_type' => 'CC',
            'employee_job_position_id' => $guardaId,
            'employee_scope' => 'operativo',
            'job_title' => 'GUARDA DE SEGURIDAD',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->set('status', 'incompletos')
            ->assertSee('Sin Ciudad')
            ->assertDontSee('Completo Perfil');
    }

    public function test_cannot_delete_employee_job_position_in_use(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-del-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('nivel1');

        $position = EmployeeJobPosition::query()->where('is_guarda', true)->firstOrFail();
        $this->seedGuardaEmployee('880020003');

        Livewire::actingAs($admin)
            ->test(OrganizationCatalog::class)
            ->call('deleteEmployeePosition', $position->id);

        $this->assertDatabaseHas('employee_job_positions', ['id' => $position->id]);
    }
}
