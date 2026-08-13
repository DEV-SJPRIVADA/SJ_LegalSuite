/**
 * Progreso animado de carga masiva: contador registro a registro, barra y ETA suaves.
 */
function bulkImportProgressDisplay(progressState) {
    return {
        progress: progressState,
        displayed: 0,
        etaLabel: 'Calculando tiempo…',
        animateTimer: null,
        pollTimer: null,
        inFlight: false,
        startedAtMs: null,

        init() {
            this.$watch('progress.processed_rows', (value) => {
                this.animateToward(Number(value) || 0);
            });

            this.$watch('progress.status', (status) => {
                if (status === 'completed') {
                    this.snapToComplete();
                }
            });

            this.$watch('progress.started_at', (value) => {
                if (value) {
                    this.startedAtMs = Date.parse(value) || null;
                }
            });
        },

        startPolling(wire) {
            const run = async () => {
                if (this.inFlight || wire.bulkImportRunning !== true) {
                    return;
                }

                this.inFlight = true;
                try {
                    await wire.advanceBulkImport();
                } finally {
                    this.inFlight = false;
                }
            };

            run();
            this.pollTimer = window.setInterval(run, 500);
        },

        animateToward(target) {
            const goal = Math.max(0, target);

            if (this.animateTimer) {
                clearInterval(this.animateTimer);
                this.animateTimer = null;
            }

            if (this.displayed >= goal) {
                this.displayed = goal;
                this.refreshEta();

                return;
            }

            this.animateTimer = window.setInterval(() => {
                if (this.displayed < goal) {
                    this.displayed += 1;
                    this.refreshEta();
                } else {
                    clearInterval(this.animateTimer);
                    this.animateTimer = null;
                }
            }, 32);
        },

        snapToComplete() {
            const total = Number(this.progress.total_rows) || 0;
            if (this.animateTimer) {
                clearInterval(this.animateTimer);
                this.animateTimer = null;
            }
            this.displayed = total;
            this.etaLabel = 'Completado';
        },

        refreshEta() {
            const status = this.progress.status || '';
            const total = Number(this.progress.total_rows) || 0;

            if (status === 'completed') {
                this.etaLabel = 'Completado';

                return;
            }

            if (status === 'failed') {
                this.etaLabel = '—';

                return;
            }

            if (total <= 0 || this.displayed <= 0) {
                this.etaLabel = this.progress.eta_label || 'Calculando tiempo…';

                return;
            }

            const startedMs = this.startedAtMs ?? (this.progress.started_at ? Date.parse(this.progress.started_at) : null);
            if (! startedMs) {
                this.etaLabel = 'Calculando tiempo…';

                return;
            }

            const elapsedSec = Math.max(1, (Date.now() - startedMs) / 1000);
            const rate = this.displayed / elapsedSec;
            const remaining = Math.max(0, total - this.displayed);
            const etaSec = Math.ceil(remaining / Math.max(rate, 0.5));

            this.etaLabel = this.formatEta(etaSec);
        },

        formatEta(seconds) {
            if (seconds <= 3) {
                return 'Finalizando…';
            }

            if (seconds < 60) {
                return `Aprox. ${seconds} s restantes`;
            }

            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;

            return `Aprox. ${minutes} min ${secs} s restantes`;
        },

        get percent() {
            const total = Number(this.progress.total_rows) || 0;
            if (total <= 0) {
                return 0;
            }

            if (this.progress.status === 'completed') {
                return 100;
            }

            return Math.min(99, Math.floor((this.displayed / total) * 100));
        },

        get phaseLabel() {
            return this.progress.phase_label || 'Importando empleados…';
        },

        destroy() {
            if (this.animateTimer) {
                clearInterval(this.animateTimer);
            }
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
            }
        },
    };
}

window.bulkImportProgressDisplay = bulkImportProgressDisplay;
