{{-- Modales FO-GJ montados siempre en el detalle (z-85+) para no perder estado al cerrar tarjetas. --}}
@include('livewire.disciplinary.cases.partials.stage-b-citation-modals', [
    'case' => $case,
    'citationAdvanceTargetLabel' => $citationAdvanceTargetLabel ?? null,
    'supervisionZones' => $supervisionZones ?? collect(),
    'citationReadOnly' => $citationReadOnly ?? false,
])

@include('livewire.disciplinary.cases.partials.stage-c-diligence-modals', ['case' => $case])

@include('livewire.disciplinary.cases.partials.stage-c-diligence-confirm-modals', [
    'diligenceAdvanceTargetLabel' => $diligenceAdvanceTargetLabel ?? null,
    'showDiligenceAdvanceConfirm' => $showDiligenceAdvanceConfirm ?? false,
])

@include('livewire.disciplinary.cases.partials.stage-d-decision-modals', [
    'case' => $case,
    'decisionBranch' => $decisionBranch ?? null,
])
