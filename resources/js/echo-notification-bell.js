import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoStarted = false;

/** @type {number|null} */
let caseAgendaSubscriptionCaseId = null;

function dispatchBellRefresh() {
    const lw = window.Livewire;
    if (lw && typeof lw.dispatch === 'function') {
        lw.dispatch('notifications-updated');
    }
}

function readLiveCaseIdFromDom() {
    const el = document.querySelector('[data-live-case-id]');
    if (!el?.dataset?.liveCaseId) {
        return null;
    }
    const id = parseInt(el.dataset.liveCaseId, 10);

    return Number.isFinite(id) ? id : null;
}

/**
 * Echo guarda X-CSRF-TOKEN solo al crear la instancia; si la sesión/meta cambian,
 * `/broadcasting/auth` puede fallar y en cascada romper la app. Refrescar desde el meta.
 */
function refreshBroadcastingCsrfFromMeta() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')?.trim() ?? '';
    if (!token) {
        return;
    }
    if (window.axios?.defaults?.headers?.common) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
    const opts = window.Echo?.connector?.options;
    if (!opts?.auth?.headers) {
        return;
    }
    opts.auth.headers['X-CSRF-TOKEN'] = token;
    if (opts.userAuthentication?.headers) {
        opts.userAuthentication.headers['X-CSRF-TOKEN'] = token;
    }
}

function bindCaseAgendaPrivateChannel() {
    if (!window.Echo?.private) {
        return;
    }

    const caseId = readLiveCaseIdFromDom();

    if (caseAgendaSubscriptionCaseId === caseId) {
        return;
    }

    if (caseAgendaSubscriptionCaseId !== null) {
        try {
            window.Echo.leave(`disciplinary.case.${caseAgendaSubscriptionCaseId}`);
        } catch {
            //
        }
        caseAgendaSubscriptionCaseId = null;
    }

    if (caseId === null) {
        return;
    }

    caseAgendaSubscriptionCaseId = caseId;

    window.Echo.private(`disciplinary.case.${caseId}`).listen('.AgendaMessagePosted', () => {
        const lw = window.Livewire;
        if (lw && typeof lw.dispatch === 'function') {
            lw.dispatch('agenda-thread-refresh');
        }
    });
}

/**
 * Pusher Channels + Echo: canal privado por usuario (campanita) y `disciplinary.case.{id}` (agenda).
 */
function setupEchoNotificationBell() {
    if (echoStarted || typeof window === 'undefined') {
        return;
    }

    const cfg = window.__appBroadcasting;
    if (!cfg?.userId || !cfg.key || !cfg.cluster) {
        return;
    }

    window.Pusher = Pusher;

    const forceTls = Boolean(cfg.forceTls);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: cfg.key,
        cluster: cfg.cluster,
        forceTLS: forceTls,
        disableStats: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        },
    });

    window.Echo.private(`App.Models.User.${cfg.userId}`).notification(() => {
        dispatchBellRefresh();
    });

    echoStarted = true;
    bindCaseAgendaPrivateChannel();
}

document.addEventListener('livewire:init', () => {
    setupEchoNotificationBell();

    if (!window.Livewire?.hook) {
        return;
    }
    window.Livewire.hook('request', ({ succeed }) => {
        succeed(() => {
            refreshBroadcastingCsrfFromMeta();
        });
    });
});

document.addEventListener('livewire:navigated', () => {
    refreshBroadcastingCsrfFromMeta();
    bindCaseAgendaPrivateChannel();
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        refreshBroadcastingCsrfFromMeta();
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setupEchoNotificationBell());
} else {
    setupEchoNotificationBell();
}
