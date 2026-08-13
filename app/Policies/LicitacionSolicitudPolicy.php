<?php

namespace App\Policies;

use App\Enums\PlatformLevel;
use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\User;

class LicitacionSolicitudPolicy
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
        return $user->can('viewAny', Licitacion::class)
            || $user->can('manageSolicitudes', Licitacion::class);
    }

    public function view(User $user, LicitacionSolicitud $solicitud): bool
    {
        if ($user->can('manageSolicitudes', Licitacion::class)) {
            return true;
        }

        return $solicitud->usuario_responsable_id === $user->id
            || $solicitud->created_by_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('manageSolicitudes', Licitacion::class);
    }

    public function update(User $user, LicitacionSolicitud $solicitud): bool
    {
        return $user->can('manageSolicitudes', Licitacion::class);
    }

    public function delete(User $user, LicitacionSolicitud $solicitud): bool
    {
        return $user->can('manageSolicitudes', Licitacion::class);
    }

    public function comment(User $user, LicitacionSolicitud $solicitud): bool
    {
        return $this->view($user, $solicitud);
    }

    public function uploadDocument(User $user, LicitacionSolicitud $solicitud): bool
    {
        if ($user->can('uploadDocument', Licitacion::class)) {
            return true;
        }

        return $this->view($user, $solicitud);
    }

    public function manageInvitados(User $user, LicitacionSolicitud $solicitud): bool
    {
        return $this->update($user, $solicitud)
            || $solicitud->created_by_id === $user->id
            || $solicitud->usuario_responsable_id === $user->id;
    }

    public function reviewDocument(User $user, LicitacionSolicitud $solicitud): bool
    {
        return $this->manageInvitados($user, $solicitud);
    }
}
