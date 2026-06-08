@props(['message', 'case' => null, 'thread' => null, 'selectableSlots' => false])

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
    $bodyForDisplay = trim((string) $message->body);
    if ($slots !== []) {
        $commentLines = collect(preg_split('/\r\n|\n/', $bodyForDisplay) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== ''
                && ! str_starts_with($line, '•')
                && $line !== 'Planeación propone fechas de diligencia disponibles:'
                && $line !== 'Planeación propone fechas de diligencia disponibles.');
        $bodyForDisplay = $commentLines->implode("\n");
    }
@endphp

<li {{ $attributes->merge(['class' => 'rounded-md border border-slate-200 px-3 py-2 dark:border-white/10']) }}>
    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $badge[1] }}">{{ $badge[0] }}</span>
            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $message->author?->name }}</span>
        </div>
        <span>{{ $message->created_at?->format('d/m/Y H:i') }}</span>
    </div>
    @if ($bodyForDisplay !== '')
        <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200">{{ $bodyForDisplay }}</p>
    @endif

    @if ($slots !== [])
        <div class="mt-3 rounded-md bg-indigo-50/80 px-3 py-2 ring-1 ring-indigo-200/80 dark:bg-indigo-950/30 dark:ring-indigo-500/30">
            <p class="text-[10px] font-bold uppercase tracking-wide text-indigo-800 dark:text-indigo-200">Fechas propuestas</p>
            <ul class="mt-2 space-y-1.5 text-sm text-indigo-900 dark:text-indigo-100">
                @foreach ($slots as $index => $slot)
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
                        $slotKey = $message->id.'-'.$index;
                    @endphp
                    <li>
                        @if ($selectableSlots && $date !== '')
                            <label class="flex cursor-pointer items-start gap-2 rounded-md border border-transparent px-1 py-1 hover:border-indigo-300/60 dark:hover:border-indigo-400/40">
                                <input type="radio" name="citation_slot_from_chat" value="{{ $slotKey }}"
                                    wire:model.live="selectedCitationSlotKey"
                                    class="mt-0.5 border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span>
                                    <span class="font-semibold">{{ $label }}</span>
                                    @if (! empty($slot['notes']))
                                        <span class="block text-xs text-indigo-700/90 dark:text-indigo-200/80">{{ $slot['notes'] }}</span>
                                    @endif
                                </span>
                            </label>
                        @else
                            <span class="flex gap-2">
                                <span class="text-indigo-600 dark:text-indigo-300">•</span>
                                <span>
                                    <span class="font-semibold">{{ $label }}</span>
                                    @if (! empty($slot['notes']))
                                        <span class="block text-xs text-indigo-700/90 dark:text-indigo-200/80">{{ $slot['notes'] }}</span>
                                    @endif
                                </span>
                            </span>
                        @endif
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
                @php
                    if ($thread) {
                        $inlineUrl = $att->isImage()
                            ? route('disciplinary.coordinations.attachments.inline', [$thread, $att])
                            : null;
                        $downloadUrl = route('disciplinary.coordinations.attachments.download', [$thread, $att]);
                    } elseif ($case) {
                        $inlineUrl = $att->isImage()
                            ? route('disciplinary.cases.agenda-attachment.inline', [$case, $att])
                            : null;
                        $downloadUrl = route('disciplinary.cases.agenda-attachment.download', [$case, $att]);
                    } else {
                        $inlineUrl = null;
                        $downloadUrl = null;
                    }
                    $attLabel = $att->original_name;
                @endphp
                @if ($inlineUrl)
                    <button type="button"
                        title="{{ $attLabel }} — clic para ampliar"
                        class="group relative h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100 ring-indigo-500/0 transition hover:ring-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-white/15 dark:bg-black/40"
                        x-on:click="openLightbox(@js($inlineUrl), @js($attLabel))"
                        x-on:contextmenu.prevent="openImageContextMenu($event, @js($downloadUrl))">
                        <img src="{{ $inlineUrl }}" alt="{{ $attLabel }}" loading="lazy"
                            class="pointer-events-none h-full w-full object-cover transition group-hover:brightness-95 dark:group-hover:brightness-110">
                    </button>
                @elseif ($downloadUrl)
                    <button type="button"
                        title="{{ $attLabel }} — clic para ver"
                        class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-lg border border-slate-200 bg-white px-1 text-center transition hover:ring-2 hover:ring-indigo-400/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-white/15 dark:bg-dash-lift"
                        x-on:click="openLightbox(@js($downloadUrl), @js($attLabel), 'pdf', @js($downloadUrl))">
                        <svg class="h-7 w-7 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2 5 5h-4V4zM8 12h8v2H8v-2zm0 4h5v2H8v-2z"/>
                        </svg>
                        <span class="mt-0.5 max-w-full truncate px-0.5 text-[9px] font-semibold text-indigo-700 dark:text-indigo-300">PDF</span>
                    </button>
                @else
                    <span class="text-xs text-slate-600 dark:text-slate-400">{{ $attLabel }}</span>
                @endif
            @endforeach
        </div>
    @endif
</li>
