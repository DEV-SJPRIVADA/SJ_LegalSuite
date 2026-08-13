<?php

namespace App\Services\Disciplinary;

use App\Models\Disciplinary\SupervisionZone;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Catálogo de zonas de supervisión y membresía (1 usuario → 1 zona).
 */
final class SupervisionZoneService
{
    /**
     * @return Collection<int, SupervisionZone>
     */
    public function activeZonesOrdered(): Collection
    {
        return SupervisionZone::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, SupervisionZone>
     */
    public function allZonesOrdered(): Collection
    {
        return SupervisionZone::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount('users')
            ->get();
    }

    public function findActive(int $zoneId): ?SupervisionZone
    {
        return SupervisionZone::query()->active()->whereKey($zoneId)->first();
    }

    /**
     * @param  array{name: string, code?: string|null, notification_email?: string|null, is_active?: bool, sort_order?: int}  $data
     */
    public function create(array $data): SupervisionZone
    {
        return SupervisionZone::query()->create([
            'name' => trim($data['name']),
            'code' => $this->nullableTrim($data['code'] ?? null),
            'notification_email' => $this->nullableTrim($data['notification_email'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    /**
     * @param  array{name?: string, code?: string|null, notification_email?: string|null, is_active?: bool, sort_order?: int}  $data
     */
    public function update(SupervisionZone $zone, array $data): SupervisionZone
    {
        if (array_key_exists('name', $data)) {
            $zone->name = trim((string) $data['name']);
        }
        if (array_key_exists('code', $data)) {
            $zone->code = $this->nullableTrim($data['code']);
        }
        if (array_key_exists('notification_email', $data)) {
            $zone->notification_email = $this->nullableTrim($data['notification_email']);
        }
        if (array_key_exists('is_active', $data)) {
            $zone->is_active = (bool) $data['is_active'];
        }
        if (array_key_exists('sort_order', $data)) {
            $zone->sort_order = (int) $data['sort_order'];
        }
        $zone->save();

        return $zone->fresh();
    }

    public function delete(SupervisionZone $zone): void
    {
        if ($zone->users()->exists()) {
            throw ValidationException::withMessages([
                'supervision_zone' => 'No se puede eliminar: la zona tiene supervisores asignados. Muévalos primero.',
            ]);
        }

        $assignedCases = DB::table('disciplinary_cases')
            ->where('notification_supervision_zone_id', $zone->id)
            ->orWhere('decision_notification_supervision_zone_id', $zone->id)
            ->exists();

        if ($assignedCases) {
            throw ValidationException::withMessages([
                'supervision_zone' => 'No se puede eliminar: hay casos con notificaciones asignadas a esta zona.',
            ]);
        }

        $zone->delete();
    }

    public function assignUser(User $user, SupervisionZone $zone): void
    {
        if (! $zone->is_active) {
            throw ValidationException::withMessages([
                'supervision_zone_id' => 'La zona de supervisión no está activa.',
            ]);
        }

        if (! $user->hasRole('nivel7')) {
            throw ValidationException::withMessages([
                'supervision_zone_id' => 'Solo supervisores (nivel 7) pueden asignarse a una zona.',
            ]);
        }

        DB::transaction(function () use ($user, $zone): void {
            DB::table('supervision_zone_user')->where('user_id', $user->id)->delete();
            $zone->users()->attach($user->id);
        });
    }

    public function clearUser(User $user): void
    {
        DB::table('supervision_zone_user')->where('user_id', $user->id)->delete();
    }

    public function zoneOf(User $user): ?SupervisionZone
    {
        return $user->currentSupervisionZone();
    }

    public function userBelongsToZone(User $user, int $zoneId): bool
    {
        return DB::table('supervision_zone_user')
            ->where('user_id', $user->id)
            ->where('supervision_zone_id', $zoneId)
            ->exists();
    }

    /**
     * @return Collection<int, User>
     */
    public function activeMembers(SupervisionZone $zone): Collection
    {
        return $zone->users()
            ->where('users.is_active', true)
            ->where('users.read_only', false)
            ->role('nivel7')
            ->orderBy('users.name')
            ->get();
    }

    public function assertActiveZone(int $zoneId): SupervisionZone
    {
        $zone = $this->findActive($zoneId);
        if (! $zone instanceof SupervisionZone) {
            throw ValidationException::withMessages([
                'notification_supervision_zone_id' => 'Seleccione una zona de supervisión activa.',
            ]);
        }

        return $zone;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed === '' ? null : $trimmed;
    }
}
