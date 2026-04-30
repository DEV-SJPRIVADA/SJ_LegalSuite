<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Orquesta operaciones sobre usuarios del sistema.
 *
 * Alta por administrador: contraseña provisional aleatoria + `must_change_password`.
 * El modelo usa cast `hashed` en password: asignar siempre texto plano.
 */
class UserService
{
    /**
     * @param  array<string,mixed>  $attributes
     * @param  list<string>  $roles
     * @return array{user: User, plain_password: string}
     */
    public function create(array $attributes, array $roles = []): array
    {
        return DB::transaction(function () use ($attributes, $roles) {
            $plainPassword = Str::password(14, true, true, true, false);

            $user = new User;
            $user->name = $attributes['name'];
            $user->email = $attributes['email'];
            $user->password = $plainPassword;
            $user->document_number = $attributes['document_number'] ?? null;
            $user->phone = $attributes['phone'] ?? null;
            $user->area = $attributes['area'] ?? null;
            $user->position = $attributes['position'] ?? null;
            $user->is_active = $attributes['is_active'] ?? true;
            $user->read_only = $attributes['read_only'] ?? false;
            $user->must_change_password = true;
            $user->email_verified_at = $attributes['email_verified_at'] ?? now();
            $user->save();

            if (! empty($roles)) {
                $user->syncRoles($roles);
            }

            return [
                'user' => $user->fresh('roles'),
                'plain_password' => $plainPassword,
            ];
        });
    }

    /**
     * @param  array<string,mixed>  $attributes
     * @param  list<string>|null  $roles  null = no tocar roles
     */
    public function update(User $user, array $attributes, ?array $roles = null): User
    {
        return DB::transaction(function () use ($user, $attributes, $roles) {
            $user->fill([
                'name' => $attributes['name'] ?? $user->name,
                'email' => $attributes['email'] ?? $user->email,
                'document_number' => $attributes['document_number'] ?? $user->document_number,
                'phone' => $attributes['phone'] ?? $user->phone,
                'area' => $attributes['area'] ?? $user->area,
                'position' => $attributes['position'] ?? $user->position,
                'is_active' => $attributes['is_active'] ?? $user->is_active,
                'read_only' => array_key_exists('read_only', $attributes)
                    ? (bool) $attributes['read_only']
                    : $user->read_only,
            ])->save();

            if ($roles !== null) {
                $user->syncRoles($roles);
            }

            return $user->fresh('roles');
        });
    }

    public function changePassword(User $user, string $newPassword): User
    {
        $user->password = $newPassword;
        $user->must_change_password = false;
        $user->save();

        return $user;
    }

    /** Reinicio administrativo: contraseña en texto plano + obligación de cambiarla en el próximo ingreso. */
    public function resetToProvisionalPassword(User $user, string $plainPassword): User
    {
        $user->password = $plainPassword;
        $user->must_change_password = true;
        $user->save();

        return $user->fresh();
    }

    public function toggleActive(User $user): User
    {
        $user->is_active = ! $user->is_active;
        $user->save();

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function restore(User $user): User
    {
        $user->restore();

        return $user;
    }
}
