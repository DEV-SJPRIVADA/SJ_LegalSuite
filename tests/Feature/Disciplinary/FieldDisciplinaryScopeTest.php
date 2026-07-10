<?php

namespace Tests\Feature\Disciplinary;

use App\Models\Employee;
use App\Models\EmployeeJobPosition;
use App\Models\User;
use App\Services\Employees\EmployeeResolver;
use App\Support\Disciplinary\FieldDisciplinaryScopeService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FieldDisciplinaryTestHelpers;
use Tests\TestCase;

class FieldDisciplinaryScopeTest extends TestCase
{
    use FieldDisciplinaryTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_supervisor_without_cities_has_empty_employee_search(): void
    {
        $supervisor = $this->seedFieldUserWithCities('nivel7', []);
        $this->seedGuardaEmployee('880010001');

        $response = $this->actingAs($supervisor)->getJson('/api/employees/search?q=880');

        $response->assertOk();
        $response->assertJsonPath('scope_blocked', true);
        $response->assertJsonPath('items', []);
    }

    public function test_supervisor_only_sees_guardas_in_authorized_city(): void
    {
        $this->seedMunicipality('76001', 'Cali');
        $this->seedMunicipality('05001', 'Medellín');

        $supervisor = $this->seedFieldUserWithCities('nivel7', ['76001']);
        $inScope = $this->seedGuardaEmployee('880010010', '76001');
        $this->seedGuardaEmployee('880010011', '05001');

        $response = $this->actingAs($supervisor)->getJson('/api/employees/search?q=8800100');

        $response->assertOk();
        $response->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.id', $inScope->id);
    }

    public function test_operador_has_same_scope_restrictions_as_supervisor(): void
    {
        $this->seedMunicipality('76001', 'Cali');
        $operador = $this->seedFieldUserWithCities('nivel8', ['76001']);
        $employee = $this->seedGuardaEmployee('880010020', '76001');

        $resolver = app(EmployeeResolver::class);
        $resolved = $resolver->resolveForDisciplinaryActor($operador, $employee->id, $employee->document_number);

        $this->assertSame($employee->id, $resolved->id);
    }

    public function test_resolver_rejects_non_guarda_employee_for_supervisor(): void
    {
        $this->seedMunicipality('76001', 'Cali');
        $supervisor = $this->seedFieldUserWithCities('nivel7', ['76001']);

        $otherPositionId = (int) EmployeeJobPosition::query()->where('is_guarda', false)->value('id');
        $employee = Employee::query()->create([
            'first_name' => 'Aux',
            'last_name' => 'Servicios',
            'document_number' => '880010030',
            'document_type' => 'CC',
            'municipality_code' => '76001',
            'employee_job_position_id' => $otherPositionId,
            'job_title' => 'Auxiliar de servicios',
            'is_active' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('guarda');

        app(EmployeeResolver::class)->resolveForDisciplinaryActor($supervisor, $employee->id, $employee->document_number);
    }

    public function test_supervisor_candidates_filtered_by_case_municipality(): void
    {
        $this->seedMunicipality('76001', 'Cali');
        $this->seedMunicipality('05001', 'Medellín');

        $caliSupervisor = $this->seedFieldUserWithCities('nivel7', ['76001']);
        $medellinSupervisor = $this->seedFieldUserWithCities('nivel7', ['05001']);

        $scope = app(FieldDisciplinaryScopeService::class);
        $candidates = $scope->applySupervisorCandidatesForMunicipality(User::query(), '76001')->pluck('id')->all();

        $this->assertContains($caliSupervisor->id, $candidates);
        $this->assertNotContains($medellinSupervisor->id, $candidates);
    }
}
