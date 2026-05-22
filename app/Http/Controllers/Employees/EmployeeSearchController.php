<?php

namespace App\Http\Controllers\Employees;

use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeSearchController
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Employee::class);

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        $items = Employee::query()
            ->active()
            ->search($q)
            ->orderBy('document_number')
            ->limit(15)
            ->get([
                'id',
                'document_number',
                'document_type',
                'first_name',
                'last_name',
                'job_title',
                'municipality_code',
            ])
            ->map(fn (Employee $e) => [
                'id' => $e->id,
                'document_number' => $e->document_number,
                'document_type' => $e->document_type?->value ?? 'CC',
                'full_name' => $e->full_name,
                'job_title' => $e->job_title ?? '',
                'municipality_code' => $e->municipality_code ?? '',
            ]);

        return response()->json(['items' => $items]);
    }
}
