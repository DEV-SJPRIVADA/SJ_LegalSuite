<?php

namespace App\Services\Integration;

use App\Enums\EmployeeContractType;
use App\Enums\EmployeeDocumentType;
use App\Enums\EmployeeGender;
use App\Enums\EmployeeScope;
use App\Models\ColombianMunicipality;
use App\Models\Employee;
use App\Models\EmployeeJobPosition;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Consumidor de la integracion de empleados desde SJ StatFlow.
 *
 * Reglas:
 * - Upsert por `document_number` (unique). Nunca cambia `id`, nunca borra.
 * - Solo guarda si el modelo quedo "dirty" (no hay columna de sincronizacion).
 * - Un error por item no aborta el lote.
 */
class EmployeeSyncService
{
    /** @var array<string, EmployeeJobPosition> */
    private array $positionCache = [];

    /** @var array<string, string|null> */
    private array $municipalityCache = [];

    private const GUARDA_KEYWORDS = ['guarda', 'vigilante', 'escolta', 'motorizado', 'canino'];

    private const OPERATIVO_KEYWORDS = ['supervisor', 'operador', 'programador'];

    /**
     * @param  array<int, mixed>  $records
     * @return array{created: int, updated: int, skipped: int, errors: array<int, array{document_number: string, reason: string}>}
     */
    public function ingest(array $records): array
    {
        $summary = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $documentNumber = (string) ($record['document_number'] ?? '');

            try {
                $status = DB::transaction(fn (): string => $this->ingestOne($record));
            } catch (Throwable $exception) {
                $summary['errors'][] = [
                    'document_number' => $documentNumber,
                    'reason' => Str::limit($exception->getMessage(), 200),
                ];

                continue;
            }

            $summary[$status]++;
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function ingestOne(array $record): string
    {
        $document = preg_replace('/\D+/', '', (string) ($record['document_number'] ?? '')) ?? '';

        if (strlen($document) < 5) {
            throw new RuntimeException('Numero de documento invalido.');
        }

        $document = mb_substr($document, 0, 32);
        $data = $this->map($record);
        $scope = $data['_scope'] ?? null;
        unset($data['_scope']);

        $employee = Employee::withTrashed()->where('document_number', $document)->first();

        if ($employee === null) {
            $data['document_number'] = $document;
            $data['first_name'] = $data['first_name'] ?? '-';
            $data['last_name'] = $data['last_name'] ?? '-';
            $data['is_active'] = $data['is_active'] ?? true;

            if ($scope instanceof EmployeeScope && ! isset($data['employee_scope'])) {
                $data['employee_scope'] = $scope;
            }

            Employee::query()->create($data);

            return 'created';
        }

        if ($employee->trashed()) {
            if (($data['is_active'] ?? false) !== true) {
                return 'skipped';
            }

            $employee->restore();
        }

        $employee->fill($data);

        if ($scope instanceof EmployeeScope && $employee->employee_scope === null) {
            $employee->employee_scope = $scope;
        }

        if (! $employee->isDirty()) {
            return 'skipped';
        }

        $employee->save();

        return 'updated';
    }

    /**
     * Mapea el payload de StatFlow a columnas de `employees`.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function map(array $record): array
    {
        $data = [];

        $externalId = $record['id'] ?? null;

        if (is_scalar($externalId)) {
            $data['external_id'] = mb_substr((string) $externalId, 0, 64);
        }

        $data['document_type'] = EmployeeDocumentType::fromStatflowCode(
            is_string($record['document_type'] ?? null) ? $record['document_type'] : null
        );

        $firstName = $this->cleanName($record['first_name'] ?? null);
        if ($firstName !== null) {
            $data['first_name'] = mb_substr($firstName, 0, 100);
        }

        $lastName = $this->cleanName($record['last_name'] ?? null);
        if ($lastName !== null) {
            $data['last_name'] = mb_substr($lastName, 0, 100);
        }

        $gender = $this->gender($record['gender'] ?? null);
        if ($gender !== null) {
            $data['gender'] = $gender;
        }

        foreach (['birth_date' => 'birth_date', 'hired_at' => 'hired_at', 'termination_at' => 'termination_at'] as $source => $column) {
            if (array_key_exists($source, $record)) {
                $data[$column] = $this->date($record[$source]);
            }
        }

        if (array_key_exists('address', $record)) {
            $data['address'] = $this->nullableString($record['address'], 255);
        }

        foreach ([
            'residence_municipality_code' => 'residence_department_code',
            'municipality_code' => 'work_department_code',
        ] as $codeKey => $departmentKey) {
            if (! array_key_exists($codeKey, $record)) {
                continue;
            }

            [$code, $department] = $this->municipality($record[$codeKey]);
            $data[$codeKey] = $code;
            $data[$departmentKey] = $department;
        }

        $phone = $this->nullableString($record['phone'] ?? null, 32);
        if ($phone !== null) {
            $data['phone'] = $phone;
        }

        $email = $this->nullableString($record['email'] ?? null, 150);
        if ($email !== null) {
            $data['email'] = $email;
        }

        $contract = EmployeeContractType::tryFromImportLabel((string) ($record['contract_type'] ?? ''));
        if ($contract !== null) {
            $data['contract_type'] = $contract;
        }

        $positionName = $this->cleanName($record['job_title'] ?? null);
        if ($positionName !== null) {
            $positionName = mb_substr($positionName, 0, 120);
            $data['job_title'] = $positionName;
            $position = $this->resolvePosition($positionName);

            if ($position !== null) {
                $data['employee_job_position_id'] = $position->id;
                $data['_scope'] = $position->employee_scope;
            }
        }

        $salary = $record['base_salary'] ?? null;
        if ($salary !== null && $salary !== '' && is_numeric($salary)) {
            $data['base_salary'] = number_format((float) $salary, 2, '.', '');
        }

        if (array_key_exists('is_active', $record)) {
            $data['is_active'] = (bool) $record['is_active'];
        }

        return $data;
    }

    /**
     * Upsert del catalogo de cargos por nombre normalizado.
     */
    private function resolvePosition(string $name): ?EmployeeJobPosition
    {
        $normalized = EmployeeJobPosition::normalizeName($name);

        if ($normalized === '') {
            return null;
        }

        if (array_key_exists($normalized, $this->positionCache)) {
            return $this->positionCache[$normalized];
        }

        $existing = EmployeeJobPosition::query()
            ->get(['id', 'slug', 'name', 'is_guarda', 'employee_scope'])
            ->first(fn (EmployeeJobPosition $position): bool => EmployeeJobPosition::normalizeName((string) $position->name) === $normalized);

        if ($existing instanceof EmployeeJobPosition) {
            return $this->positionCache[$normalized] = $existing;
        }

        $isGuarda = $this->containsAny($normalized, self::GUARDA_KEYWORDS);
        $scope = $isGuarda || $this->containsAny($normalized, self::OPERATIVO_KEYWORDS)
            ? EmployeeScope::Operativo
            : EmployeeScope::Administrativo;

        // Si el cargo ya existe no se sobrescribe su clasificacion manual:
        // solo los cargos nuevos reciben is_guarda/employee_scope derivados.
        $position = EmployeeJobPosition::query()->create([
            'slug' => $this->uniqueSlug($name),
            'name' => mb_substr($name, 0, 120),
            'is_guarda' => $isGuarda,
            'employee_scope' => $scope,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        return $this->positionCache[$normalized] = $position;
    }

    private function uniqueSlug(string $name): string
    {
        $base = EmployeeJobPosition::slugFromLabel($name);
        $slug = $base;
        $suffix = 2;

        while (EmployeeJobPosition::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function municipality(mixed $raw): array
    {
        $digits = preg_replace('/\D+/', '', (string) ($raw ?? '')) ?? '';

        if (strlen($digits) !== 5) {
            return [null, null];
        }

        if (! array_key_exists($digits, $this->municipalityCache)) {
            $department = ColombianMunicipality::query()
                ->where('municipality_code', $digits)
                ->value('department_code');

            $this->municipalityCache[$digits] = $department !== null ? (string) $department : null;
        }

        $department = $this->municipalityCache[$digits];

        // Codigo inexistente: se guarda null para no violar la FK.
        return $department === null ? [null, null] : [$digits, $department];
    }

    private function gender(mixed $raw): ?EmployeeGender
    {
        return match (mb_strtolower(trim((string) ($raw ?? '')))) {
            'masculino' => EmployeeGender::Masculino,
            'femenino' => EmployeeGender::Femenino,
            default => null,
        };
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }

        if ((int) $date->year >= 9000) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    private function cleanName(mixed $value): ?string
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) ($value ?? '')) ?? '');

        return $name === '' ? null : $name;
    }

    private function nullableString(mixed $value, int $max): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : mb_substr($string, 0, $max);
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
