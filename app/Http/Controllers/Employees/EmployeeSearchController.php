<?php

namespace App\Http\Controllers\Employees;

use App\Models\Employee;
use App\Support\Disciplinary\FieldDisciplinaryScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeSearchController
{
    public function __invoke(Request $request, FieldDisciplinaryScopeService $scope): JsonResponse
    {
        Gate::authorize('viewAny', Employee::class);

        $actor = $request->user();
        if ($actor && $scope->requiresTerritorialScope($actor) && ! $scope->hasConfiguredScope($actor)) {
            return response()->json([
                'items' => [],
                'scope_blocked' => true,
                'message' => 'Su usuario no tiene ciudades autorizadas. Contacte al administrador.',
            ]);
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        $query = Employee::query()
            ->active()
            ->search($q);

        if ($actor) {
            $scope->applyEmployeeScope($query, $actor);
        }

        $items = $query
            ->with('employeeJobPosition:id,name,is_guarda')
            ->orderBy('document_number')
            ->limit(15)
            ->get([
                'id',
                'document_number',
                'document_type',
                'first_name',
                'last_name',
                'job_title',
                'employee_job_position_id',
                'municipality_code',
            ])
            ->map(fn (Employee $e) => [
                'id' => $e->id,
                'document_number' => $e->document_number,
                'document_type' => $e->document_type?->value ?? 'CC',
                'full_name' => $e->full_name,
                'job_title' => $e->employeeJobPosition?->name ?? $e->job_title ?? '',
                'municipality_code' => $e->municipality_code ?? '',
            ]);

        return response()->json(['items' => $items]);
    }
}
