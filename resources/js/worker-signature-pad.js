/**
 * Lienzo de firma táctil para el trabajador (supervisor · evidencias pendientes).
 */
window.sjWorkerSignaturePad = function sjWorkerSignaturePad() {
    return {
        drawing: false,
        lastX: 0,
        lastY: 0,
        hasInk: false,

        init() {
            this.$nextTick(() => this.resizeCanvas());
            window.addEventListener('resize', () => this.resizeCanvas());
        },

        resizeCanvas() {
            const canvas = this.$refs.canvas;
            if (! canvas) {
                return;
            }

            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const rect = canvas.getBoundingClientRect();
            canvas.width = Math.floor(rect.width * ratio);
            canvas.height = Math.floor(rect.height * ratio);

            const ctx = canvas.getContext('2d');
            if (! ctx) {
                return;
            }

            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.scale(ratio, ratio);
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = 2.2;
            ctx.strokeStyle = '#111827';
        },

        pointerPosition(event) {
            const canvas = this.$refs.canvas;
            const rect = canvas.getBoundingClientRect();
            const clientX = event.touches?.[0]?.clientX ?? event.clientX;
            const clientY = event.touches?.[0]?.clientY ?? event.clientY;

            return {
                x: clientX - rect.left,
                y: clientY - rect.top,
            };
        },

        start(event) {
            event.preventDefault();
            const { x, y } = this.pointerPosition(event);
            const ctx = this.$refs.canvas.getContext('2d');
            this.drawing = true;
            this.lastX = x;
            this.lastY = y;
            ctx.beginPath();
            ctx.moveTo(x, y);
        },

        draw(event) {
            if (! this.drawing) {
                return;
            }

            event.preventDefault();
            const { x, y } = this.pointerPosition(event);
            const ctx = this.$refs.canvas.getContext('2d');
            ctx.lineTo(x, y);
            ctx.stroke();
            this.lastX = x;
            this.lastY = y;
            this.hasInk = true;
        },

        end() {
            this.drawing = false;
        },

        clear() {
            const canvas = this.$refs.canvas;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            this.hasInk = false;
        },

        exportDataUri() {
            if (! this.hasInk) {
                return null;
            }

            return this.$refs.canvas.toDataURL('image/png');
        },
    };
};
