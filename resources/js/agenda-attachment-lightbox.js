/**
 * Lightbox para miniaturas de adjuntos del hilo de agenda (zoom con rueda, Escape / clic fuera cierra).
 */
function agendaAttachmentLightbox() {
    return {
        open: false,
        src: '',
        alt: '',
        scale: 1,
        minScale: 0.25,
        maxScale: 6,

        contextOpen: false,
        contextX: 0,
        contextY: 0,
        contextDownloadUrl: '',

        openLightbox(src, alt) {
            this.closeImageContextMenu();
            this.src = src;
            this.alt = alt || '';
            this.scale = 1;
            this.open = true;
            document.body.classList.add('overflow-hidden');
        },

        closeLightbox() {
            this.open = false;
            this.src = '';
            this.alt = '';
            this.scale = 1;
            document.body.classList.remove('overflow-hidden');
            this.closeImageContextMenu();
        },

        openImageContextMenu(event, downloadUrl) {
            event.preventDefault();
            event.stopPropagation();
            this.contextDownloadUrl = downloadUrl;
            const menuW = 224;
            const menuH = 48;
            let x = event.clientX;
            let y = event.clientY;
            if (x + menuW > window.innerWidth) {
                x = Math.max(8, window.innerWidth - menuW - 8);
            }
            if (y + menuH > window.innerHeight) {
                y = Math.max(8, window.innerHeight - menuH - 8);
            }
            this.contextX = x;
            this.contextY = y;
            this.contextOpen = true;
        },

        closeImageContextMenu() {
            this.contextOpen = false;
            this.contextDownloadUrl = '';
        },

        downloadFromContextMenu() {
            if (!this.contextDownloadUrl) {
                return;
            }
            const a = document.createElement('a');
            a.href = this.contextDownloadUrl;
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            a.remove();
            this.closeImageContextMenu();
        },

        wheelZoom(event) {
            if (!this.open) {
                return;
            }
            event.preventDefault();
            const step = event.deltaY > 0 ? -0.15 : 0.15;
            this.scale = Math.min(this.maxScale, Math.max(this.minScale, this.scale + step));
        },
    };
}

window.sjAgendaAttachmentLightbox = () => agendaAttachmentLightbox();
