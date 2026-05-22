<?php

namespace App\Notifications;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Notifications\BroadcastsInAppDatabaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;

/** Expediente disciplinario creado tras autorizar FO-GJ-51. */
class InformeAuthorizedNotification extends Notification implements ShouldBroadcastNow
{
    use BroadcastsInAppDatabaseNotification, Queueable;

    public function __construct(
        public DisciplinaryCase $case,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->case->loadMissing('employee');

        return [
            'title' => 'Informe autorizado · expediente creado',
            'body' => 'El FO-GJ-51 fue autorizado.'.($this->case->employee
                ? ' Caso disciplinario n.º '.$this->case->case_number.' · '.$this->case->employee->first_name.' '.$this->case->employee->last_name.'.'
                : ' Caso n.º '.$this->case->case_number.'.'),
            'action_url' => route('disciplinary.cases.show', $this->case),
            'disciplinary_case_id' => $this->case->getKey(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
