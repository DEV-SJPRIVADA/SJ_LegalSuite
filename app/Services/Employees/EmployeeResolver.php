<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\User;
use App\Support\Disciplinary\FieldDisciplinaryScopeService;

class EmployeeResolver
{
    public function resolveByDocument(string $documentNumber): Employee
    {
        $employee = $this->resolveByDocumentUnchecked($documentNumber);

        return $employee;
    }

    public function resolveForDisciplinaryActor(User $actor, ?int $employeeId, string $documentNumber): Employee
    {
        $employee = $this->resolveById($employeeId, $documentNumber);
        app(FieldDisciplinaryScopeService::class)->assertEmployeeInScope($actor, $employee);

        return $employee;
    }

    private function resolveByDocumentUnchecked(string $documentNumber): Employee
    {
        $normalized = Employee::normalizeDocumentNumber($documentNumber);

        if ($normalized === '') {
            throw new \InvalidArgumentException('El número de documento es obligatorio (solo dígitos).');
        }

        if (! preg_match('/^\d{5,15}$/', $normalized)) {
            throw new \InvalidArgumentException('El documento debe contener solo números, sin puntos, espacios ni letras.');
        }

        $employee = Employee::query()
            ->where('document_number', $normalized)
            ->first();

        if (! $employee) {
            throw new \InvalidArgumentException(
                'No hay empleado registrado con ese documento. Regístrelo en Empleados o use la carga masiva.'
            );
        }

        if (! $employee->is_active) {
            throw new \InvalidArgumentException('El empleado está inactivo en la base de datos.');
        }

        return $employee;
    }

    public function resolveById(?int $employeeId, string $documentNumber): Employee
    {
        if ($employeeId) {
            $employee = Employee::query()->find($employeeId);
            if ($employee && Employee::normalizeDocumentNumber($employee->document_number) === Employee::normalizeDocumentNumber($documentNumber)) {
                if (! $employee->is_active) {
                    throw new \InvalidArgumentException('El empleado está inactivo en la base de datos.');
                }

                return $employee;
            }
        }

        return $this->resolveByDocument($documentNumber);
    }
}
