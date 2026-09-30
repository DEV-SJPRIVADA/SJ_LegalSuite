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
            ->line('**Documentos pendientes:**');

        $lines = [];
        foreach (array_slice($this->items, 0, 15) as $item) {
            $due = $item->renew_on?->format('d/m/Y') ?? ($item->renew_label ?: '—');
            $lines[] = '• '.$item->displayTitle().' · renovar: **'.$due.'**';
        }

        if (count($this->items) > 15) {
            $lines[] = '… y '.(count($this->items) - 15).' más.';
        }

        $mail->line(implode("\n", $lines));

        return $mail
            ->action(
                'Abrir Documentos Legales',
                rtrim((string) config('app.mail_url', config('app.url')), '/').'/licitaciones/documentos-legales'
            )
            ->line('Mientras no suba o reemplace el archivo, **seguirá recibiendo correos** (cada hora; entre las **16:00** y las **17:00**, cada 10 minutos). Al cargar la versión actualizada las alertas se detienen.')
            ->salutation('SJ LegalSuite');
    }
}
