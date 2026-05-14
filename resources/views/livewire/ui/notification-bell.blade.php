<div class="relative" wire:poll.5s="syncInbox">
    @php
        $bellAccent = $unreadCount > 0
            ? 'ring-2 ring-offset-2 ring-indigo-400 dark:ring-cyan-500 ring-offset-white dark:ring-offset-dash-void'
            : '';
    @endphp
    <button type="button"
            wire:click="toggle"
            class="relative inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-dash-muted dark:hover:bg-white/10 dark:hover:text-white {{ $bellAccent }}"
            title="Notificaciones"
            aria-label="Notificaciones">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
             stroke="currentColor" class="h-6 w-6 shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 01-5.714 0" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute -top-1 -right-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-rose-500 px-[5px] text-[11px] font-bold leading-none text-white shadow-sm">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    @if ($open)
        <div class="fixed inset-0 z-40 lg:hidden" wire:click="close" aria-hidden="true"></div>
        <div class="fixed right-4 top-[4.75rem] z-50 flex w-[min(100vw-2rem,22rem)] max-h-[min(70vh,28rem)] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-white/15 dark:bg-dash-ink/95 lg:absolute lg:right-0 lg:top-full lg:mt-2">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">Notificaciones</p>
                <div class="flex shrink-0 items-center gap-2">
                    @unless ($viewerIsAdmin)
                        <button type="button"
                                wire:click="markOwnUnreadRead"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-cyan-400 dark:hover:text-cyan-200">
                            Marcar leídas
                        </button>
                    @endunless
                    <button type="button" wire:click="close"
                            class="rounded p-1 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-white/10"
                            aria-label="Cerrar">&times;</button>
                </div>
            </div>

            <div class="flex flex-1 flex-col divide-y divide-slate-100 overflow-y-auto dark:divide-white/10">
                @if ($recent->isEmpty())
                    <div class="flex flex-1 items-center justify-center px-4 py-10">
                        <p class="text-center text-sm text-slate-500 dark:text-dash-muted">No hay notificaciones recientes.</p>
                    </div>
                @else
                    @foreach ($recent as $n)
                        {{-- @var \Illuminate\Notifications\DatabaseNotification $n --}}
                        @php
                            $d = is_array($n->data) ? $n->data : [];
                            $notifTitle = $d['title'] ?? 'Mensaje';
                            $bodyText = $d['body'] ?? '';
                            $actionUrl = $d['action_url'] ?? null;
                            $showUnreadDot = blank($n->read_at);
                        @endphp
                        <article class="{{ $showUnreadDot ? 'bg-indigo-50/80 dark:bg-cyan-500/10' : '' }}">
                            @if (filled($actionUrl))
                                <button type="button" wire:click="openAndMark('{{ $n->id }}')"
                                        class="w-full px-4 py-3 text-left hover:bg-slate-50 dark:hover:bg-white/5">
                                    <div class="flex items-start gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ $notifTitle }}</p>
                                            <p class="mt-0.5 text-xs leading-snug text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($bodyText, 180) }}</p>
                                            @if ($n->created_at)
                                                <p class="mt-1 text-[11px] text-slate-400 dark:text-dash-muted">{{ $n->created_at->locale(app()->getLocale())->diffForHumans() }}</p>
                                            @endif
                                        </div>
                                        @if ($showUnreadDot)
                                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-600 dark:bg-cyan-400" title="Sin leer"></span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-[11px] font-semibold text-indigo-600 dark:text-cyan-400">Abrir</p>
                                </button>
                            @else
                                <div class="px-4 py-3">
                                    <div class="flex items-start gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ $notifTitle }}</p>
                                            <p class="mt-0.5 text-xs leading-snug text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($bodyText, 180) }}</p>
                                            @if ($n->created_at)
                                                <p class="mt-1 text-[11px] text-slate-400 dark:text-dash-muted">{{ $n->created_at->locale(app()->getLocale())->diffForHumans() }}</p>
                                            @endif
                                        </div>
                                        @if ($showUnreadDot)
                                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-600 dark:bg-cyan-400" title="Sin leer"></span>
                                        @endif
                                    </div>
                                    <button type="button"
                                            wire:click="markOneRead('{{ $n->id }}')"
                                            class="mt-2 text-[11px] font-semibold text-indigo-600 dark:text-cyan-400">
                                        Marcar leída
                                    </button>
                                </div>
                            @endif
                        </article>
                    @endforeach
                @endif
            </div>
            @if ($viewerIsAdmin)
                <p class="border-t border-slate-100 bg-slate-50 px-3 py-2 text-[11px] leading-relaxed text-slate-500 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-400">
                    Como administrador ves el envío dirigido a todos los usuarios. «Marcar leídas» no aplica a bandeja global.
                </p>
            @endif
        </div>
    @endif
</div>
