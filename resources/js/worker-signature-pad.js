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

            const canvas = this.$refs.canvas;
            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;
            const pixels = ctx.getImageData(0, 0, width, height).data;

            let minX = width;
            let minY = height;
            let maxX = 0;
            let maxY = 0;

            for (let y = 0; y < height; y++) {
                for (let x = 0; x < width; x++) {
                    const alpha = pixels[((y * width) + x) * 4 + 3];
                    if (alpha > 0) {
                        minX = Math.min(minX, x);
                        minY = Math.min(minY, y);
                        maxX = Math.max(maxX, x);
                        maxY = Math.max(maxY, y);
                    }
                }
            }

            if (maxX < minX || maxY < minY) {
                return null;
            }

            const pad = Math.max(4, Math.floor(Math.max(window.devicePixelRatio || 1, 1) * 4));
            minX = Math.max(0, minX - pad);
            minY = Math.max(0, minY - pad);
            maxX = Math.min(width - 1, maxX + pad);
            maxY = Math.min(height - 1, maxY + pad);

            const cropWidth = maxX - minX + 1;
            const cropHeight = maxY - minY + 1;
            const cropped = document.createElement('canvas');
            cropped.width = cropWidth;
            cropped.height = cropHeight;
            cropped.getContext('2d').drawImage(
                canvas,
                minX,
                minY,
                cropWidth,
                cropHeight,
                0,
                0,
                cropWidth,
                cropHeight,
            );

            return cropped.toDataURL('image/png');
        },
    };
};
