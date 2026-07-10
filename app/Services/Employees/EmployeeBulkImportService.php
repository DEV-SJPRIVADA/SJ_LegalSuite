<?php

namespace App\Services\Employees;

use App\Enums\EmployeeContractType;
use App\Enums\EmployeeDocumentType;
use App\Enums\EmployeeGender;
use App\Enums\EmployeeScope;
use App\Models\Employee;
use App\Models\EmployeeJobPosition;
use App\Support\Employees\EmployeeBulkImportStore;
use App\Support\Employees\EmployeeImportValueNormalizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use RuntimeException;

class EmployeeBulkImportService
{
    public const BATCH_SIZE = 12;

    private const HEADER_ROW = 1;

    public function __construct(
        private readonly EmployeeTerritoryResolver $territoryResolver = new EmployeeTerritoryResolver,
    ) {}

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
        'ciudad de residencia' => 'residence_municipality',
        'ciudad de residencia divipola' => 'residence_municipality',
        'codigo municipio residencia' => 'residence_municipality_code',
        'código municipio residencia' => 'residence_municipality_code',
        'ciudad de labor' => 'work_municipality',
        'ciudad de labor divipola' => 'work_municipality',
        'codigo municipio labor' => 'work_municipality_code',
        'código municipio labor' => 'work_municipality_code',
        'ciudad / municipio' => 'work_municipality',
        'ciudad municipio' => 'work_municipality',
        'municipio' => 'work_municipality',
        'codigo municipio' => 'work_municipality_code',
        'código municipio' => 'work_municipality_code',
        'codigo municipio divipola' => 'work_municipality_code',
        'código municipio divipola' => 'work_municipality_code',
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
        'rol empleado' => 'employee_scope',
        'area o departamento' => 'employee_scope',
        'área o departamento' => 'employee_scope',
        'area departamento' => 'employee_scope',
        'salario base' => 'base_salary',
        'nombre de contacto de emergencia' => 'emergency_contact_name',
        'contacto emergencia' => 'emergency_contact_name',
        'telefono del contacto' => 'emergency_contact_phone',
        'teléfono del contacto' => 'emergency_contact_phone',
        'telefono contacto emergencia' => 'emergency_contact_phone',
        'empleado activo' => 'is_active',
        'activo' => 'is_active',
        'estado' => 'is_active',
    ];

    /**
     * @return array{inserted:int, updated:int, skipped:int, errors:array<int, string>}
     */
    public function importFromPath(string $absolutePath): array
    {
        $token = EmployeeBulkImportStore::createFromUploadedFile(0, $absolutePath);
        try {
            $this->prepareImport($token);
            $this->processAllBatches($token);

            return $this->resultFromToken($token);
        } catch (\Throwable $e) {
            EmployeeBulkImportStore::markFailed($token, $e->getMessage());
            throw $e;
        }
    }

    public function prepareImport(string $token): void
    {
        $path = EmployeeBulkImportStore::spreadsheetPath($token);
        $reader = new XlsxReader;
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = (int) $sheet->getHighestDataRow();
            $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            $map = $this->buildColumnMap($sheet, $highestCol);
            EmployeeBulkImportStore::saveColumnMap($token, $map);

            $totalRows = max(0, $highestRow - self::HEADER_ROW);

            EmployeeBulkImportStore::updateMeta($token, [
                'status' => EmployeeBulkImportStore::STATUS_PENDING,
                'total_rows' => $totalRows,
                'next_row' => self::HEADER_ROW + 1,
                'highest_row' => $highestRow,
                'highest_col' => $highestCol,
            ]);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    public function processAllBatches(string $token, int $batchSize = self::BATCH_SIZE): void
    {
        while ($this->advanceImport($token, $batchSize, 1)) {
            // Procesa lote a lote hasta completar.
        }
    }

    /**
     * Avanza la importación en uno o más lotes. Devuelve false cuando terminó o no hay más trabajo.
     */
    public function advanceImport(
        string $token,
        int $batchSize = self::BATCH_SIZE,
        int $maxBatches = 1,
    ): bool {
        $meta = EmployeeBulkImportStore::meta($token);
        if ($meta === null) {
            throw new RuntimeException('Sesión de importación no encontrada.');
        }

        $status = (string) ($meta['status'] ?? EmployeeBulkImportStore::STATUS_PENDING);

        if ($status === EmployeeBulkImportStore::STATUS_COMPLETED) {
            return false;
        }

        if ($status === EmployeeBulkImportStore::STATUS_FAILED) {
            return false;
        }

        if ($status === EmployeeBulkImportStore::STATUS_PENDING && EmployeeBulkImportStore::columnMap($token) === []) {
            $this->prepareImport($token);

            return true;
        }

        EmployeeBulkImportStore::updateMeta($token, [
            'status' => EmployeeBulkImportStore::STATUS_PROCESSING,
            'started_at' => $meta['started_at'] ?? now()->toIso8601String(),
        ]);

        $path = EmployeeBulkImportStore::spreadsheetPath($token);
        $reader = new XlsxReader;
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $map = EmployeeBulkImportStore::columnMap($token);
            $highestRow = (int) (EmployeeBulkImportStore::meta($token)['highest_row'] ?? $sheet->getHighestDataRow());

            for ($batch = 0; $batch < $maxBatches; $batch++) {
                $meta = EmployeeBulkImportStore::meta($token);
                $nextRow = (int) ($meta['next_row'] ?? self::HEADER_ROW + 1);

                if ($nextRow > $highestRow) {
                    EmployeeBulkImportStore::updateMeta($token, [
                        'status' => EmployeeBulkImportStore::STATUS_COMPLETED,
                    ]);

                    return false;
                }

                $this->processBatchRows($token, $sheet, $map, $nextRow, $highestRow, $batchSize);
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        $meta = EmployeeBulkImportStore::meta($token);

        return ($meta['status'] ?? '') !== EmployeeBulkImportStore::STATUS_COMPLETED;
    }

    /**
     * @param  array<string, int>  $map
     */
    private function processBatchRows(
        string $token,
        Worksheet $sheet,
        array $map,
        int $startRow,
        int $highestRow,
        int $batchSize,
    ): void {
        $meta = EmployeeBulkImportStore::meta($token);
        $inserted = (int) ($meta['inserted'] ?? 0);
        $updated = (int) ($meta['updated'] ?? 0);
        $skipped = (int) ($meta['skipped'] ?? 0);
        $processed = (int) ($meta['processed_rows'] ?? 0);
        $errorsCount = (int) ($meta['errors_count'] ?? 0);

        $endRow = min($startRow + $batchSize - 1, $highestRow);

        DB::beginTransaction();
        try {
            for ($row = $startRow; $row <= $endRow; $row++) {
                $data = $this->rowToPayload($sheet, $row, $map);
                $processed++;

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
                    EmployeeBulkImportStore::appendError($token, $row, $e->getMessage());
                    $errorsCount++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        EmployeeBulkImportStore::updateMeta($token, [
            'next_row' => $endRow + 1,
            'processed_rows' => $processed,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors_count' => $errorsCount,
        ]);
    }

    /**
     * @return array{
     *     status:string,
     *     percent:int,
     *     processed_rows:int,
     *     total_rows:int,
     *     inserted:int,
     *     updated:int,
     *     skipped:int,
     *     errors_count:int,
     *     eta_seconds:?int,
     *     eta_label:string,
     *     phase_label:string,
     *     started_at:?string
     * }
     */
    public function progressSnapshot(string $token): array
    {
        $meta = EmployeeBulkImportStore::meta($token) ?? [];
        $status = (string) ($meta['status'] ?? EmployeeBulkImportStore::STATUS_PENDING);
        $processed = (int) ($meta['processed_rows'] ?? 0);
        $total = max(0, (int) ($meta['total_rows'] ?? 0));
        $denominator = max(1, $total);
        $percent = $status === EmployeeBulkImportStore::STATUS_COMPLETED
            ? 100
            : min(99, (int) floor(($processed / $denominator) * 100));

        $etaSeconds = null;
        if ($status === EmployeeBulkImportStore::STATUS_PROCESSING && $processed > 0 && filled($meta['started_at'] ?? null)) {
            $startedAt = Carbon::parse((string) $meta['started_at']);
            $elapsed = max(1, $startedAt->diffInSeconds(now()));
            $rate = $processed / $elapsed;
            $remaining = max(0, $total - $processed);
            $etaSeconds = (int) ceil($remaining / max($rate, 0.5));
        }

        return [
            'status' => $status,
            'percent' => $percent,
            'processed_rows' => $processed,
            'total_rows' => $total,
            'inserted' => (int) ($meta['inserted'] ?? 0),
            'updated' => (int) ($meta['updated'] ?? 0),
            'skipped' => (int) ($meta['skipped'] ?? 0),
            'errors_count' => (int) ($meta['errors_count'] ?? 0),
            'eta_seconds' => $etaSeconds,
            'eta_label' => $this->formatEtaLabel($etaSeconds, $status),
            'phase_label' => $this->phaseLabel($status),
            'started_at' => filled($meta['started_at'] ?? null) ? (string) $meta['started_at'] : null,
        ];
    }

    /**
     * @return array{inserted:int, updated:int, skipped:int, errors:array<int, string>}
     */
    public function resultFromToken(string $token): array
    {
        $meta = EmployeeBulkImportStore::meta($token) ?? [];

        return [
            'inserted' => (int) ($meta['inserted'] ?? 0),
            'updated' => (int) ($meta['updated'] ?? 0),
            'skipped' => (int) ($meta['skipped'] ?? 0),
            'errors' => EmployeeBulkImportStore::errors($token),
        ];
    }

    public function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Empleados');
        $headers = [
            'Nombre completo', 'Tipo de Documento', 'Número de Documento',
            'Fecha de Nacimiento', 'Género', 'Dirección de Residencia',
            'Ciudad de residencia', 'Ciudad de labor',
            'Teléfono Celular', 'Correo Electrónico', 'Fecha de Ingreso', 'Tipo de Contrato',
            'Cargo', 'Área o departamento', 'Salario Base',
            'Nombre de Contacto de Emergencia', 'Teléfono del Contacto',
            'Empleado activo',
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

        if (! isset($map['job_title'])) {
            throw new RuntimeException('La fila 1 debe incluir la columna Cargo.');
        }

        $hasWorkCity = isset($map['work_municipality_code']) || isset($map['work_municipality']);
        $hasResidenceCity = isset($map['residence_municipality_code']) || isset($map['residence_municipality']);

        if (! $hasWorkCity) {
            throw new RuntimeException('La fila 1 debe incluir Ciudad de labor.');
        }

        if (! $hasResidenceCity) {
            throw new RuntimeException('La fila 1 debe incluir Ciudad de residencia.');
        }

        if (! isset($map['hired_at'])) {
            throw new RuntimeException('La fila 1 debe incluir Fecha de Ingreso.');
        }

        if (! isset($map['contract_type'])) {
            throw new RuntimeException('La fila 1 debe incluir Tipo de Contrato.');
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

        $contract = EmployeeContractType::tryFromImportLabel((string) ($data['contract_type'] ?? ''));
        if ($contract === null) {
            $rawContract = trim((string) ($data['contract_type'] ?? ''));
            if ($rawContract === '') {
                throw new RuntimeException('El tipo de contrato es obligatorio.');
            }

            throw new RuntimeException("Tipo de contrato no reconocido: {$rawContract}.");
        }

        $hiredAt = $this->parseDate($data['hired_at'] ?? null);
        if ($hiredAt === null) {
            throw new RuntimeException('La fecha de ingreso es obligatoria.');
        }

        $residenceTerritory = $this->territoryResolver->resolve(
            $data['residence_municipality_code'] ?? null,
            $data['residence_municipality'] ?? null,
            'residencia'
        );

        $workTerritory = $this->territoryResolver->resolve(
            $data['work_municipality_code'] ?? null,
            $data['work_municipality'] ?? null,
            'labor'
        );

        $cargoLabel = trim((string) ($data['job_title'] ?? ''));
        if ($cargoLabel === '') {
            throw new RuntimeException('El cargo es obligatorio.');
        }

        $employeeJobPositionId = EmployeeJobPosition::resolveIdFromLabel($cargoLabel);
        if ($employeeJobPositionId === null) {
            throw new RuntimeException("Cargo no reconocido en el catálogo: {$cargoLabel}.");
        }

        $position = EmployeeJobPosition::query()->findOrFail($employeeJobPositionId);

        $employeeScope = EmployeeScope::tryFromLabel((string) ($data['employee_scope'] ?? ''));
        if ($employeeScope === null) {
            $employeeScope = $position->employee_scope;
        }

        if ($employeeScope !== $position->employee_scope) {
            throw new RuntimeException(
                "El área o departamento «{$employeeScope->value}» no coincide con el cargo «{$position->name}» ({$position->employee_scope->value})."
            );
        }

        if ($position->is_guarda && $workTerritory['municipality_code'] === null) {
            throw new RuntimeException('Los cargos de guarda requieren municipio de labor (no solo departamento).');
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

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'document_type' => $docType->value,
            'document_number' => $documentNumber,
            'birth_date' => $this->parseDate($data['birth_date'] ?? null),
            'gender' => $gender?->value,
            'address' => ($data['address'] ?? '') !== '' ? (string) $data['address'] : null,
            'residence_municipality_code' => $residenceTerritory['municipality_code'],
            'residence_department_code' => $residenceTerritory['department_code'],
            'municipality_code' => $workTerritory['municipality_code'],
            'work_department_code' => $workTerritory['department_code'],
            'phone' => EmployeeImportValueNormalizer::nullableContact(
                ($data['phone'] ?? '') !== '' ? (string) $data['phone'] : null
            ),
            'email' => EmployeeImportValueNormalizer::nullableContact(
                ($data['email'] ?? '') !== '' ? (string) $data['email'] : null
            ),
            'hired_at' => $hiredAt,
            'contract_type' => $contract->value,
            'employee_job_position_id' => $position->id,
            'job_title' => $position->name,
            'employee_scope' => $employeeScope->value,
            'department_area' => $employeeScope->label(),
            'base_salary' => $this->parseMoney($data['base_salary'] ?? null),
            'termination_at' => null,
            'emergency_contact_name' => EmployeeImportValueNormalizer::nullableContact(
                ($data['emergency_contact_name'] ?? '') !== '' ? (string) $data['emergency_contact_name'] : null
            ),
            'emergency_contact_phone' => EmployeeImportValueNormalizer::nullableContact(
                ($data['emergency_contact_phone'] ?? '') !== '' ? (string) $data['emergency_contact_phone'] : null
            ),
            'is_active' => $this->parseActiveFlag($data['is_active'] ?? null),
        ];
    }

    private function parseActiveFlag(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $normalized = mb_strtolower(trim((string) $value));
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $normalized);

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'inactivo', 'inactiva'], true)) {
            return false;
        }

        return true;
    }

    private function phaseLabel(string $status): string
    {
        return match ($status) {
            EmployeeBulkImportStore::STATUS_PENDING => 'Validando archivo…',
            EmployeeBulkImportStore::STATUS_PROCESSING => 'Importando empleados…',
            EmployeeBulkImportStore::STATUS_COMPLETED => 'Importación finalizada',
            EmployeeBulkImportStore::STATUS_FAILED => 'Importación fallida',
            default => 'Cargando…',
        };
    }

    private function formatEtaLabel(?int $etaSeconds, string $status): string
    {
        if ($status === EmployeeBulkImportStore::STATUS_COMPLETED) {
            return 'Completado';
        }

        if ($status === EmployeeBulkImportStore::STATUS_FAILED) {
            return '—';
        }

        if ($etaSeconds === null) {
            return 'Calculando tiempo…';
        }

        if ($etaSeconds <= 3) {
            return 'Finalizando…';
        }

        if ($etaSeconds < 60) {
            return "Aprox. {$etaSeconds} s restantes";
        }

        $minutes = intdiv($etaSeconds, 60);
        $seconds = $etaSeconds % 60;

        return "Aprox. {$minutes} min {$seconds} s restantes";
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
        if ($n === 'mujer' || str_contains($n, 'femen')) {
            return EmployeeGender::Femenino;
        }
        if ($n === 'hombre' || str_contains($n, 'masc')) {
            return EmployeeGender::Masculino;
        }
        if (str_contains($n, 'otro')) {
            return EmployeeGender::Otro;
        }

        return EmployeeGender::NoIndica;
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
