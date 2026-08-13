{{-- Pila de etapas: la más reciente arriba, Etapa A siempre al final. --}}
<div class="md:col-span-2 xl:col-span-3 space-y-6" data-case-stage-stack>
    @foreach ($overviewStageStack as $stageKey)
        @switch($stageKey)
            @case('d')
                @include('livewire.disciplinary.cases.partials.stage-d-decision', [
                    'case' => $case,
                    'showsDecisionStagePanel' => $showsDecisionStagePanel ?? false,
                    'decisionStageSteps' => $decisionStageSteps ?? collect(),
                    'decisionCurrentStep' => $decisionCurrentStep ?? null,
                    'decisionCurrentStepNumber' => $decisionCurrentStepNumber ?? null,
                    'decisionTotalSteps' => $decisionTotalSteps ?? 6,
                    'decisionBranch' => $decisionBranch ?? null,
                ])
                @break
            @case('c')
                @include('livewire.disciplinary.cases.partials.stage-c-diligence', [
                    'case' => $case,
                    'isDiligenciaActive' => $isDiligenciaActive ?? false,
                    'diligenceReadOnly' => $diligenceReadOnly ?? false,
                    'diligenceStageSteps' => $diligenceStageSteps ?? collect(),
                    'diligenceCurrentStep' => $diligenceCurrentStep ?? null,
                    'diligenceCurrentStepNumber' => $diligenceCurrentStepNumber ?? null,
                    'diligenceTotalSteps' => $diligenceTotalSteps ?? 3,
                    'diligenceAdvanceTargetLabel' => $diligenceAdvanceTargetLabel ?? null,
                    'diligenceSlotDisplay' => $diligenceSlotDisplay,
                    'showDiligenceAdvanceConfirm' => $showDiligenceAdvanceConfirm ?? false,
                ])
                @break
            @case('b')
                @include('livewire.disciplinary.cases.partials.stage-b-citation', [
                    'case' => $case,
                    'citationReadOnly' => $citationReadOnly ?? false,
                    'citationReadiness' => $citationReadiness,
                    'citationRequirementLabels' => $citationRequirementLabels,
                    'citationSlotChoices' => $citationSlotChoices,
                    'citationAdvanceTargetLabel' => $citationAdvanceTargetLabel,
                    'foGj03GenerationChecklist' => $foGj03GenerationChecklist,
                    'foGj03GenerationLabels' => $foGj03GenerationLabels,
                    'notificationPending' => $notificationPending,
                    'notificationCompleted' => $notificationCompleted,
                    'supervisionZones' => $supervisionZones,
                    'citationStageSteps' => $citationStageSteps,
                    'citationCurrentStep' => $citationCurrentStep,
                    'citationCurrentStepNumber' => $citationCurrentStepNumber,
                    'citationTotalSteps' => $citationTotalSteps,
                    'diligenceSlotDisplay' => $diligenceSlotDisplay,
                    'notificationSlotDisplay' => $notificationSlotDisplay,
                    'diligenceDateRequestStatus' => $diligenceDateRequestStatus,
                ])
                @break
            @case('a')
                @include('livewire.disciplinary.cases.partials.stage-a-informe', ['case' => $case])
                @break
        @endswitch
    @endforeach
</div>
