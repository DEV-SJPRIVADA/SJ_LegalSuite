<?php

namespace App\Http\Controllers\Disciplinary;

use App\Http\Controllers\Controller;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\DisciplinaryDashboardService;
use Illuminate\Http\JsonResponse;

class DisciplinaryDashboardController extends Controller
{
    public function __construct(
        private readonly DisciplinaryDashboardService $dashboard,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->authorize('viewDashboard', DisciplinaryCase::class);

        return response()->json([
            'kpis' => $this->dashboard->kpis(),
            'workflow_donuts' => $this->dashboard->workflowStageDonuts(),
            'by_fault' => $this->dashboard->casesByFault(),
            'by_city' => $this->dashboard->casesByCity(),
            'lawyer_workload' => $this->dashboard->lawyerWorkload(),
        ]);
    }
}
