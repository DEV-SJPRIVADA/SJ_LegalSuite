<?php

namespace App\Services\Directory;

use App\Models\Directory\AgendaDirectoryUser;
use App\Models\Directory\DirectoryUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Importa funcionarios desde Agendamiento (agenda_users) hacia directory_users
 * en la base de LegalSuite.
 */
class CompanyDirectorySyncService
{
    /**
     * @return array{imported: int, updated: int, deactivated: int, total_source: int}
     */
    public function syncFromAgendamiento(): array
    {
        $source = AgendaDirectoryUser::query()
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'is_active']);

        $imported = 0;
        $updated = 0;
        $seenEmails = [];

        DB::transaction(function () use ($source, &$imported, &$updated, &$seenEmails): void {
            foreach ($source as $row) {
                $email = strtolower(trim((string) $row->email));
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $seenEmails[] = $email;

                $existing = DirectoryUser::query()->where('email', $email)->first();
                $payload = [
                    'name' => trim((string) $row->name) !== '' ? trim((string) $row->name) : $email,
                    'email' => $email,
                    'is_active' => (bool) $row->is_active,
                    'source' => 'agendamiento',
                    'external_id' => (int) $row->id,
                ];

                if ($existing) {
                    $existing->fill($payload);
                    if ($existing->isDirty()) {
                        $existing->save();
                        $updated++;
                    }
                } else {
                    DirectoryUser::query()->create($payload);
                    $imported++;
                }
            }

            // Quienes ya no están en la fuente de Agendamiento se desactivan
            // (solo los que vinieron de esa fuente).
        });

        $deactivated = 0;
        if ($seenEmails !== []) {
            $deactivated = DirectoryUser::query()
                ->where('source', 'agendamiento')
                ->whereNotIn('email', $seenEmails)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'deactivated' => $deactivated,
            'total_source' => $source->count(),
        ];
    }

    public function trySync(): ?array
    {
        try {
            return $this->syncFromAgendamiento();
        } catch (Throwable $e) {
            Log::warning('directory.sync_failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
