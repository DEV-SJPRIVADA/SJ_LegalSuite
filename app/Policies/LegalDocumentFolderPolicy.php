<?php

namespace App\Policies;

use App\Enums\PlatformLevel;
use App\Models\LegalDocuments\LegalDocumentFolder;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class LegalDocumentFolderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasPlatformLevel(PlatformLevel::Nivel1) && ! $user->read_only) {
            return true;
        }

        // Dirección jurídica / abogada gestiona el módulo completo.
        if ($user->hasPlatformLevel(PlatformLevel::Nivel6) && ! $user->read_only) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        if ($this->managesModule($user)) {
            return true;
        }

        if ($this->has($user, 'licitaciones.view')) {
            return true;
        }

        return LegalDocumentFolder::query()
            ->where('is_active', true)
            ->visibleTo($user)
            ->exists();
    }

    public function view(User $user, LegalDocumentFolder $folder): bool
    {
        if ($this->managesModule($user)) {
            return true;
        }

        return $folder->isAssignedTo($user);
    }

    /**
     * Director de área: solo subir/reemplazar el archivo vigente.
     */
    public function upload(User $user, LegalDocumentFolder $folder): bool
    {
        if ($user->read_only) {
            return false;
        }

        if ($this->configuresFolder($user)) {
            return true;
        }

        return $folder->isAssignedTo($user);
    }

    /**
     * Solo admin/gestor: crear solicitudes adicionales en la carpeta.
     */
    public function addRequest(User $user, LegalDocumentFolder $folder): bool
    {
        return $this->configuresFolder($user);
    }

    /**
     * Solo admin/gestor: frecuencia de correos y reglas de notificación.
     */
    public function manageReminders(User $user, LegalDocumentFolder $folder): bool
    {
        return $this->configuresFolder($user);
    }

    /**
     * Solo admin/gestor: frecuencia del documento, solicitudes y reglas amplias.
     */
    public function editRules(User $user, LegalDocumentFolder $folder): bool
    {
        return $this->configuresFolder($user);
    }

    /**
     * Admin/gestor o director de la carpeta: fecha de expedición (recalcula renovación).
     */
    public function editIssuedDate(User $user, LegalDocumentFolder $folder): bool
    {
        if ($user->read_only) {
            return false;
        }

        if ($this->configuresFolder($user)) {
            return true;
        }

        return $folder->isAssignedTo($user);
    }

    public function assignResponsible(User $user): bool
    {
        return $user->hasPlatformLevel(PlatformLevel::Nivel5, PlatformLevel::Nivel6)
            || $this->has($user, 'legal-documents.manage');
    }

    /**
     * Crear una nueva carpeta / espacio de área.
     */
    public function create(User $user): bool
    {
        return $user->hasPlatformLevel(PlatformLevel::Nivel1, PlatformLevel::Nivel5, PlatformLevel::Nivel6)
            || $this->has($user, 'legal-documents.manage');
    }

    /** Admin / gestor: configura carpetas, fechas, solicitudes y recordatorios. */
    private function configuresFolder(User $user): bool
    {
        if ($user->read_only) {
            return false;
        }

        if ($user->hasPlatformLevel(PlatformLevel::Nivel5, PlatformLevel::Nivel6)) {
            return true;
        }

        return $this->has($user, 'legal-documents.manage')
            || $this->has($user, 'licitaciones.manage-solicitudes');
    }

    private function managesModule(User $user): bool
    {
        return $user->hasPlatformLevel(PlatformLevel::Nivel5, PlatformLevel::Nivel6)
            || $this->has($user, 'legal-documents.view')
            || $this->has($user, 'legal-documents.manage');
    }

    private function has(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
