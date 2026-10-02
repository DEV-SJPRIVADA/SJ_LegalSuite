@props([
    'lifetimeMinutes' => null,
    'warnSeconds' => 90,
])

@php
    $lifetimeMinutes = max(1, (int) ($lifetimeMinutes ?? config('session.lifetime', 15)));
    $warnSeconds = max(30, min(300, (int) $warnSeconds));
    $idleMs = $lifetimeMinutes * 60 * 1000;
    $warnMs = $warnSeconds * 1000;
@endphp

<div
    x-data="sessionIdleGuard({
        idleMs: {{ $idleMs }},
        warnMs: {{ $warnMs }},
        pingUrl: @js(route('session.ping')),
        expireUrl: @js(route('session.expire')),
        csrf: @js(csrf_token()),
    })"
    x-init="start(); return () => destroy()"
    @mousemove.window="bump()"
    @mousedown.window="bump()"
    @keydown.window="bump()"
    @keyup.window="bump()"
    @click.window="bump()"
    @scroll.window="bump()"
    @touchstart.window="bump()"
    @wheel.window="bump()"
    x-on:livewire:navigating.window="bump()"
    x-on:livewire:navigated.window="bump()"
    class="contents"
>
    <div
        x-show="warning"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="session-idle-title"
        style="display: none;"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15" @click.stop>
            <p class="text-[11px] font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400">Seguridad</p>
            <h2 id="session-idle-title" class="mt-1 text-lg font-bold text-slate-900 dark:text-white">
                Su sesión se va a cerrar
            </h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                No se detectó actividad. Por seguridad la sesión se cierra a los
                <strong>{{ $lifetimeMinutes }} minutos</strong> sin uso.
                Se cerrará en
                <span class="font-bold tabular-nums text-amber-700 dark:text-amber-300" x-text="secondsLeft"></span>
                segundos si no continúa.
            </p>
            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <button
                    type="button"
                    @click="expireNow(true)"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-white/15 dark:text-slate-200 dark:hover:bg-white/5"
                >
                    Cerrar ahora
                </button>
                <button
                    type="button"
                    @click="staySignedIn()"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Seguir conectado
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.sessionIdleGuard = function sessionIdleGuard(cfg) {
        return {
            idleMs: cfg.idleMs,
            warnMs: cfg.warnMs,
            pingUrl: cfg.pingUrl,
            expireUrl: cfg.expireUrl,
            csrf: cfg.csrf,
            warning: false,
            secondsLeft: 0,
            _idleTimer: null,
            _warnTimer: null,
            _tickTimer: null,
            _throttleAt: 0,
            _lastActivityAt: Date.now(),
            _alive: true,
            _lwHooked: false,

            start() {
                this._alive = true;
                this._lastActivityAt = Date.now();
                this.hookLivewire();
                this.resetTimers();
            },

            destroy() {
                this._alive = false;
                this.clearTimers();
            },

            hookLivewire() {
                if (this._lwHooked || typeof window.Livewire === 'undefined') {
                    // Reintentar cuando Livewire cargue.
                    if (! this._lwHooked) {
                        document.addEventListener('livewire:init', () => this.hookLivewire(), { once: true });
                    }
                    return;
                }
                this._lwHooked = true;
                Livewire.hook('request', ({ respond }) => {
                    respond(() => {
                        if (this._alive) {
                            this.bump(true);
                        }
                    });
                });
                Livewire.hook('commit', ({ succeed }) => {
                    succeed(() => {
                        if (this._alive) {
                            this.bump(true);
                        }
                    });
                });
            },

            bump(force = false) {
                const now = Date.now();
                this._lastActivityAt = now;

                if (! force && now - this._throttleAt < 800) {
                    return;
                }
                this._throttleAt = now;

                // Cualquier interacción (también con el modal visible) renueva la sesión.
                if (this.warning) {
                    this.staySignedIn();
                    return;
                }

                this.resetTimers();
            },

            clearTimers() {
                clearTimeout(this._idleTimer);
                clearTimeout(this._warnTimer);
                clearInterval(this._tickTimer);
                this._idleTimer = null;
                this._warnTimer = null;
                this._tickTimer = null;
            },

            resetTimers() {
                if (! this._alive) {
                    return;
                }
                this.clearTimers();
                this.warning = false;
                this.secondsLeft = 0;

                const warnAt = Math.max(0, this.idleMs - this.warnMs);
                this._warnTimer = setTimeout(() => this.showWarning(), warnAt);
                this._idleTimer = setTimeout(() => this.expireNow(false), this.idleMs);
            },

            showWarning() {
                if (! this._alive) {
                    return;
                }
                // Si hubo actividad reciente, no mostrar aviso (temporizador huérfano).
                if (Date.now() - this._lastActivityAt < this.idleMs - this.warnMs) {
                    this.resetTimers();
                    return;
                }
                this.warning = true;
                this.secondsLeft = Math.ceil(this.warnMs / 1000);
                clearInterval(this._tickTimer);
                this._tickTimer = setInterval(() => {
                    this.secondsLeft = Math.max(0, this.secondsLeft - 1);
                    if (this.secondsLeft <= 0) {
                        clearInterval(this._tickTimer);
                    }
                }, 1000);
            },

            async staySignedIn() {
                try {
                    await fetch(this.pingUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });
                } catch (e) {
                    // Reinicio local de todos modos.
                }
                this._lastActivityAt = Date.now();
                this.resetTimers();
            },

            expireNow(forced = false) {
                if (! this._alive && ! forced) {
                    return;
                }

                // Evitar cierre si el usuario sí estuvo activo (temporizador viejo / pestaña).
                if (! forced && (Date.now() - this._lastActivityAt) < (this.idleMs - 2000)) {
                    this.resetTimers();
                    return;
                }

                this.clearTimers();
                this._alive = false;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = this.expireUrl;
                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = this.csrf;
                form.appendChild(token);
                document.body.appendChild(form);
                form.submit();
            },
        };
    };
</script>
