@props([
    'message',
    'case' => null,
    'thread' => null,
    'selectableSlots' => false,
    /** lawyer = jurídico a la derecha; planning = planeación a la derecha */
    'perspective' => 'lawyer',
])

@php
    use App\Enums\Disciplinary\AgendaMessageKind;

    $kind = $message->message_kind;
    $badge = match ($kind) {
        AgendaMessageKind::LAWYER_REQUEST => 'Solicitud de fechas',
        AgendaMessageKind::PLANNING_RESPONSE => 'Respuesta de Planeación',
        AgendaMessageKind::LAWYER_NOTIFICATION_REQUEST => 'Solicitud de notificación',
        AgendaMessageKind::NOTIFICATION_COORDINATION => 'Notificación coordinada',
        AgendaMessageKind::DECISION_PLANNING_RESPONSE => 'Planeación · decisión',
        AgendaMessageKind::DECISION_NOTIFICATION_COORDINATION => 'Notificación decisión',
        default => null,
    };

    $lawyerKinds = [
        AgendaMessageKind::LAWYER_REQUEST,
        AgendaMessageKind::LAWYER_NOTIFICATION_REQUEST,
    ];
    $planningKinds = [
        AgendaMessageKind::PLANNING_RESPONSE,
        AgendaMessageKind::NOTIFICATION_COORDINATION,
        AgendaMessageKind::DECISION_PLANNING_RESPONSE,
        AgendaMessageKind::DECISION_NOTIFICATION_COORDINATION,
    ];

    $authorId = (int) ($message->user_id ?? 0);
    $authId = (int) (auth()->id() ?? 0);
    $perspective = $perspective === 'planning' ? 'planning' : 'lawyer';

    if ($kind === AgendaMessageKind::GENERAL) {
        $mine = $authId > 0 && $authorId === $authId;
    } elseif ($perspective === 'planning') {
        $mine = in_array($kind, $planningKinds, true);
    } else {
        $mine = in_array($kind, $lawyerKinds, true);
    }

    $slots = $message->normalizedProposedSlots();
    $payload = $message->normalizedNotificationPayload();
    $isDecisionPlanning = $kind === AgendaMessageKind::DECISION_PLANNING_RESPONSE;
    $isNotificationCoordination = in_array($kind, [
        AgendaMessageKind::NOTIFICATION_COORDINATION,
        AgendaMessageKind::DECISION_NOTIFICATION_COORDINATION,
    ], true);
    $decisionMeasurePayload = $isDecisionPlanning ? array_filter([
        'suspension_start' => $payload['suspension_start'] ?? null,
        'suspension_end' => $payload['suspension_end'] ?? null,
        'relief_notes' => $payload['relief_notes'] ?? null,
    ]) : [];
    $slotsHeading = $isDecisionPlanning
        ? 'Opciones para notificar al trabajador'
        : 'Fechas propuestas';

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

    $authorName = $message->author?->name ?? '—';
    $initials = collect(preg_split('/\s+/', trim($authorName)) ?: [])
        ->filter()
        ->take(2)
        ->map(fn (string $p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
    if ($initials === '') {
        $initials = $mine ? 'YO' : 'PL';
    }

    $bubble = $mine
        ? 'rounded-2xl rounded-br-md bg-indigo-600 text-white dark:bg-indigo-500'
        : 'rounded-2xl rounded-bl-md bg-white text-slate-800 ring-1 ring-slate-200/90 dark:bg-white/[0.07] dark:text-slate-100 dark:ring-white/10';
    $metaAlign = $mine ? 'text-right' : 'text-left';
    $slotBox = $mine
        ? 'rounded-lg bg-white/15 px-2.5 py-2 text-indigo-50 ring-1 ring-white/20'
        : 'rounded-lg bg-indigo-50/90 px-2.5 py-2 text-indigo-950 ring-1 ring-indigo-200/70 dark:bg-indigo-950/40 dark:text-indigo-100 dark:ring-indigo-500/30';
    $slotMuted = $mine ? 'text-indigo-100/85' : 'text-indigo-700/90 dark:text-indigo-200/80';
    $measureBox = $mine
        ? 'rounded-lg bg-white/15 px-2.5 py-2 text-xs text-indigo-50 ring-1 ring-white/20'
        : 'rounded-lg bg-fuchsia-50/90 px-2.5 py-2 text-xs text-fuchsia-950 ring-1 ring-fuchsia-200/70 dark:bg-fuchsia-950/40 dark:text-fuchsia-100 dark:ring-fuchsia-500/30';
    $notifBox = $mine
        ? 'rounded-lg bg-white/15 px-2.5 py-2 text-xs text-indigo-50 ring-1 ring-white/20'
        : 'rounded-lg bg-violet-50/90 px-2.5 py-2 text-xs text-violet-950 ring-1 ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-100 dark:ring-violet-500/30';
@endphp

<li {{ $attributes->merge(['class' => 'flex list-none items-end gap-2 '.($mine ? 'flex-row-reverse' : 'flex-row')]) }}>
    <div
        class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold
            {{ $mine
                ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                : 'bg-slate-200 text-slate-700 dark:bg-white/15 dark:text-slate-200' }}"
        title="{{ $authorName }}"
        aria-hidden="true"
    >{{ $initials }}</div>

    <div class="flex max-w-[82%] flex-col gap-1 {{ $mine ? 'items-end' : 'items-start' }}">
        <p class="px-0.5 text-[10px] text-slate-500 dark:text-slate-400 {{ $metaAlign }}">
            <span class="font-medium text-slate-600 dark:text-slate-300">{{ $authorName }}</span>
            · {{ $message->created_at?->format('d/m H:i') }}
            @if ($badge)
                · {{ $badge }}
            @endif
        </p>

        <div class="px-3 py-2.5 text-sm leading-relaxed {{ $bubble }}">
            @if ($bodyForDisplay !== '')
                <p class="whitespace-pre-wrap">{{ $bodyForDisplay }}</p>
            @endif

            @if ($slots !== [])
                <div class="{{ $bodyForDisplay !== '' ? 'mt-2.5' : '' }} {{ $slotBox }}">
                    <p class="text-[10px] font-bold uppercase tracking-wide opacity-90">{{ $slotsHeading }}</p>
                    <ul class="mt-1.5 space-y-1.5">
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
                                } elseif ($date !== '') {
                                    try {
                                        $label = \Illuminate\Support\Carbon::parse($date)->format('d/m/Y');
                                    } catch (\Throwable) {
                                        $label = $date;
                                    }
                                }
                                $slotKey = $message->id.'-'.$index;
                            @endphp
                            <li>
                                @if ($selectableSlots && $date !== '')
                                    <label class="flex cursor-pointer items-start gap-2 rounded-md px-0.5 py-0.5 hover:opacity-90">
                                        <input type="radio" name="citation_slot_from_chat" value="{{ $slotKey }}"
                                            wire:model.live="selectedCitationSlotKey"
                                            class="mt-0.5 border-slate-300 text-indigo-600 focus:ring-indigo-500
                                                {{ $mine ? 'border-white/40 bg-white/10 text-white focus:ring-white/40' : '' }}">
                                        <span>
                                            <span class="font-semibold">{{ $label }}</span>
                                            @if (! empty($slot['notes']))
                                                <span class="block text-xs {{ $slotMuted }}">Turno: {{ $slot['notes'] }}</span>
                                            @endif
                                            @if (! empty($slot['zone']))
                                                <span class="block text-xs {{ $slotMuted }}">Zona: {{ $slot['zone'] }}</span>
                                            @endif
                                            @if (! empty($slot['supervisor_name']))
                                                <span class="block text-xs {{ $slotMuted }}">Supervisor: {{ $slot['supervisor_name'] }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @else
                                    <span class="flex gap-2">
                                        <span class="opacity-70">•</span>
                                        <span>
                                            <span class="font-semibold">{{ $label }}</span>
                                            @if (! empty($slot['notes']))
                                                <span class="block text-xs {{ $slotMuted }}">Turno: {{ $slot['notes'] }}</span>
                                            @endif
                                            @if (! empty($slot['zone']))
                                                <span class="block text-xs {{ $slotMuted }}">Zona: {{ $slot['zone'] }}</span>
                                            @endif
                                            @if (! empty($slot['supervisor_name']))
                                                <span class="block text-xs {{ $slotMuted }}">Supervisor: {{ $slot['supervisor_name'] }}</span>
                                            @endif
                                        </span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($decisionMeasurePayload !== [])
                <dl class="{{ ($bodyForDisplay !== '' || $slots !== []) ? 'mt-2.5' : '' }} grid gap-1 {{ $measureBox }}">
                    @if (! empty($decisionMeasurePayload['suspension_start']) || ! empty($decisionMeasurePayload['suspension_end']))
                        <div>
                            <span class="font-semibold">Periodo de suspensión:</span>
                            {{ $decisionMeasurePayload['suspension_start'] ?? '—' }} — {{ $decisionMeasurePayload['suspension_end'] ?? '—' }}
                        </div>
                    @endif
                    @if (! empty($decisionMeasurePayload['relief_notes']))
                        <div><span class="font-semibold">Relevo:</span> {{ $decisionMeasurePayload['relief_notes'] }}</div>
                    @endif
                </dl>
            @endif

            @if ($isNotificationCoordination && $payload !== [])
                <dl class="{{ ($bodyForDisplay !== '' || $slots !== [] || $decisionMeasurePayload !== []) ? 'mt-2.5' : '' }} grid gap-1 {{ $notifBox }}">
                    <div><span class="font-semibold">Fecha ingreso trabajador:</span> {{ $payload['notification_date'] ?? '—' }}</div>
                    <div><span class="font-semibold">Turno:</span> {{ $payload['notification_shift'] ?? '—' }}</div>
                    <div><span class="font-semibold">Zona:</span> {{ $payload['notification_zone'] ?? '—' }}</div>
                    <div><span class="font-semibold">Supervisor:</span> {{ $payload['notification_supervisor_name'] ?? '—' }}</div>
                    @if (! empty($payload['notification_notes']))
                        <div><span class="font-semibold">Observaciones:</span> {{ $payload['notification_notes'] }}</div>
                    @endif
                </dl>
            @endif

            @if ($message->relationLoaded('attachments') && $message->attachments->isNotEmpty())
                <div class="{{ ($bodyForDisplay !== '' || $slots !== [] || $decisionMeasurePayload !== [] || ($isNotificationCoordination && $payload !== [])) ? 'mt-2.5' : '' }} flex flex-wrap gap-2">
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
                                class="group relative h-14 w-14 shrink-0 overflow-hidden rounded-lg ring-1 ring-black/10 transition hover:ring-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 dark:ring-white/20"
                                x-on:click="openLightbox(@js($inlineUrl), @js($attLabel))"
                                x-on:contextmenu.prevent="openImageContextMenu($event, @js($downloadUrl))">
                                <img src="{{ $inlineUrl }}" alt="{{ $attLabel }}" loading="lazy"
                                    class="pointer-events-none h-full w-full object-cover">
                            </button>
                        @elseif ($downloadUrl)
                            <button type="button"
                                title="{{ $attLabel }} — clic para ver"
                                class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-white/90 px-1 text-center ring-1 ring-black/10 transition hover:ring-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 dark:bg-dash-lift dark:ring-white/20"
                                x-on:click="openLightbox(@js($downloadUrl), @js($attLabel), 'pdf', @js($downloadUrl))">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2 5 5h-4V4zM8 12h8v2H8v-2zm0 4h5v2H8v-2z"/>
                                </svg>
                                <span class="mt-0.5 max-w-full truncate px-0.5 text-[9px] font-semibold {{ $mine ? 'text-indigo-800' : 'text-indigo-700 dark:text-indigo-300' }}">PDF</span>
                            </button>
                        @else
                            <span class="text-xs opacity-90">{{ $attLabel }}</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</li>
