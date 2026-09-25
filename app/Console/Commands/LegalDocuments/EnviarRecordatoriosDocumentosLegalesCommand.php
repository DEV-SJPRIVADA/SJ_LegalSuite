<?php

namespace App\Console\Commands\LegalDocuments;

use App\Models\LegalDocuments\LegalDocumentItem;
use App\Notifications\LegalDocuments\LegalDocumentRenewalReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class EnviarRecordatoriosDocumentosLegalesCommand extends Command
{
    protected $signature = 'legal-documents:enviar-recordatorios
                            {--dry-run : Lista sin enviar}';

    protected $description = 'Recordatorios horarios de documentos legales vencidos/por renovar (excluye área Jurídica).';

    public function handle(): int
    {
        $items = LegalDocumentItem::query()
            ->active()
            ->with(['folder.responsible', 'currentFile'])
            ->whereHas('folder', fn ($q) => $q->where('exclude_reminders', false)->where('is_active', true))
            ->whereNotNull('renew_on')
            ->whereDate('renew_on', '<=', now()->toDateString())
            ->orderBy('folder_id')
            ->orderBy('renew_on')
            ->get()
            ->filter(fn (LegalDocumentItem $item) => $item->needsReminder());

        if ($items->isEmpty()) {
            $this->info('Sin recordatorios de documentos legales.');

            return self::SUCCESS;
        }

        $grouped = $items->groupBy('folder_id');
        $this->info('Carpetas a notificar: '.$grouped->count().' · docs: '.$items->count());

        foreach ($grouped as $folderItems) {
            /** @var \Illuminate\Support\Collection<int, LegalDocumentItem> $folderItems */
            $folder = $folderItems->first()?->folder;
            if (! $folder) {
                continue;
            }

            $emails = $folder->reminderRecipients();
            if ($emails === []) {
                $this->warn("- {$folder->name}: sin correo de director asignado");
                continue;
            }

            $this->line("- {$folder->name}: ".$folderItems->count().' docs → '.implode(', ', $emails));

            if ($this->option('dry-run')) {
                continue;
            }

            foreach ($emails as $email) {
                Notification::route('mail', $email)
                    ->notify(new LegalDocumentRenewalReminderNotification(
                        $folderItems->values()->all(),
                        $folder->name,
                    ));
            }

            LegalDocumentItem::query()
                ->whereIn('id', $folderItems->pluck('id'))
                ->update(['last_reminder_at' => now()]);
        }

        return self::SUCCESS;
    }
}
