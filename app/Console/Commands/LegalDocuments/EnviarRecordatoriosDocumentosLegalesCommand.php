<?php

namespace App\Console\Commands\LegalDocuments;

use App\Models\LegalDocuments\LegalDocumentItem;
use App\Notifications\LegalDocuments\LegalDocumentRenewalReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class EnviarRecordatoriosDocumentosLegalesCommand extends Command
{
    protected $signature = 'legal-documents:enviar-recordatorios
                            {--folder= : Slug o ID de carpeta (opcional)}
                            {--dry-run : Lista sin enviar}
                            {--force : Ignora el intervalo y reenvía si sigue vencido sin archivo}';

    protected $description = 'Recordatorios de documentos legales vencidos según la frecuencia configurada en cada carpeta.';

    public function handle(): int
    {
        $folderFilter = trim((string) $this->option('folder'));
        $force = (bool) $this->option('force');

        $items = LegalDocumentItem::query()
            ->active()
            ->with(['folder.responsible', 'currentFile'])
            ->whereHas('folder', function ($q) use ($folderFilter) {
                $q->where('exclude_reminders', false)->where('is_active', true);
                if ($folderFilter !== '') {
                    if (ctype_digit($folderFilter)) {
                        $q->whereKey((int) $folderFilter);
                    } else {
                        $q->where('slug', $folderFilter);
                    }
                }
            })
            ->whereNotNull('renew_on')
            ->whereDate('renew_on', '<=', now()->toDateString())
            ->orderBy('folder_id')
            ->orderBy('renew_on')
            ->get()
            ->filter(function (LegalDocumentItem $item) use ($force) {
                if ($force) {
                    return $item->isDueForRenewal()
                        && $item->folder
                        && ! $item->folder->exclude_reminders
                        && ! $item->hasFreshFileForCurrentRenewal();
                }

                return $item->needsReminder();
            });

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

            $this->line("- {$folder->name}: ".$folderItems->count().' docs → '.implode(', ', $emails)
                .' (cada '.$folder->reminderIntervalMinutes().' min)');

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
