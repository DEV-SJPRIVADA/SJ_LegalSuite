<?php

namespace Tests\Feature\Employees;

use App\Enums\EmployeeContractType;
use App\Livewire\Employees\EmployeesIndex;
use App\Models\ColombianMunicipality;
use App\Models\Employee;
use App\Models\EmployeeJobPosition;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeesIndexFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        ColombianMunicipality::query()->create([
            'department_code' => '76',
            'department_name' => 'Valle del Cauca',
            'municipality_code' => '76001',
            'municipality_name' => 'Cali',
        ]);
    }

    public function test_save_normalizes_blank_contact_markers(): void
    {
        $admin = $this->adminUser();
        $guarda = EmployeeJobPosition::query()->where('is_guarda', true)->firstOrFail();

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->call('openCreate')
            ->set('fullName', 'Ana Prueba Test')
            ->set('documentNumber', '990011001')
            ->set('residenceDepartmentCode', '76')
            ->set('workDepartmentCode', '76')
            ->set('municipalityCode', '76001')
            ->set('hiredAt', '2024-01-15')
            ->set('contractType', EmployeeContractType::TerminoIndefinido->value)
            ->set('employeeScope', 'operativo')
            ->set('employeeJobPositionId', $guarda->id)
            ->set('phone', 'S/I')
            ->set('email', 'NA')
            ->set('emergencyContactName', 'NN')
            ->set('emergencyContactPhone', 'NO')
            ->call('save')
            ->assertHasNoErrors();

        $employee = Employee::query()->where('document_number', '990011001')->firstOrFail();
        $this->assertNull($employee->phone);
        $this->assertNull($employee->email);
        $this->assertNull($employee->emergency_contact_name);
        $this->assertNull($employee->emergency_contact_phone);
        $this->assertTrue($employee->isProfileComplete());
    }

    public function test_changing_scope_clears_incompatible_job_position(): void
    {
        $admin = $this->adminUser();
        $guarda = EmployeeJobPosition::query()->where('is_guarda', true)->firstOrFail();
        $adminPosition = EmployeeJobPosition::query()
            ->where('employee_scope', 'administrativo')
            ->where('is_guarda', false)
            ->firstOrFail();

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->call('openCreate')
            ->set('employeeScope', 'operativo')
            ->set('employeeJobPositionId', $guarda->id)
            ->set('employeeScope', 'administrativo')
            ->assertSet('employeeJobPositionId', null)
            ->set('employeeJobPositionId', $adminPosition->id)
            ->assertSet('employeeScope', 'administrativo');
    }

    public function test_form_profile_issues_reflect_missing_fields(): void
    {
        $admin = $this->adminUser();

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->call('openCreate')
            ->assertSet('formProfileComplete', false)
            ->tap(function ($component) {
                $issues = $component->get('formProfileIssues');
                $this->assertContains('Cargo', $issues);
                $this->assertContains('Fecha de ingreso', $issues);
                $this->assertContains('Territorio de residencia', $issues);
            });
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create([
            'email' => 'admin-form-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('nivel1');

        return $admin;
    }
}
