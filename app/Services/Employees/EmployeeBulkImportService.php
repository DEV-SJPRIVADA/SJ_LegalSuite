<?php

namespace App\Services\Employees;

use App\Enums\EmployeeContractType;
use App\Enums\EmployeeDocumentType;
use App\Enums\EmployeeGender;
use App\Models\ColombianMunicipality;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use RuntimeException;

class EmployeeBulkImportService
{
    private const HEADER_ROW = 1;

    /** @var array<string, string> */
    private const HEADER_ALIASES = [
        'nombre completo' => 'full_name',
        'nombres' => 'first_name',
        'apellidos' => 'last_name',
        'tipo de documento' => 'document_type',
        'tipo documento' => 'document_type',
        'numero de documento' => 'document_number',
        'número de documento' => 'document_number',
        'numero documento' => 'document_number',
        'fecha de nacimiento' => 'birth_date',
        'fecha nacimiento' => 'birth_date',
        'genero' => 'gender',
        'género' => 'gender',
        'direccion de residencia' => 'address',
        'dirección de residencia' => 'address',
        'direccion' => 'address',
        'ciudad / municipio' => 'municipality',
        'ciudad municipio' => 'municipality',
        'municipio' => 'municipality',
        'codigo municipio' => 'municipality_code',
        'código municipio' => 'municipality_code',
        'telefono celular' => 'phone',
        'teléfono celular' => 'phone',
        'telefono' => 'phone',
        'correo electronico' => 'email',
        'correo electrónico' => 'email',
        'email' => 'email',
        'fecha de ingreso' => 'hired_at',
        'fecha ingreso' => 'hired_at',
        'tipo de contrato' => 'contract_type',
        'tipo contrato' => 'contract_type',
        'cargo / puesto de trabajo' => 'job_title',
        'cargo puesto de trabajo' => 'job_title',
        'cargo' => 'job_title',
        'area o departamento' => 'department_area',
        'área o departamento' => 'department_area',
        'area departamento' => 'department_area',
        'salario base' => 'base_salary',
        'fecha de terminacion' => 'termination_at',
        'fecha terminación' => 'termination_at',
        'fecha terminacion' => 'termination_at',
        'nombre de contacto de emergencia' => 'emergency_contact_name',
        'contacto emergencia' => 'emergency_contact_name',
        'telefono del contacto' => 'emergency_contact_phone',
        'teléfono del contacto' => 'emergency_contact_phone',
        'telefono contacto emergencia' => 'emergency_contact_phone',
    ];

    /**
     * @return array{inserted:int, updated:int, skipped:int, errors:array<int, string>}
     */
    public function importFromPath(string $absolutePath): array
    {
        $reader = new XlsxReader;
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($absolutePath);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = (int) $sheet->getHighestDataRow();
            $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            $map = $this->buildColumnMap($sheet, $highestCol);

            $inserted = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();
            try {
                for ($row = self::HEADER_ROW + 1; $row <= $highestRow; $row++) {
                    $data = $this->rowToPayload($sheet, $row, $map);
                    if ($this->rowIsEmpty($data)) {
                        $skipped++;

                        continue;
                    }

                    try {
                        $attrs = $this->normalizePayload($data);
                        $existing = Employee::withTrashed()
                            ->where('document_number', $attrs['document_number'])
                            ->first();

                        if ($existing?->trashed()) {
                            $existing->restore();
                        }

                        if ($existing) {
                            $existing->update($attrs);
                            $updated++;
                        } else {
                            Employee::create($attrs);
                            $inserted++;
                        }
                    } catch (\Throwable $e) {
                        $errors[$row] = $e->getMessage();
                    }
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return compact('inserted', 'updated', 'skipped', 'errors');
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    public function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Empleados');
        $headers = [
            'Nombre completo', 'Tipo de Documento', 'Número de Documento',
            'Fecha de Nacimiento', 'Género', 'Dirección de Residencia', 'Código municipio (DIVIPOLA)',
            'Teléfono Celular', 'Correo Electrónico', 'Fecha de Ingreso', 'Tipo de Contrato',
            'Cargo / Puesto de Trabajo', 'Área o Departamento', 'Salario Base', 'Fecha de Terminación',
            'Nombre de Contacto de Emergencia', 'Teléfono del Contacto',
        ];
        foreach ($headers as $i => $label) {
            $sheet->setCellValue([$i + 1, 1], $label);
        }

        return $spreadsheet;
    }

    public function writeTemplateToPath(string $path): void
    {
        $spreadsheet = $this->buildTemplateSpreadsheet();
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * @return array<string, int>
     */
    private function buildColumnMap(Worksheet $sheet, int $highestCol): array
    {
        $map = [];
        for ($col = 1; $col <= $highestCol; $col++) {
            $label = $this->normalizeHeader((string) $sheet->getCell([$col, self::HEADER_ROW])->getValue());
            if ($label === '') {
                continue;
            }
            $field = self::HEADER_ALIASES[$label] ?? null;
            if ($field) {
                $map[$field] = $col;
            }
        }

        $hasName = isset($map['full_name'])
            || (isset($map['first_name'], $map['last_name']));
        if (! $hasName || ! isset($map['document_number'])) {
            throw new RuntimeException('La fila 1 debe incluir: Nombre completo (o Nombres y Apellidos) y Número de Documento.');
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $map
     * @return array<string, mixed>
     */
    private function rowToPayload(Worksheet $sheet, int $row, array $map): array
    {
        $out = [];
        foreach ($map as $field => $col) {
            $out[$field] = trim((string) $sheet->getCell([$col, $row])->getValue());
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function rowIsEmpty(array $data): bool
    {
        $doc = trim((string) ($data['document_number'] ?? ''));

        return $doc === '';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data): array
    {
        $docType = $this->parseDocumentType((string) ($data['document_type'] ?? 'CC'));
        $gender = $this->parseGender((string) ($data['gender'] ?? ''));
        $contract = $this->parseContractType((string) ($data['contract_type'] ?? ''));

        $municipalityCode = null;
        if (isset($data['municipality_code']) && $data['municipality_code'] !== '') {
            $municipalityCode = preg_replace('/\D/', '', (string) $data['municipality_code']);
        } elseif (isset($data['municipality']) && $data['municipality'] !== '') {
            $mun = trim((string) $data['municipality']);
            if (preg_match('/^\d{5}$/', $mun)) {
                $municipalityCode = $mun;
            } else {
                $found = ColombianMunicipality::query()
                    ->where('municipality_name', 'like', $mun)
                    ->value('municipality_code');
                $municipalityCode = $found;
            }
        }

        $documentNumber = Employee::normalizeDocumentNumber((string) ($data['document_number'] ?? ''));
        if ($documentNumber === '') {
            throw new RuntimeException('Número de documento vacío.');
        }
        if (! preg_match('/^\d{5,15}$/', $documentNumber)) {
            throw new RuntimeException('El documento debe contener solo números (5 a 15 dígitos), sin puntos ni espacios.');
        }

        if (isset($data['full_name']) && trim((string) $data['full_name']) !== '') {
            [$firstName, $lastName] = Employee::splitFullName((string) $data['full_name']);
        } else {
            $firstName = trim((string) ($data['first_name'] ?? ''));
            $lastName = trim((string) ($data['last_name'] ?? ''));
            if ($firstName === '' || $lastName === '') {
                throw new RuntimeException('El nombre completo es obligatorio.');
            }
        }

        $attrs = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'document_type' => $docType->value,
            'document_number' => $documentNumber,
            'birth_date' => $this->parseDate($data['birth_date'] ?? null),
            'gender' => $gender?->value,
            'address' => ($data['address'] ?? '') !== '' ? (string) $data['address'] : null,
            'municipality_code' => $municipalityCode,
            'phone' => ($data['phone'] ?? '') !== '' ? (string) $data['phone'] : null,
            'email' => ($data['email'] ?? '') !== '' ? (string) $data['email'] : null,
            'hired_at' => $this->parseDate($data['hired_at'] ?? null),
            'contract_type' => $contract?->value,
            'job_title' => ($data['job_title'] ?? '') !== '' ? (string) $data['job_title'] : null,
            'department_area' => ($data['department_area'] ?? '') !== '' ? (string) $data['department_area'] : null,
            'base_salary' => $this->parseMoney($data['base_salary'] ?? null),
            'termination_at' => $contract === EmployeeContractType::TerminoFijo
                ? $this->parseDate($data['termination_at'] ?? null)
                : null,
            'emergency_contact_name' => ($data['emergency_contact_name'] ?? '') !== '' ? (string) $data['emergency_contact_name'] : null,
            'emergency_contact_phone' => ($data['emergency_contact_phone'] ?? '') !== '' ? (string) $data['emergency_contact_phone'] : null,
            'is_active' => true,
        ];

        return $attrs;
    }

    private function normalizeHeader(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $v);

        return preg_replace('/\s+/u', ' ', $v) ?? $v;
    }

    private function parseDocumentType(string $raw): EmployeeDocumentType
    {
        $n = $this->normalizeHeader($raw);
        if (str_contains($n, 'extranj')) {
            return EmployeeDocumentType::Ce;
        }
        if (str_contains($n, 'pasaporte')) {
            return EmployeeDocumentType::Pa;
        }
        if ($n === 'ppt') {
            return EmployeeDocumentType::Ppt;
        }

        return EmployeeDocumentType::Cc;
    }

    private function parseGender(string $raw): ?EmployeeGender
    {
        if ($raw === '') {
            return null;
        }
        $n = $this->normalizeHeader($raw);
        if (str_contains($n, 'femen')) {
            return EmployeeGender::Femenino;
        }
        if (str_contains($n, 'masc')) {
            return EmployeeGender::Masculino;
        }
        if (str_contains($n, 'otro')) {
            return EmployeeGender::Otro;
        }

        return EmployeeGender::NoIndica;
    }

    private function parseContractType(string $raw): ?EmployeeContractType
    {
        if ($raw === '') {
            return null;
        }
        $n = $this->normalizeHeader($raw);
        if (str_contains($n, 'indefin')) {
            return EmployeeContractType::TerminoIndefinido;
        }
        if (str_contains($n, 'obra') || str_contains($n, 'labor')) {
            return EmployeeContractType::ObraOLabor;
        }
        if (str_contains($n, 'aprendiz')) {
            return EmployeeContractType::Aprendizaje;
        }
        if (str_contains($n, 'fijo')) {
            return EmployeeContractType::TerminoFijo;
        }

        return null;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $serial = (int) $value;
            $unix = ($serial - 25569) * 86400;

            return gmdate('Y-m-d', (int) $unix);
        }
        $ts = strtotime((string) $value);

        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function parseMoney(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $n = preg_replace('/[^\d.,]/', '', (string) $value);
        $n = str_replace(['.', ','], ['', '.'], $n);
        if ($n === '' || ! is_numeric($n)) {
            return null;
        }

        return number_format((float) $n, 2, '.', '');
    }
}
