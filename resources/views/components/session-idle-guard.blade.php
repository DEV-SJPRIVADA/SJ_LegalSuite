@props([
    'lifetimeMinutes' => null,
    'warnSeconds' => 60,
])

@php
    $lifetimeMinutes = max(1, (int) ($lifetimeMinutes ?? config('session.lifetime', 15)));
    $warnSeconds = max(15, min(300, (int) $warnSeconds));
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
    x-init="start()"
    @mousemove.window="bump()"
    @keydown.window="bump()"
    @click.window="bump()"
    @scroll.window="bump()"
    @touchstart.window="bump()"
    @livewire:navigating.window="bump()"
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
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
            <p class="text-[11px] font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400">Seguridad</p>
            <h2 id="session-idle-title" class="mt-1 text-lg font-bold text-slate-900 dark:text-white">
                Su sesión se va a cerrar
            </h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                No se detectó actividad. Por seguridad la sesión se cierra a los
                <strong>{{ $lifetimeMinutes }} minutos</strong>.
                Se cerrará en
                <span class="font-bold tabular-nums text-amber-700 dark:text-amber-300" x-text="secondsLeft"></span>
                segundos.
            </p>
            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <button
                    type="button"
                    @click="expireNow()"
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

            start() {
                this.resetTimers();
            },

            bump() {
                const now = Date.now();
                if (now - this._throttleAt < 1000) {
                    return;
                }
                this._throttleAt = now;
                if (this.warning) {
                    return;
                }
                this.resetTimers();
            },

            resetTimers() {
                clearTimeout(this._idleTimer);
                clearTimeout(this._warnTimer);
                clearInterval(this._tickTimer);
                this.warning = false;
                this.secondsLeft = 0;

                const warnAt = Math.max(0, this.idleMs - this.warnMs);
                this._warnTimer = setTimeout(() => this.showWarning(), warnAt);
                this._idleTimer = setTimeout(() => this.expireNow(), this.idleMs);
            },

            showWarning() {
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
                    // Continuar con reinicio local.
                }
                this.resetTimers();
            },

            expireNow() {
                clearTimeout(this._idleTimer);
                clearTimeout(this._warnTimer);
                clearInterval(this._tickTimer);

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
