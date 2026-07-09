@php
    $stageVars = [
        'case' => $case,
        'insideStageModal' => true,
        'stageModalReadOnly' => $stageModalReadOnly ?? false,
        'citationReadOnly' => $citationReadOnly ?? false,
        'citationReadiness' => $citationReadiness,
        'citationRequirementLabels' => $citationRequirementLabels,
        'citationSlotChoices' => $citationSlotChoices,
        'citationAdvanceTargetLabel' => $citationAdvanceTargetLabel,
        'foGj03GenerationChecklist' => $foGj03GenerationChecklist,
        'foGj03GenerationLabels' => $foGj03GenerationLabels,
        'notificationPending' => $notificationPending,
        'notificationCompleted' => $notificationCompleted,
        'supervisorCandidates' => $supervisorCandidates,
        'citationStageSteps' => $citationStageSteps,
        'citationCurrentStep' => $citationCurrentStep,
        'citationCurrentStepNumber' => $citationCurrentStepNumber,
        'citationTotalSteps' => $citationTotalSteps,
        'diligenceSlotDisplay' => $diligenceSlotDisplay,
        'notificationSlotDisplay' => $notificationSlotDisplay,
        'diligenceDateRequestStatus' => $diligenceDateRequestStatus,
        'isDiligenciaActive' => $isDiligenciaActive ?? false,
        'diligenceReadOnly' => $diligenceReadOnly ?? false,
        'diligenceStageSteps' => $diligenceStageSteps ?? collect(),
        'diligenceCurrentStep' => $diligenceCurrentStep ?? null,
        'diligenceCurrentStepNumber' => $diligenceCurrentStepNumber ?? null,
        'diligenceTotalSteps' => $diligenceTotalSteps ?? 3,
        'diligenceAdvanceTargetLabel' => $diligenceAdvanceTargetLabel ?? null,
        'showDiligenceAdvanceConfirm' => $showDiligenceAdvanceConfirm ?? false,
        'showsDecisionStagePanel' => ($showsDecisionStagePanel ?? false) || ($showsDecisionStageReadOnly ?? false),
        'showsDecisionStageReadOnly' => $showsDecisionStageReadOnly ?? false,
        'decisionReadOnly' => $decisionReadOnly ?? false,
        'decisionStageSteps' => $decisionStageSteps ?? collect(),
        'decisionCurrentStep' => $decisionCurrentStep ?? null,
        'decisionCurrentStepNumber' => $decisionCurrentStepNumber ?? null,
        'decisionTotalSteps' => $decisionTotalSteps ?? 6,
        'decisionBranch' => $decisionBranch ?? null,
    ];
@endphp

@switch($openStageModal)
    @case('d')
        @include('livewire.disciplinary.cases.partials.stage-d-decision', $stageVars)
        @break
    @case('c')
        @include('livewire.disciplinary.cases.partials.stage-c-diligence', $stageVars)
        @break
    @case('b')
        @include('livewire.disciplinary.cases.partials.stage-b-citation', $stageVars)
        @break
    @case('a')
        @include('livewire.disciplinary.cases.partials.stage-a-informe', $stageVars)
        @break
@endswitch
