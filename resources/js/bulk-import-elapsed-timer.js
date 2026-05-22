/**
 * Contador de tiempo para overlay de carga masiva (empleados).
 */
function bulkImportElapsedTimer() {
    return {
        seconds: 0,
        interval: null,

        get label() {
            if (this.seconds < 60) {
                return `${this.seconds} s`;
            }
            const m = Math.floor(this.seconds / 60);
            const s = this.seconds % 60;

            return `${m} min ${s} s`;
        },

        start() {
            this.stop();
            this.seconds = 0;
            this.interval = setInterval(() => {
                this.seconds += 1;
            }, 1000);
        },

        stop() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        },
    };
}

window.bulkImportElapsedTimer = bulkImportElapsedTimer;
