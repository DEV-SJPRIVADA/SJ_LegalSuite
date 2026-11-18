<?php

namespace Tests\Feature\Integration;

use App\Enums\EmployeeDocumentType;
use App\Models\Employee;
use App\Models\EmployeeJobPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class EmployeeIngestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'integrations.statflow.key' => 'token-test',
            'integrations.statflow.secret' => 'secreto-test',
            'integrations.statflow.timestamp_tolerance' => 300,
        ]);

        DB::table('colombian_municipalities')->insert([
            'department_code' => '76',
            'department_name' => 'VALLE DEL CAUCA',
            'municipality_code' => '76001',
            'municipality_name' => 'CALI',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function record(array $overrides = []): array
    {
        return array_merge([
            'id' => 101,
            'document_number' => '1020304050',
            'document_type' => 'C',
            'first_name' => 'JUAN CARLOS',
            'last_name' => 'PEREZ GOMEZ',
            'gender' => 'masculino',
            'birth_date' => '1990-05-04',
            'address' => 'CALLE 1 # 2-3',
            'residence_municipality_code' => '76001',
            'residence_department_code' => null,
            'municipality_code' => '76001',
            'work_department_code' => null,
            'phone' => '3001234567',
            'email' => 'juan@example.com',
            'hired_at' => '2020-01-15',
            'contract_type' => 'termino_fijo',
            'job_title' => 'GUARDA',
            'position_code' => '009',
            'base_salary' => '2456300.00',
            'is_active' => true,
            'termination_at' => null,
        ], $overrides);
    }

    private function body(array $records): string
    {
        return json_encode([
            'run_id' => (string) Str::uuid(),
            'mode' => 'incremental',
            'batch' => 1,
            'total_batches' => 1,
            'sent_at' => now()->toIso8601String(),
            'records' => $records,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function push(array $records, array $headers = []): TestResponse
    {
        $body = $this->body($records);

        $server = array_merge([
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_INTEGRATION_KEY' => 'token-test',
            'HTTP_X_SIGNATURE' => hash_hmac('sha256', $body, 'secreto-test'),
            'HTTP_X_TIMESTAMP' => (string) time(),
        ], $headers);

        return $this->call('POST', '/api/integraciones/empleados', [], [], [], $server, $body);
    }

    private function makeEmployee(array $attributes = []): Employee
    {
        return Employee::query()->create(array_merge([
            'first_name' => 'BASE',
            'last_name' => 'BASE',
            'document_number' => '99999999',
            'document_type' => 'CC',
            'is_active' => true,
        ], $attributes));
    }

    public function test_ping_responde_ok(): void
    {
        $this->getJson('/api/integraciones/ping')
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_rechaza_sin_token(): void
    {
        $this->push([$this->record()], ['HTTP_X_INTEGRATION_KEY' => ''])
            ->assertStatus(401);

        $this->assertSame(0, Employee::withTrashed()->count());
    }

    public function test_rechaza_firma_invalida(): void
    {
        $this->push([$this->record()], ['HTTP_X_SIGNATURE' => str_repeat('a', 64)])
            ->assertStatus(422);

        $this->assertSame(0, Employee::withTrashed()->count());
    }

    public function test_rechaza_timestamp_viejo(): void
    {
        $this->push([$this->record()], ['HTTP_X_TIMESTAMP' => (string) (time() - 1000)])
            ->assertStatus(422);
    }

    public function test_crea_y_luego_actualiza(): void
    {
        $this->push([$this->record()])
            ->assertOk()
            ->assertJsonPath('created', 1)
            ->assertJsonPath('updated', 0);

        $this->assertDatabaseHas('employees', [
            'document_number' => '1020304050',
            'first_name' => 'JUAN CARLOS',
            'last_name' => 'PEREZ GOMEZ',
            'external_id' => '101',
            'document_type' => 'C',
            'is_active' => true,
        ]);

        $this->push([$this->record(['phone' => '3119999999', 'base_salary' => '3000000.00'])])
            ->assertOk()
            ->assertJsonPath('created', 0)
            ->assertJsonPath('updated', 1);

        $this->assertSame(1, Employee::withTrashed()->count());
        $this->assertDatabaseHas('employees', [
            'document_number' => '1020304050',
            'phone' => '3119999999',
            'base_salary' => 3000000.00,
        ]);
    }

    public function test_es_idempotente(): void
    {
        $record = $this->record();

        $this->push([$record])->assertOk();
        $this->push([$record])->assertOk();

        $this->assertSame(1, Employee::withTrashed()->count());
    }

    public function test_payload_identico_no_genera_update(): void
    {
        $record = $this->record();

        $this->push([$record])->assertOk();

        $before = Employee::withTrashed()->where('document_number', '1020304050')->firstOrFail();

        $this->push([$record])
            ->assertOk()
            ->assertJsonPath('updated', 0)
            ->assertJsonPath('skipped', 1);

        $after = Employee::withTrashed()->where('document_number', '1020304050')->firstOrFail();

        $this->assertSame($before->updated_at?->toDateTimeString(), $after->updated_at?->toDateTimeString());
    }

    public function test_no_cambia_el_id_ni_borra(): void
    {
        $existing = $this->makeEmployee([
            'document_number' => '1020304050',
            'first_name' => 'VIEJO',
            'last_name' => 'NOMBRE',
        ]);

        $this->push([$this->record(['first_name' => 'NUEVO'])])

            ->assertOk()
            ->assertJsonPath('updated', 1);

        $fresh = Employee::withTrashed()->findOrFail($existing->id);

        $this->assertSame($existing->id, $fresh->id);
        $this->assertSame('NUEVO', $fresh->first_name);
        $this->assertNull($fresh->deleted_at);
        $this->assertSame(1, Employee::withTrashed()->count());
    }

    public function test_municipio_inexistente_queda_en_null_sin_romper_la_fk(): void
    {
        $this->push([$this->record([
            'residence_municipality_code' => '99999',
            'municipality_code' => '99998',
        ])])->assertOk();

        $employee = Employee::withTrashed()->where('document_number', '1020304050')->firstOrFail();

        $this->assertNull($employee->residence_municipality_code);
        $this->assertNull($employee->residence_department_code);
        $this->assertNull($employee->municipality_code);
        $this->assertNull($employee->work_department_code);
    }

    public function test_restaura_documento_soft_deleted_si_viene_activo(): void
    {
        $existing = $this->makeEmployee([
            'document_number' => '1020304050',
            'first_name' => 'VIEJO',
            'last_name' => 'NOMBRE',
        ]);
        $existing->delete();

        $this->push([$this->record()])->assertOk();

        $this->assertDatabaseHas('employees', [
            'id' => $existing->id,
            'document_number' => '1020304050',
            'deleted_at' => null,
        ]);
    }

    public function test_no_restaura_si_viene_inactivo(): void
    {
        $existing = $this->makeEmployee([
            'document_number' => '1020304050',
            'first_name' => 'VIEJO',
            'last_name' => 'NOMBRE',
        ]);
        $existing->delete();

        $this->push([$this->record(['is_active' => false])])
            ->assertOk()
            ->assertJsonPath('skipped', 1);

        $this->assertNotNull(Employee::withTrashed()->findOrFail($existing->id)->deleted_at);
    }

    public function test_cargo_nuevo_se_crea_con_clasificacion_derivada(): void
    {
        $this->push([
            $this->record(['job_title' => 'GUARDA MOTORIZADO', 'document_number' => '11111111']),
            $this->record(['job_title' => 'AUXILIAR ADMINISTRATIVO', 'document_number' => '22222222']),
        ])->assertOk();

        $guarda = EmployeeJobPosition::query()->where('name', 'GUARDA MOTORIZADO')->firstOrFail();
        $this->assertTrue($guarda->is_guarda);
        $this->assertSame('operativo', $guarda->employee_scope?->value);

        $administrativo = EmployeeJobPosition::query()->where('name', 'AUXILIAR ADMINISTRATIVO')->firstOrFail();
        $this->assertFalse($administrativo->is_guarda);
        $this->assertSame('administrativo', $administrativo->employee_scope?->value);
    }

    public function test_cargo_existente_no_se_sobrescribe(): void
    {
        $position = EmployeeJobPosition::query()->create([
            'slug' => 'guarda-existente',
            'name' => 'GUARDA',
            'is_guarda' => false,
            'employee_scope' => 'administrativo',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->push([$this->record(['job_title' => 'guarda'])])->assertOk();

        $fresh = $position->fresh();

        $this->assertFalse($fresh->is_guarda);
        $this->assertSame('administrativo', $fresh->employee_scope?->value);

        $employee = Employee::withTrashed()->where('document_number', '1020304050')->firstOrFail();
        $this->assertSame($position->id, $employee->employee_job_position_id);
        $this->assertSame('administrativo', $employee->employee_scope?->value);
    }

    public function test_mapeo_de_tipos_de_documento_de_statflow(): void
    {
        $this->assertSame(EmployeeDocumentType::Cedula, EmployeeDocumentType::fromStatflowCode('CEDULA'));
        $this->assertSame(EmployeeDocumentType::C, EmployeeDocumentType::fromStatflowCode('C'));
        $this->assertSame(EmployeeDocumentType::Ce, EmployeeDocumentType::fromStatflowCode('CEDULA EXTRANJERIA'));
        $this->assertSame(EmployeeDocumentType::Ce, EmployeeDocumentType::fromStatflowCode('TARJETA EXTRANJERIA'));
        $this->assertSame(EmployeeDocumentType::Ce, EmployeeDocumentType::fromStatflowCode('CE'));
        $this->assertSame(EmployeeDocumentType::Nit, EmployeeDocumentType::fromStatflowCode('NIT'));
        $this->assertSame(EmployeeDocumentType::N, EmployeeDocumentType::fromStatflowCode('N'));
        $this->assertSame(EmployeeDocumentType::Ti, EmployeeDocumentType::fromStatflowCode('TI'));
        $this->assertSame(EmployeeDocumentType::Pt, EmployeeDocumentType::fromStatflowCode('PT'));
        $this->assertSame(EmployeeDocumentType::Cedula, EmployeeDocumentType::fromStatflowCode(null));
        $this->assertSame(EmployeeDocumentType::Cedula, EmployeeDocumentType::fromStatflowCode('RARO'));
    }

    public function test_desvinculado_queda_inactivo_y_con_fecha_de_retiro(): void
    {
        $this->push([$this->record([
            'is_active' => false,
            'termination_at' => '2026-01-31',
        ])])->assertOk();

        $employee = Employee::withTrashed()->where('document_number', '1020304050')->firstOrFail();

        $this->assertFalse($employee->is_active);
        $this->assertSame('2026-01-31', $employee->termination_at?->format('Y-m-d'));
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/integraciones/last-run.json'));

        parent::tearDown();
    }
}
