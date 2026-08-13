<?php

namespace App\Policies;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Enums\PlatformLevel;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Disciplinary\ComiteActaService;
use App\Services\Disciplinary\ComiteDraftService;
use App\Services\Disciplinary\DecisionComunicadoService;
use App\Services\Disciplinary\DecisionDraftService;
use App\Services\Disciplinary\DisciplinaryDecisionNotificationService;
use App\Services\Disciplinary\DisciplinaryDecisionWorkflowService;
use App\Services\Disciplinary\DiligenceAttendanceService;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
use App\Services\Disciplinary\FoGj03DraftService;
use App\Services\Disciplinary\FoGj04DiligenceActaService;
use App\Services\Disciplinary\FoGj04DraftService;
use App\Services\Disciplinary\FoGj44ConstanciaService;
use App\Services\Disciplinary\FoGj44DraftService;
use App\Services\Disciplinary\FoGj54DraftService;
use App\Services\Disciplinary\FoGj54ReprogramacionService;
use App\Support\Disciplinary\DecisionBranch;
use App\Support\Disciplinary\FieldDisciplinaryScopeService;

/**
 * Autorización del módulo disciplinario:
 *
 * - admin (sin modo solo lectura) → control total vía `before`.
 * - admin en modo solo lectura → sólo consulta (listados, detalle, dashboard).
 * - Otros usuarios con `read_only` → igual: consulta sin mutaciones.
 * - abogado → casos asignados + bandeja INFORME sin titular (`claim`); puede ver PDF FO-GJ-51 del expediente si existe (`viewFo51InformePdf`).
 * - supervisor / operador → pool por turno (casos fuera de borrador); informe FO-GJ-51 + evidencias.
 * - programador → expedientes ya formalizados (no borrador); programar fechas de etapa.
 * - planeacion → no gestiona expedientes; trabaja solo en bandeja de coordinaciones abiertas.
 * - administrativa → consulta amplia vía `disciplinary.view`.
 * - operaciones → expedientes con revisor FO-GJ-51 asignado (`assigned_reviewer_id`), reportó o todos con `review-inform-all`.
 * - Asignar / reasignar abogado titular: `admin` o permiso `disciplinary.assign` (no solo lectura), vía `assign`.
 * - Hilo agenda (citación / reprogramación): `postAgendaLawyer` (abogado titular), `postAgendaPlanning` (planeación o admin sin atajo `before`).
 */
class DisciplinaryCasePolicy
{
    /** @var list<string> */
    private const READ_ABILITIES = ['viewAny', 'view', 'viewDashboard'];

    /**
     * El admin no debe suplantar al abogado titular ni a planeación en el hilo de agenda;
     * se evalúa en los métodos específicos.
     *
     * @var list<string>
     */
    private const ADMIN_DO_NOT_SHORT_CIRCUIT = ['postAgendaLawyer', 'postAgendaPlanning', 'assign', 'claim'];

    public function before(User $user, string $ability): ?bool
    {
        if (! $user->hasPlatformLevel(PlatformLevel::Nivel1)) {
            return null;
        }

        if ($user->read_only) {
            return in_array($ability, self::READ_ABILITIES, true) ? true : false;
        }

        if (in_array($ability, self::ADMIN_DO_NOT_SHORT_CIRCUIT, true)) {
            return null;
        }

        return true;
    }

    private function deniesMutation(User $user): bool
    {
        return (bool) $user->read_only;
    }

    public function viewAny(User $user): bool
    {
        if ($user->hasPlatformLevel(PlatformLevel::Nivel7)) {
            return false;
        }

        return $user->hasPlatformLevel(
            PlatformLevel::Nivel5,
            PlatformLevel::Nivel6,
            PlatformLevel::Nivel4,
            PlatformLevel::Nivel2,
            PlatformLevel::Nivel8,
            PlatformLevel::Nivel9,
        )
            || $user->hasPermissionTo('disciplinary.view');
    }

    public function view(User $user, DisciplinaryCase $case): bool
    {
        if ($user->hasPlatformLevel(PlatformLevel::Nivel5)) {
            return true;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel7)) {
            return false;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel8)) {
            return $case->isVisibleToDisciplinaryFieldPool();
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel9)) {
            return $case->isVisibleToDisciplinaryFieldPool();
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel6)) {
            return (int) $case->assigned_lawyer_id === (int) $user->id
                || $case->isInInformePool();
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel3)) {
            return false;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel2)) {
            return $case->isVisibleToOperacionesReviewer($user);
        }

        if ($user->hasPermissionTo('disciplinary.view')) {
            return true;
        }

        return $case->reporter_id === $user->id || $case->assigned_lawyer_id === $user->id;
    }

    public function create(User $user): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel7, PlatformLevel::Nivel8, PlatformLevel::Nivel9)) {
            return false;
        }

        return $user->hasPermissionTo('disciplinary.create');
    }

    public function update(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel7, PlatformLevel::Nivel8, PlatformLevel::Nivel9)) {
            return false;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel6)) {
            return $case->assigned_lawyer_id === $user->id;
        }

        return $user->hasPermissionTo('disciplinary.update')
            && ($case->assigned_lawyer_id === $user->id || $case->reporter_id === $user->id);
    }

    public function transition(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel7, PlatformLevel::Nivel8, PlatformLevel::Nivel9)) {
            return false;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel6)) {
            return $case->assigned_lawyer_id === $user->id;
        }

        if (! $user->hasPermissionTo('disciplinary.transition')) {
            return false;
        }

        return $case->assigned_lawyer_id === $user->id;
    }

    public function assignDate(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $user->hasPermissionTo('disciplinary.assign-date')) {
            return false;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel9)) {
            return $case->isVisibleToDisciplinaryFieldPool();
        }

        return $user->can('view', $case);
    }

    public function assign(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return $user->hasPlatformLevel(PlatformLevel::Nivel1)
            || $user->hasPermissionTo('disciplinary.assign');
    }

    /** Tomar gestión de un expediente en bandeja INFORME (sin titular). */
    public function claim(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $user->hasPlatformLevel(PlatformLevel::Nivel6)) {
            return false;
        }

        return $case->isInInformePool();
    }

    public function assignPlanner(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return $user->hasPermissionTo('disciplinary.assign-planner');
    }

    public function startCoordination(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return (int) $case->assigned_lawyer_id === (int) $user->id
            && $case->canStartCoordination();
    }

    public function previewFoGj03(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        if ($case->citation_confirmed_date === null) {
            return false;
        }

        $notification = app(DisciplinaryCitationNotificationService::class);

        return $notification->hasNotificationInformationCompleted($case)
            && app(FoGj03DraftService::class)->isReadyForPdf($case);
    }

    public function generateFoGj03(User $user, DisciplinaryCase $case): bool
    {
        if (! $this->previewFoGj03($user, $case)) {
            return false;
        }

        return app(DisciplinaryCitationNotificationService::class)->canGenerateFoGj03($case);
    }

    public function editFoGj03Draft(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        if ($case->citation_confirmed_date === null) {
            return false;
        }

        if ($case->fo_gj_03_generated_at !== null) {
            return false;
        }

        return app(DisciplinaryCitationNotificationService::class)
            ->hasNotificationInformationCompleted($case);
    }

    public function editFoGj04Draft(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        if ($case->current_status !== CaseStatus::DILIGENCIA) {
            return false;
        }

        if ($case->citation_confirmed_date === null) {
            return false;
        }

        if ($case->diligence_attendance !== DiligenceAttendance::ATTENDED) {
            return false;
        }

        return $case->fo_gj_04_generated_at === null;
    }

    public function registerDiligenceAttendance(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        return app(DiligenceAttendanceService::class)->canRegister($case);
    }

    public function editFoGj44Draft(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        if ($case->current_status !== CaseStatus::DILIGENCIA) {
            return false;
        }

        if ($case->diligence_attendance !== DiligenceAttendance::ABSENT) {
            return false;
        }

        return $case->fo_gj_44_generated_at === null;
    }

    public function previewFoGj44(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        return app(FoGj44DraftService::class)->isReadyForPdf($case);
    }

    public function generateFoGj44(User $user, DisciplinaryCase $case): bool
    {
        if (! $this->previewFoGj44($user, $case)) {
            return false;
        }

        return app(FoGj44ConstanciaService::class)->canGenerate($case);
    }

    public function editFoGj54Draft(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        return app(FoGj54DraftService::class)->canEditDraft($case);
    }

    public function previewFoGj54(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        return app(FoGj54DraftService::class)->isReadyForPdf($case);
    }

    public function generateFoGj54(User $user, DisciplinaryCase $case): bool
    {
        if (! $this->previewFoGj54($user, $case)) {
            return false;
        }

        return app(FoGj54ReprogramacionService::class)->canGenerate($case);
    }

    public function uploadFoGj54Evidence(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        return app(FoGj54ReprogramacionService::class)->canUploadReceiptEvidence($case);
    }

    public function manageDiligenceJustification(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        return $case->current_status === CaseStatus::JUSTIFICACION_PENDIENTE
            && $case->diligence_attendance === DiligenceAttendance::ABSENT
            && $case->fo_gj_44_generated_at !== null;
    }

    public function editComiteDraft(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        if ($case->current_status !== CaseStatus::COMITE_DISCIPLINARIO) {
            return false;
        }

        return $case->comite_generated_at === null;
    }

    public function previewComite(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        return app(ComiteDraftService::class)->isReadyForPdf($case);
    }

    public function generateComite(User $user, DisciplinaryCase $case): bool
    {
        if (! $this->previewComite($user, $case)) {
            return false;
        }

        return app(ComiteActaService::class)->canGenerate($case);
    }

    public function selectDecisionType(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        return $case->current_status === CaseStatus::DECISION
            && $case->decision_coordination_started_at === null;
    }

    public function editDecisionDraft(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        if ($case->current_status !== CaseStatus::DECISION) {
            return false;
        }

        return $case->decision_comunicado_generated_at === null
            && $case->decision !== null
            && $case->decision_notification_completed_at !== null;
    }

    public function previewDecisionComunicado(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        return app(DecisionDraftService::class)->isReadyForPdf($case);
    }

    public function generateDecisionComunicado(User $user, DisciplinaryCase $case): bool
    {
        if (! $this->previewDecisionComunicado($user, $case)) {
            return false;
        }

        return app(DecisionComunicadoService::class)->canGenerate($case);
    }

    public function finalizeDecisionCase(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        return $case->current_status === CaseStatus::DECISION
            && app(DisciplinaryDecisionWorkflowService::class)->missingFinalizeRequirements($case) === [];
    }

    public function postDecisionNotificationCoordination(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $user->hasPlatformLevel(PlatformLevel::Nivel3) && ! $user->hasPlatformLevel(PlatformLevel::Nivel1)) {
            return false;
        }

        if ($case->agendaThread?->isClosed()) {
            return false;
        }

        return app(DisciplinaryDecisionNotificationService::class)->canPlanningRegisterNotification($case);
    }

    public function uploadDecisionEvidence(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return $case->canUserUploadDecisionEvidence($user);
    }

    public function completeDecisionHrReview(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return app(DisciplinaryDecisionWorkflowService::class)->userCanCompleteHrReview($user)
            && $case->current_status === CaseStatus::DECISION
            && $case->decision_hr_review_completed_at === null
            && $case->hasDecisionHrAnnex();
    }

    public function uploadDecisionHrAnnex(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        if ($case->current_status !== CaseStatus::DECISION) {
            return false;
        }

        $branch = DecisionBranch::forDecision($case->decision);

        return $branch !== null && DecisionBranch::requiresLawyerTerminationPackage($branch);
    }

    public function captureFoGj04WorkerSignature(User $user, DisciplinaryCase $case): bool
    {
        if (! $this->previewFoGj04($user, $case)) {
            return false;
        }

        if ($case->fo_gj_04_generated_at !== null) {
            return false;
        }

        return $case->diligence_attendance === DiligenceAttendance::ATTENDED;
    }

    public function previewFoGj04(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        if ($case->citation_confirmed_date === null) {
            return false;
        }

        if ($case->diligence_attendance !== DiligenceAttendance::ATTENDED) {
            return false;
        }

        return app(FoGj04DraftService::class)->isReadyForPdf($case);
    }

    public function generateFoGj04(User $user, DisciplinaryCase $case): bool
    {
        if (! $this->previewFoGj04($user, $case)) {
            return false;
        }

        return app(FoGj04DiligenceActaService::class)->canGenerate($case);
    }

    public function uploadFoGj04Signed(User $user, DisciplinaryCase $case): bool
    {
        if (! $this->previewFoGj04($user, $case)) {
            return false;
        }

        if ($case->fo_gj_04_generated_at !== null) {
            return false;
        }

        return app(FoGj04DiligenceActaService::class)->canUploadSigned($case);
    }

    public function requestNotificationCoordination(User $user, DisciplinaryCase $case): bool
    {
        return false;
    }

    public function postNotificationCoordination(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $user->hasPlatformLevel(PlatformLevel::Nivel3) && ! $user->hasPlatformLevel(PlatformLevel::Nivel1)) {
            return false;
        }

        if ($case->agendaThread?->isClosed()) {
            return false;
        }

        return app(DisciplinaryCitationNotificationService::class)->canPlanningRegisterNotification($case);
    }

    public function reassignNotificationSupervisor(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return app(DisciplinaryCitationNotificationService::class)
            ->userCanReassignNotificationSupervisor($user, $case);
    }

    public function viewCitationEvidence(User $user, DisciplinaryCase $case): bool
    {
        if (! $case->canReceiveCitationEvidence()) {
            return false;
        }

        return $this->view($user, $case);
    }

    public function viewFoGj03NotificationForSupervisor(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $user->hasPlatformLevel(PlatformLevel::Nivel7)) {
            return false;
        }

        if ((int) $case->notification_supervisor_user_id !== (int) $user->id) {
            return false;
        }

        if (! app(FieldDisciplinaryScopeService::class)->caseEmployeeInScope($user, $case)) {
            return false;
        }

        return $case->fo_gj_03_generated_at !== null
            && $case->citation_evidence_uploaded_at === null
            && $case->fo_gj_03_draft_completed_at !== null;
    }

    public function viewDecisionComunicadoForSupervisor(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $user->hasPlatformLevel(PlatformLevel::Nivel7)) {
            return false;
        }

        if ((int) $case->decision_notification_supervisor_user_id !== (int) $user->id) {
            return false;
        }

        if (! app(FieldDisciplinaryScopeService::class)->caseEmployeeInScope($user, $case)) {
            return false;
        }

        return $case->decision_comunicado_generated_at !== null
            && $case->decision_evidence_uploaded_at === null
            && $case->canReceiveDecisionEvidence();
    }

    public function uploadCitationEvidence(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return $case->canUserUploadCitationEvidence($user);
    }

    /** Coordinación FO-GJ-03 / fechas: mensajes del titular desde citación o reprogramación. */
    public function postAgendaLawyer(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $case->allowsAgendaThread()) {
            return false;
        }

        return (int) $case->assigned_lawyer_id === (int) $user->id;
    }

    /** Respuestas y adjuntos del lado planeación (rol planeación; admin evaluado en el método). */
    public function postAgendaPlanning(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $case->allowsAgendaThread()) {
            return false;
        }
        if ($case->agendaThread && $case->agendaThread->isClosed()) {
            return false;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel1)) {
            return true;
        }

        return $user->hasPlatformLevel(PlatformLevel::Nivel3);
    }

    public function closeCoordination(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        $isAssignedLawyer = (int) $case->assigned_lawyer_id === (int) $user->id;
        $isJuridicalDirection = $user->hasPlatformLevel(PlatformLevel::Nivel1) || $user->hasPermissionTo('disciplinary.assign');

        return $case->agendaThread !== null
            && $case->agendaThread->isOpen()
            && ($isAssignedLawyer || $isJuridicalDirection);
    }

    /** Plantilla FO-GJ-51 (PDF): operaciones crean casos; campo sólo con permiso dedicado. */
    public function generateFo51Inform(User $user): bool
    {
        return $user->hasPermissionTo('disciplinary.generate-inform');
    }

    /**
     * Ver el bloque FO-GJ-51 en expediente: quien puede generar informe, o el abogado titular
     * cuando ya existe PDF del informe en el caso (solo consulta).
     */
    public function viewFo51InformePdf(User $user, DisciplinaryCase $case): bool
    {
        if ($this->generateFo51Inform($user)) {
            return true;
        }

        if (! $user->hasPlatformLevel(PlatformLevel::Nivel6) || (int) $case->assigned_lawyer_id !== (int) $user->id) {
            return false;
        }

        $doc = $case->primaryFo51InformeDocument();

        return $doc !== null && $doc->path !== '';
    }

    public function uploadDocument(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        if (! $user->hasPermissionTo('disciplinary.upload-document')) {
            return false;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel6)) {
            return $case->assigned_lawyer_id === $user->id;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel7, PlatformLevel::Nivel8)) {
            return $case->isVisibleToDisciplinaryFieldPool();
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel2)) {
            return $case->isVisibleToOperacionesReviewer($user);
        }

        return true;
    }

    public function delete(User $user, DisciplinaryCase $case): bool
    {
        if ($this->deniesMutation($user)) {
            return false;
        }

        return $user->hasPermissionTo('disciplinary.delete')
            && ! $case->isFinalized();
    }

    public function viewDashboard(User $user): bool
    {
        if ($user->hasPlatformLevel(PlatformLevel::Nivel3, PlatformLevel::Nivel7)) {
            return false;
        }

        return $user->hasPlatformLevel(PlatformLevel::Nivel5, PlatformLevel::Nivel6)
            || $user->hasPermissionTo('disciplinary.view-dashboard');
    }

    /** Catálogo de formatos FO-GJ (referencia para quien puede consultar expedientes). */
    public function viewOfficialForms(User $user): bool
    {
        if ($user->isMinimalDisciplinaryPortalUser()
            || $user->hasPlatformLevel(PlatformLevel::Nivel3)
            || $user->isDisciplinaryOperacionesReviewer()) {
            return false;
        }

        return $this->viewAny($user);
    }

    /** Membrete institucional del acta de comité (PNG/JPEG en Formatos). */
    public function manageOfficialLetterhead(User $user): bool
    {
        if ($user->read_only ?? false) {
            return false;
        }

        return $user->hasPlatformLevel(PlatformLevel::Nivel1)
            || $user->hasPermissionTo('disciplinary.assign');
    }
}
