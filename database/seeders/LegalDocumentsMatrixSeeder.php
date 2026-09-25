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
        foreach ($payload['folders'] ?? [] as $folder) {
            $slug = (string) ($folder['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
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

            LegalDocumentItem::query()->updateOrCreate($match, [
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
            $count++;
        }

        $this->command?->info("Documentos legales: {$count} requisitos en ".count($folderIds).' carpetas.');
    }
}
