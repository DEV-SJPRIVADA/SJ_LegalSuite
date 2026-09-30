<?php

namespace App\Services\LegalDocuments;

use App\Models\LegalDocuments\LegalDocumentActivity;
use App\Models\LegalDocuments\LegalDocumentFolder;
use App\Models\LegalDocuments\LegalDocumentItem;
use App\Models\User;

class LegalDocumentActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function log(
        LegalDocumentFolder $folder,
        string $action,
        string $summary,
        ?User $actor = null,
        ?LegalDocumentItem $item = null,
        ?array $meta = null,
    ): LegalDocumentActivity {
        return LegalDocumentActivity::query()->create([
            'folder_id' => $folder->id,
            'item_id' => $item?->id,
            'user_id' => $actor?->id,
            'action' => $action,
            'summary' => $summary,
            'meta' => $meta,
        ]);
    }
}
