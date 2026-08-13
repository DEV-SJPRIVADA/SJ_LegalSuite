<?php

namespace App\Policies;

use App\Enums\PlatformLevel;
use App\Models\Licitaciones\Licitacion;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class LicitacionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasPlatformLevel(PlatformLevel::Nivel1) && ! $user->read_only) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'licitaciones.view');
    }

    public function view(User $user, Licitacion $licitacion): bool
    {
        return $this->viewAny($user);
    }

    public function viewDashboard(User $user): bool
    {
        return $this->has($user, 'licitaciones.view-dashboard');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'licitaciones.create');
    }

    public function update(User $user, Licitacion $licitacion): bool
    {
        return $this->has($user, 'licitaciones.update');
    }

    public function delete(User $user, Licitacion $licitacion): bool
    {
        return $this->has($user, 'licitaciones.delete');
    }

    public function manageSolicitudes(User $user): bool
    {
        return $this->has($user, 'licitaciones.manage-solicitudes');
    }

    public function uploadDocument(User $user, ?Licitacion $licitacion = null): bool
    {
        return $this->has($user, 'licitaciones.upload-document');
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
