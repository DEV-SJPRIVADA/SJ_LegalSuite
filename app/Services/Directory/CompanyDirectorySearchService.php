<?php

namespace App\Services\Directory;

use App\Models\Directory\DirectoryUser;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Busca funcionarios para invitaciones en el directorio local (directory_users),
 * con respaldo a usuarios activos de LegalSuite.
 */
class CompanyDirectorySearchService
{
    /**
     * @param  list<string>  $excludeEmails
     * @return Collection<int, array{id: string, name: string, email: string, source: string}>
     */
    public function search(string $term, array $excludeEmails = [], int $limit = 15): Collection
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        $exclude = collect($excludeEmails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->values()
            ->all();

        $fromDirectory = $this->searchDirectoryUsers($term, $exclude, $limit);
        if ($fromDirectory->isNotEmpty()) {
            return $fromDirectory;
        }

        return $this->searchLegalSuiteUsers($term, $exclude, $limit);
    }

    /**
     * @param  list<string>  $excludeEmails
     * @return Collection<int, array{id: string, name: string, email: string, source: string}>
     */
    private function searchDirectoryUsers(string $term, array $excludeEmails, int $limit): Collection
    {
        return DirectoryUser::query()
            ->active()
            ->search($term)
            ->when($excludeEmails !== [], fn ($q) => $q->whereNotIn('email', $excludeEmails))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'source'])
            ->map(fn (DirectoryUser $user) => [
                'id' => 'directory:'.$user->id,
                'name' => (string) $user->name,
                'email' => strtolower((string) $user->email),
                'source' => (string) ($user->source ?: 'directory'),
            ]);
    }

    /**
     * @param  list<string>  $excludeEmails
     * @return Collection<int, array{id: string, name: string, email: string, source: string}>
     */
    private function searchLegalSuiteUsers(string $term, array $excludeEmails, int $limit): Collection
    {
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return User::query()
            ->active()
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->when($excludeEmails !== [], fn ($q) => $q->whereNotIn('email', $excludeEmails))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => 'legalsuite:'.$user->id,
                'name' => (string) $user->name,
                'email' => strtolower((string) $user->email),
                'source' => 'legalsuite',
            ]);
    }
}
