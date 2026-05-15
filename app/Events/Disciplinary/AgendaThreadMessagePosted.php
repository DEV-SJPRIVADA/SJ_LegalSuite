<?php

namespace App\Events\Disciplinary;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Nuevo mensaje en el hilo de coordinación citación (abogado ↔ planeación, FO-GJ-03). */
class AgendaThreadMessagePosted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $disciplinaryCaseId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('disciplinary.case.'.$this->disciplinaryCaseId)];
    }

    public function broadcastAs(): string
    {
        return 'AgendaMessagePosted';
    }

    /**
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return ['disciplinary_case_id' => $this->disciplinaryCaseId];
    }
}
