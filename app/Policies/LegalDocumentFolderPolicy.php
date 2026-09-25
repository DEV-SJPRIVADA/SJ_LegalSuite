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

        // Dirección jurídica / abogada gestiona el módulo (incluida carpeta Jurídico sin recordatorios).
        if ($user->hasPlatformLevel(PlatformLevel::Nivel6) && ! $user->read_only) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPlatformLevel(PlatformLevel::Nivel5, PlatformLevel::Nivel6)
            || $this->has($user, 'licitaciones.view')
            || $this->has($user, 'legal-documents.view');
    }

    public function view(User $user, LegalDocumentFolder $folder): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        return (int) $folder->responsible_user_id === (int) $user->id;
    }

    public function upload(User $user, LegalDocumentFolder $folder): bool
    {
        if ($user->read_only) {
            return false;
        }

        if ($this->has($user, 'legal-documents.manage') || $this->has($user, 'licitaciones.manage-solicitudes')) {
            return true;
        }

        return (int) $folder->responsible_user_id === (int) $user->id;
    }

    public function addRequest(User $user, LegalDocumentFolder $folder): bool
    {
        return $this->upload($user, $folder);
    }

    public function assignResponsible(User $user): bool
    {
        return $user->hasPlatformLevel(PlatformLevel::Nivel5, PlatformLevel::Nivel6)
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
