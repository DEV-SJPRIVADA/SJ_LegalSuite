<?php

namespace Database\Seeders;

use App\Models\LegalDocuments\LegalDocumentFolder;
use App\Models\LegalDocuments\LegalDocumentItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LegalDocumentsMatrixSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/legal_documents_matrix.json');
        if (! File::exists($path)) {
            $this->command?->warn('No existe database/data/legal_documents_matrix.json');

            return;
        }

        $payload = json_decode(File::get($path), true);
        if (! is_array($payload)) {
            $this->command?->error('JSON de matriz inválido.');

            return;
        }

        $sort = 0;
        $folderIds = [];
        $activeFolderSlugs = [];
        foreach ($payload['folders'] ?? [] as $folder) {
            $slug = (string) ($folder['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $activeFolderSlugs[] = $slug;
            $model = LegalDocumentFolder::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => (string) ($folder['name'] ?? $slug),
                    'exclude_reminders' => (bool) ($folder['exclude_reminders'] ?? false),
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]
            );
            $folderIds[$slug] = $model->id;
        }

        $keptItemIds = [];
        $count = 0;
        foreach ($payload['items'] ?? [] as $row) {
            $slug = (string) ($row['folder_slug'] ?? '');
            $folderId = $folderIds[$slug] ?? null;
            if (! $folderId) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $code = $row['code'] ?? null;
            $match = [
                'folder_id' => $folderId,
                'title' => $title,
                'code' => $code,
            ];

            $item = LegalDocumentItem::query()->updateOrCreate($match, [
                'group_title' => $row['group_title'] ?? null,
                'issued_by' => $row['issued_by'] ?? null,
                'issued_on' => $row['issued_on'] ?? null,
                'frequency' => $row['frequency'] ?? null,
                'renew_on' => $row['renew_on'] ?? null,
                'renew_label' => $row['renew_label'] ?? null,
                'observations' => $row['observations'] ?? null,
                'source' => 'matrix',
                'is_active' => true,
            ]);
            $keptItemIds[] = $item->id;
            $count++;
        }

        // Solo quedan los de la matriz oficial: desactivar el resto.
        $deactivated = LegalDocumentItem::query()
            ->when($keptItemIds !== [], fn ($q) => $q->whereNotIn('id', $keptItemIds))
            ->when($keptItemIds === [], fn ($q) => $q)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        LegalDocumentFolder::query()
            ->whereNotIn('slug', $activeFolderSlugs)
            ->update(['is_active' => false]);

        $this->command?->info("Documentos legales: {$count} requisitos activos en ".count($folderIds).' carpetas.');
        $this->command?->info("Requisitos desactivados (fuera de matriz): {$deactivated}.");
    }
}
