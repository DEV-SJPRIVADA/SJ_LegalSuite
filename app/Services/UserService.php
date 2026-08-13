<?php

namespace App\Services;

use App\Models\JobPosition;
use App\Models\OrganizationalArea;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Orquesta operaciones sobre usuarios del sistema.
 *
 * Alta por administrador: contraseña provisional aleatoria + `must_change_password`.
 * El modelo usa cast `hashed` en password: asignar siempre texto plano.
 */
class UserService
{
    /** Permisos gestionados como concesiones directas opcionales (área Operaciones). */
    private const OPERATIONS_DIRECT_PERMISSIONS = [
        'disciplinary.generate-inform',
        'disciplinary.upload-notification',
        'disciplinary.download-pdf',
    ];

    /**
     * @param  array<string,mixed>  $attributes
     * @param  list<string>  $roles
     * @param  array<string,bool>  $directOperationalPermissions  nombre permiso => activo
     * @param  list<string>  $authorizedMunicipalityCodes
     * @return array{user: User, plain_password: string}
     */
    public function create(
        array $attributes,
        array $roles = [],
        array $directOperationalPermissions = [],
        array $authorizedMunicipalityCodes = [],
    ): array {
        return DB::transaction(function () use ($attributes, $roles, $directOperationalPermissions, $authorizedMunicipalityCodes) {
            $plainPassword = Str::password(14, true, true, true, false);

            $user = new User;
            $user->name = $attributes['name'];
            $user->email = $attributes['email'];
            $user->password = $plainPassword;
            $user->document_number = $attributes['document_number'] ?? null;
            $user->phone = $attributes['phone'] ?? null;
            $user->organizational_area_id = $attributes['organizational_area_id'] ?? null;
            $user->job_position_id = $attributes['job_position_id'] ?? null;
            $this->applyLegacyOrganizationColumns($user);
            $user->is_active = $attributes['is_active'] ?? true;
            $user->read_only = $attributes['read_only'] ?? false;
            $user->must_change_password = true;
            $user->email_verified_at = $attributes['email_verified_at'] ?? now();
            $user->save();

            if (! empty($roles)) {
                $user->syncRoles($roles);
            }

            $this->syncOperationalDirectExtras($user, $directOperationalPermissions);
            $this->syncAuthorizedMunicipalities($user, $authorizedMunicipalityCodes);

            return [
                'user' => $user->fresh(['roles', 'organizationalArea', 'jobPosition', 'authorizedMunicipalities']),
                'plain_password' => $plainPassword,
            ];
        });
    }

    /**
     * @param  array<string,mixed>  $attributes
     * @param  list<string>|null  $roles  null = no tocar roles
     * @param  array<string,bool>|null  $directOperationalPermissions  null = no tocar permisos directos de esta lista
     */
    public function update(User $user, array $attributes, ?array $roles = null, ?array $directOperationalPermissions = null, ?array $authorizedMunicipalityCodes = null): User
    {
        return DB::transaction(function () use ($user, $attributes, $roles, $directOperationalPermissions, $authorizedMunicipalityCodes) {
            $user->fill([
                'name' => $attributes['name'] ?? $user->name,
                'email' => $attributes['email'] ?? $user->email,
                'document_number' => $attributes['document_number'] ?? $user->document_number,
                'phone' => $attributes['phone'] ?? $user->phone,
                'organizational_area_id' => array_key_exists('organizational_area_id', $attributes)
                    ? $attributes['organizational_area_id']
                    : $user->organizational_area_id,
                'job_position_id' => array_key_exists('job_position_id', $attributes)
                    ? $attributes['job_position_id']
                    : $user->job_position_id,
                'is_active' => $attributes['is_active'] ?? $user->is_active,
                'read_only' => array_key_exists('read_only', $attributes)
                    ? (bool) $attributes['read_only']
                    : $user->read_only,
            ]);

            $this->applyLegacyOrganizationColumns($user);

            $user->save();

            if ($roles !== null) {
                $user->syncRoles($roles);
            }

            if ($directOperationalPermissions !== null) {
                $this->syncOperationalDirectExtras($user, $directOperationalPermissions);
            }

            if ($authorizedMunicipalityCodes !== null) {
                $this->syncAuthorizedMunicipalities($user, $authorizedMunicipalityCodes);
            }

            return $user->fresh(['roles', 'organizationalArea', 'jobPosition', 'authorizedMunicipalities']);
        });
    }

    private function applyLegacyOrganizationColumns(User $user): void
    {
        $slug = null;
        $positionLabel = null;

        if ($user->organizational_area_id) {
            $slug = OrganizationalArea::whereKey($user->organizational_area_id)->value('slug');
        }

        if ($user->job_position_id) {
            $positionLabel = JobPosition::whereKey($user->job_position_id)->value('name');
        }

        $user->area = $slug;
        $user->position = $positionLabel;
    }

    /**
     * @param  array<string,bool>  $desired  nombre permiso => conceder como permiso directo
     */
    private function syncOperationalDirectExtras(User $user, array $desired): void
    {
        foreach (self::OPERATIONS_DIRECT_PERMISSIONS as $perm) {
            $on = (bool) ($desired[$perm] ?? false);
            if ($on) {
                if (! $user->hasDirectPermission($perm)) {
                    $user->givePermissionTo($perm);
                }
            } elseif ($user->hasDirectPermission($perm)) {
                $user->revokePermissionTo($perm);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $codes
     */
    private function syncAuthorizedMunicipalities(User $user, array $codes): void
    {
        $normalized = collect($codes)
            ->map(fn ($code) => preg_replace('/\D/', '', (string) $code))
            ->filter(fn ($code) => is_string($code) && strlen($code) === 5)
            ->unique()
            ->values()
            ->all();

        $user->authorizedMunicipalities()->sync($normalized);
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
