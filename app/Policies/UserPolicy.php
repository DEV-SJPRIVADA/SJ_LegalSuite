<?php

namespace App\Policies;

use App\Models\User;

/**
 * Crear, editar, contraseña, activar/desactivar y eliminar: solo rol **admin** (no solo lectura).
 * Consulta de listado y detalle: permiso `users.view` (auditoría, etc.).
 */
class UserPolicy
{
    /** @var list<string> */
    private const READ_ABILITIES = ['viewAny', 'view'];

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin') && $user->read_only) {
            return in_array($ability, self::READ_ABILITIES, true) ? true : false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') && ! $user->read_only;
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasRole('admin') && ! $user->read_only;
    }

    public function changePassword(User $user, User $target): bool
    {
        return $user->hasRole('admin') && ! $user->read_only;
    }

    public function toggleActive(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $user->hasRole('admin') && ! $user->read_only;
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $user->hasRole('admin') && ! $user->read_only;
    }

    public function restore(User $user, User $target): bool
    {
        return $user->hasRole('admin') && ! $user->read_only;
    }
}
