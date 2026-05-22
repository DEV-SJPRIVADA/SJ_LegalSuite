<?php

namespace App\Policies;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        try {
            if ($user->hasPermissionTo('employees.view')) {
                return true;
            }
        } catch (PermissionDoesNotExist) {
        }

        return $user->can('generateFo51Inform', DisciplinaryCase::class)
            || $user->can('create', DisciplinaryCase::class);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('employees.manage');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $this->create($user);
    }

    public function import(User $user): bool
    {
        return $this->create($user);
    }
}
