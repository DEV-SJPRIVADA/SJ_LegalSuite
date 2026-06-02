@props(['message', 'case' => null, 'thread' => null])

@php
    use App\Enums\Disciplinary\AgendaMessageKind;
    $kind = $message->message_kind;
    $badge = match ($kind) {
        AgendaMessageKind::LAWYER_REQUEST => ['Solicitud de fechas', 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-100'],
        AgendaMessageKind::PLANNING_RESPONSE => ['Respuesta de Planeación', 'bg-indigo-100 text-indigo-900 dark:bg-indigo-900/40 dark:text-indigo-100'],
        AgendaMessageKind::LAWYER_NOTIFICATION_REQUEST => ['Solicitud de notificación', 'bg-amber-100 text-amber-950 dark:bg-amber-900/40 dark:text-amber-100'],
        AgendaMessageKind::NOTIFICATION_COORDINATION => ['Notificación coordinada', 'bg-violet-100 text-violet-900 dark:bg-violet-900/40 dark:text-violet-100'],
        default => ['Mensaje', 'bg-slate-100 text-slate-800 dark:bg-white/10 dark:text-slate-200'],
    };
    $slots = $message->normalizedProposedSlots();
    $payload = $message->normalizedNotificationPayload();
@endphp

<li {{ $attributes->merge(['class' => 'rounded-md border border-slate-200 px-3 py-2 dark:border-white/10']) }}>
    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $badge[1] }}">{{ $badge[0] }}</span>
            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $message->author?->name }}</span>
        </div>
        <span>{{ $message->created_at?->format('d/m/Y H:i') }}</span>
    </div>
    <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200">{{ $message->body }}</p>

    @if ($slots !== [])
        <div class="mt-3 rounded-md bg-indigo-50/80 px-3 py-2 ring-1 ring-indigo-200/80 dark:bg-indigo-950/30 dark:ring-indigo-500/30">
            <p class="text-[10px] font-bold uppercase tracking-wide text-indigo-800 dark:text-indigo-200">Fechas propuestas</p>
            <ul class="mt-2 space-y-1.5 text-sm text-indigo-900 dark:text-indigo-100">
                @foreach ($slots as $slot)
                    @php
                        $date = (string) ($slot['date'] ?? '');
                        $time = isset($slot['time']) && $slot['time'] !== '' ? (string) $slot['time'] : '';
                        $label = $date;
                        if ($date !== '' && $time !== '') {
                            try {
                                $label = \Illuminate\Support\Carbon::parse($date.' '.$time)->format('d/m/Y — h:i A');
                            } catch (\Throwable) {
                                $label = trim($date.' '.$time);
                            }
                        }
                    @endphp
                    <li class="flex gap-2">
                        <span class="text-indigo-600 dark:text-indigo-300">•</span>
                        <span>
                            <span class="font-semibold">{{ $label }}</span>
                            @if (!empty($slot['notes']))
                                <span class="block text-xs text-indigo-700/90 dark:text-indigo-200/80">{{ $slot['notes'] }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($payload !== [])
        <dl class="mt-3 grid gap-1 rounded-md bg-violet-50/80 px-3 py-2 text-xs text-violet-900 ring-1 ring-violet-200/80 dark:bg-violet-950/30 dark:text-violet-100 dark:ring-violet-500/30">
            <div><span class="font-semibold">Fecha ingreso trabajador:</span> {{ $payload['notification_date'] ?? '—' }}</div>
            <div><span class="font-semibold">Turno:</span> {{ $payload['notification_shift'] ?? '—' }}</div>
            <div><span class="font-semibold">Zona:</span> {{ $payload['notification_zone'] ?? '—' }}</div>
            <div><span class="font-semibold">Supervisor:</span> {{ $payload['notification_supervisor_name'] ?? '—' }}</div>
            @if (!empty($payload['notification_notes']))
                <div><span class="font-semibold">Observaciones:</span> {{ $payload['notification_notes'] }}</div>
            @endif
        </dl>
    @endif

    @if ($message->relationLoaded('attachments') && $message->attachments->isNotEmpty())
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach ($message->attachments as $att)
                @if ($thread)
                    <a href="{{ route('disciplinary.coordinations.attachments.download', [$thread, $att]) }}"
                        class="text-xs font-semibold text-indigo-700 hover:underline dark:text-cyan-300">
                        {{ $att->original_name }}
                    </a>
                @elseif ($case)
                    <a href="{{ route('disciplinary.cases.agenda-attachment.download', [$case, $att]) }}"
                        class="text-xs font-semibold text-indigo-700 hover:underline dark:text-cyan-300">
                        {{ $att->original_name }}
                    </a>
                @else
                    <span class="text-xs text-slate-600 dark:text-slate-400">{{ $att->original_name }}</span>
                @endif
            @endforeach
        </div>
    @endif
</li>
