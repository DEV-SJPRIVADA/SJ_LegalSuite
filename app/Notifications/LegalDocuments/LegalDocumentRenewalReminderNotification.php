<?php

namespace App\Notifications\LegalDocuments;

use App\Models\LegalDocuments\LegalDocumentItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LegalDocumentRenewalReminderNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<LegalDocumentItem>  $items
     */
    public function __construct(
        public array $items,
        public string $folderName,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Recordatorio · Documentos legales por actualizar · '.$this->folderName)
            ->greeting('Hola,')
            ->line('Hay documentos legales de su área (**'.$this->folderName.'**) que requieren actualización según la matriz MT-GJ-06.')
            ->line('Documentos pendientes:');

        foreach (array_slice($this->items, 0, 15) as $item) {
            $due = $item->renew_on?->format('d/m/Y') ?? ($item->renew_label ?: '—');
            $mail->line('• '.$item->displayTitle().' · renovar: '.$due);
        }

        if (count($this->items) > 15) {
            $mail->line('… y '.(count($this->items) - 15).' más.');
        }

        return $mail
            ->action('Abrir Documentos Legales', route('licitaciones.documentos-legales.index'))
            ->line('Este aviso se reenvía cada hora hasta que se cargue la versión actualizada (el archivo anterior se descarta al reemplazar).')
            ->salutation('SJ LegalSuite');
    }
}
