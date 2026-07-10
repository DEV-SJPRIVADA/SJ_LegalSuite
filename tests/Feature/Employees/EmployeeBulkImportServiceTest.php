<?php

namespace Tests\Feature\Employees;

use App\Enums\EmployeeContractType;
use App\Models\ColombianMunicipality;
use App\Models\Employee;
use App\Models\EmployeeJobPosition;
use App\Services\Employees\EmployeeBulkImportService;
use App\Support\Employees\EmployeeBulkImportStore;
use App\Support\Employees\EmployeeImportValueNormalizer;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\TestCase;

class EmployeeBulkImportServiceTest extends TestCase
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

    public function test_import_requires_hire_date_and_contract_type(): void
    {
        $path = $this->writeSpreadsheet([
            'Nombre completo', 'Tipo de Documento', 'Número de Documento',
            'Fecha de Nacimiento', 'Género', 'Dirección de Residencia',
            'Ciudad de residencia', 'Ciudad de labor',
            'Teléfono Celular', 'Correo Electrónico', 'Fecha de Ingreso', 'Tipo de Contrato',
            'Cargo', 'Área o departamento', 'Salario Base',
            'Nombre de Contacto de Emergencia', 'Teléfono del Contacto',
        ], [
            [
                'Ana Prueba', 'CC', '990010001', '', 'Mujer', '',
                '76001', '76001', '', '', '', '',
                'GUARDA DE SEGURIDAD', 'operativo', '', 'S/I', 'NN',
            ],
        ]);

        $result = app(EmployeeBulkImportService::class)->importFromPath($path);

        $this->assertSame(0, $result['inserted']);
        $this->assertArrayHasKey(2, $result['errors']);
    }

    public function test_import_creates_employee_with_contract_and_territory(): void
    {
        $guarda = EmployeeJobPosition::query()->where('is_guarda', true)->firstOrFail();

        $path = $this->writeSpreadsheet([
            'Nombre completo', 'Tipo de Documento', 'Número de Documento',
            'Fecha de Nacimiento', 'Género', 'Dirección de Residencia',
            'Ciudad de residencia', 'Ciudad de labor',
            'Teléfono Celular', 'Correo Electrónico', 'Fecha de Ingreso', 'Tipo de Contrato',
            'Cargo', 'Área o departamento', 'Salario Base',
            'Nombre de Contacto de Emergencia', 'Teléfono del Contacto',
        ], [
            [
                'Ana Prueba', 'CC', '990010002', '', 'Mujer', '',
                'Cali', '76001', '', '', '2024-06-01', 'termino indefinido',
                $guarda->name, 'operativo', '', 'S/I', 'NO',
            ],
        ]);

        $result = app(EmployeeBulkImportService::class)->importFromPath($path);

        $this->assertSame(1, $result['inserted']);
        $this->assertSame([], $result['errors']);

        $employee = Employee::query()->where('document_number', '990010002')->firstOrFail();
        $this->assertSame('2024-06-01', $employee->hired_at?->format('Y-m-d'));
        $this->assertSame(EmployeeContractType::TerminoIndefinido, $employee->contract_type);
        $this->assertSame('76001', $employee->residence_municipality_code);
        $this->assertSame('76001', $employee->municipality_code);
        $this->assertNull($employee->emergency_contact_name);
        $this->assertNull($employee->emergency_contact_phone);
        $this->assertNull($employee->termination_at);
    }

    public function test_import_rejects_no_definido_contract(): void
    {
        $guarda = EmployeeJobPosition::query()->where('is_guarda', true)->firstOrFail();

        $path = $this->writeSpreadsheet([
            'Nombre completo', 'Tipo de Documento', 'Número de Documento',
            'Fecha de Nacimiento', 'Género', 'Dirección de Residencia',
            'Ciudad de residencia', 'Ciudad de labor',
            'Teléfono Celular', 'Correo Electrónico', 'Fecha de Ingreso', 'Tipo de Contrato',
            'Cargo', 'Área o departamento', 'Salario Base',
            'Nombre de Contacto de Emergencia', 'Teléfono del Contacto',
        ], [
            [
                'Ana Prueba', 'CC', '990010003', '', 'Hombre', '',
                '76001', '76001', '', '', '2024-06-01', 'no definido',
                $guarda->name, 'operativo', '', '', '',
            ],
        ]);

        $result = app(EmployeeBulkImportService::class)->importFromPath($path);

        $this->assertSame(0, $result['inserted']);
        $this->assertArrayHasKey(2, $result['errors']);
    }

    public function test_nullable_contact_normalizer(): void
    {
        $this->assertNull(EmployeeImportValueNormalizer::nullableContact('S/I'));
        $this->assertNull(EmployeeImportValueNormalizer::nullableContact('NN'));
        $this->assertSame('María López', EmployeeImportValueNormalizer::nullableContact('María López'));
    }

    public function test_batch_import_updates_real_progress(): void
    {
        $guarda = EmployeeJobPosition::query()->where('is_guarda', true)->firstOrFail();
        $importer = app(EmployeeBulkImportService::class);

        $path = $this->writeSpreadsheet([
            'Nombre completo', 'Tipo de Documento', 'Número de Documento',
            'Fecha de Nacimiento', 'Género', 'Dirección de Residencia',
            'Ciudad de residencia', 'Ciudad de labor',
            'Teléfono Celular', 'Correo Electrónico', 'Fecha de Ingreso', 'Tipo de Contrato',
            'Cargo', 'Área o departamento', 'Salario Base',
            'Nombre de Contacto de Emergencia', 'Teléfono del Contacto',
        ], [
            [
                'Uno Prueba', 'CC', '990010010', '', 'Mujer', '',
                '76001', '76001', '', '', '2024-06-01', 'termino indefinido',
                $guarda->name, 'operativo', '', '', '',
            ],
            [
                'Dos Prueba', 'CC', '990010011', '', 'Hombre', '',
                '76001', '76001', '', '', '2024-06-02', 'termino indefinido',
                $guarda->name, 'operativo', '', '', '',
            ],
        ]);

        $token = EmployeeBulkImportStore::createFromUploadedFile(1, $path);
        $importer->prepareImport($token);

        $before = $importer->progressSnapshot($token);
        $this->assertSame(EmployeeBulkImportStore::STATUS_PENDING, $before['status']);
        $this->assertSame(2, $before['total_rows']);
        $this->assertSame(0, $before['processed_rows']);
        $this->assertSame(0, $before['percent']);

        $importer->processAllBatches($token);

        $after = $importer->progressSnapshot($token);
        $this->assertSame(EmployeeBulkImportStore::STATUS_COMPLETED, $after['status']);
        $this->assertSame(2, $after['processed_rows']);
        $this->assertSame(100, $after['percent']);
        $this->assertSame(2, $after['inserted']);

        EmployeeBulkImportStore::delete($token);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private function writeSpreadsheet(array $headers, array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $value);
            }
        }

        $path = storage_path('framework/testing/employee-import-'.uniqid('', true).'.xlsx');
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
