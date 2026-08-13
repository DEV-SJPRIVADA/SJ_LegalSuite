<?php

namespace App\Services\Employees;

use App\Models\ColombianMunicipality;
use RuntimeException;

class EmployeeTerritoryResolver
{
    /**
     * @return array{municipality_code: ?string, department_code: ?string}
     */
    public function resolve(mixed $codeValue, mixed $nameValue, string $label): array
    {
        $input = trim((string) ($nameValue ?? ''));
        if ($input === '' && $codeValue !== null && $codeValue !== '') {
            $input = trim((string) $codeValue);
        }

        if ($input === '') {
            throw new RuntimeException("La ciudad o departamento de {$label} es obligatorio.");
        }

        $digits = preg_replace('/\D/', '', $input) ?? '';
        if (strlen($digits) === 5) {
            $this->assertMunicipalityExists($digits, $label);

            $departmentCode = ColombianMunicipality::query()
                ->where('municipality_code', $digits)
                ->value('department_code');

            return [
                'municipality_code' => $digits,
                'department_code' => $departmentCode ? (string) $departmentCode : null,
            ];
        }

        $normalized = $this->normalize($input);

        $municipality = ColombianMunicipality::query()
            ->get(['municipality_code', 'municipality_name', 'department_code', 'department_name'])
            ->first(function (ColombianMunicipality $row) use ($normalized): bool {
                return $this->normalize((string) $row->municipality_name) === $normalized;
            });

        if ($municipality instanceof ColombianMunicipality) {
            return [
                'municipality_code' => (string) $municipality->municipality_code,
                'department_code' => (string) $municipality->department_code,
            ];
        }

        $departmentCode = $this->resolveDepartmentCode($normalized);
        if ($departmentCode !== null) {
            return [
                'municipality_code' => null,
                'department_code' => $departmentCode,
            ];
        }

        $fuzzyMunicipality = ColombianMunicipality::query()
            ->where('municipality_name', 'like', '%'.$input.'%')
            ->orderBy('municipality_name')
            ->first(['municipality_code', 'department_code']);

        if ($fuzzyMunicipality instanceof ColombianMunicipality) {
            return [
                'municipality_code' => (string) $fuzzyMunicipality->municipality_code,
                'department_code' => (string) $fuzzyMunicipality->department_code,
            ];
        }

        throw new RuntimeException("No se reconoce la ciudad o departamento de {$label}: {$input}.");
    }

    private function resolveDepartmentCode(string $normalized): ?string
    {
        $departments = ColombianMunicipality::query()
            ->select(['department_code', 'department_name'])
            ->distinct()
            ->orderBy('department_name')
            ->get();

        foreach ($departments as $department) {
            if ($this->normalize((string) $department->department_name) === $normalized) {
                return (string) $department->department_code;
            }
        }

        foreach ($departments as $department) {
            $deptNorm = $this->normalize((string) $department->department_name);
            if (str_contains($deptNorm, $normalized) || str_contains($normalized, $deptNorm)) {
                return (string) $department->department_code;
            }
        }

        return null;
    }

    private function assertMunicipalityExists(string $code, string $label): void
    {
        if (! ColombianMunicipality::query()->where('municipality_code', $code)->exists()) {
            throw new RuntimeException("Código de municipio de {$label} no válido: {$code}.");
        }
    }

    private function normalize(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $v);

        return preg_replace('/\s+/u', ' ', $v) ?? $v;
    }
}
