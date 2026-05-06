<?php

namespace App\Policies;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Personnel;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class PersonnelPolicy
{
    /**
     * Listado de personal (p. ej. selector en FO-GJ-51).
     * Quien puede generar/enviar el informe necesita ver nombres aunque no tenga el permiso global personnel.view
     * (p. ej. supervisor si la BD no se resincronizó tras añadir personnel.view al rol).
     */
    public function viewAny(User $user): bool
    {
        try {
            if ($user->hasPermissionTo('personnel.view')) {
                return true;
            }
        } catch (PermissionDoesNotExist) {
            // permiso ausente en BD: no romper la app; valorar otras reglas abajo
        }

        return $user->can('generateFo51Inform', DisciplinaryCase::class)
            || $user->can('create', DisciplinaryCase::class);
    }

    public function view(User $user, Personnel $personnel): bool
    {
        try {
            if ($user->hasPermissionTo('personnel.view')) {
                return true;
            }
        } catch (PermissionDoesNotExist) {
        }

        return $user->can('generateFo51Inform', DisciplinaryCase::class)
            || $user->can('create', DisciplinaryCase::class);
    }
}
