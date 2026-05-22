<?php

namespace App\Http\Controllers\Disciplinary;

use App\Enums\Disciplinary\CaseBucket;
use App\Enums\Disciplinary\CaseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Disciplinary\StoreDisciplinaryCaseRequest;
use App\Http\Requests\Disciplinary\TransitionStageRequest;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\DisciplinaryCaseService;
use App\Services\Disciplinary\DisciplinaryWorkflowService;
use App\Workflow\Disciplinary\TransitionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisciplinaryCaseController extends Controller
{
    public function __construct(
        private readonly DisciplinaryCaseService $cases,
        private readonly DisciplinaryWorkflowService $workflow,
    ) {}

    /**
     * Listado optimizado con filtros combinables y paginación.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DisciplinaryCase::class);

        $query = DisciplinaryCase::query()
            ->forDisciplinaryActor($request->user())
            ->with(['employee:id,first_name,last_name,document_number', 'assignedLawyer:id,name'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')))
            ->when($request->filled('status'), fn ($q) => $q->withStatus(CaseStatus::from($request->string('status'))))
            ->when(
                $request->filled('bucket'),
                fn ($q) => $q->bucket(CaseBucket::from($request->string('bucket')))
            )
            ->when($request->filled('lawyer_id'), fn ($q) => $q->assignedTo((int) $request->integer('lawyer_id')))
            ->when($request->filled('city'), fn ($q) => $q->inCity($request->string('city')))
            ->when($request->filled('fault_id'), fn ($q) => $q->withFault((int) $request->integer('fault_id')))
            ->when($request->filled('from') && $request->filled('to'), function ($q) use ($request) {
                $q->openedBetween($request->date('from'), $request->date('to'));
            })
            ->orderByDesc('opened_at');

        return response()->json(
            $query->paginate($request->integer('per_page', 25))
        );
    }

    public function store(StoreDisciplinaryCaseRequest $request): JsonResponse
    {
        $case = $this->cases->create(
            $request->user(),
            $request->only(['employee_id', 'assigned_lawyer_id', 'city', 'municipality_code', 'sede', 'opened_at', 'summary']),
            $request->input('faults', []),
        );

        return response()->json($case->load(['faults', 'employee']), 201);
    }

    public function show(DisciplinaryCase $case): JsonResponse
    {
        $this->authorize('view', $case);

        return response()->json(
            $case->load([
                'employee',
                'reporter:id,name',
                'assignedLawyer:id,name',
                'faults',
                'stages',
                'documents',
                'actions.user:id,name',
            ])
        );
    }

    public function transition(TransitionStageRequest $request, DisciplinaryCase $case): JsonResponse
    {
        $to = CaseStatus::from($request->string('to'));

        $case = $this->workflow->transition(
            $case,
            $to,
            $request->user(),
            $request->input('note'),
            $request->input('context', []),
            scheduledAt: $request->date('scheduled_at'),
            deadlineAt: $request->date('deadline_at'),
        );

        return response()->json($case);
    }

    public function allowedTransitions(DisciplinaryCase $case): JsonResponse
    {
        $this->authorize('view', $case);

        return response()->json([
            'current' => [
                'value' => $case->current_status->value,
                'label' => $case->current_status->label(),
            ],
            'allowed' => collect(TransitionMap::allowedFrom($case->current_status))
                ->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])
                ->values(),
        ]);
    }
}
