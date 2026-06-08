/**
 * Lightbox para adjuntos del hilo de agenda (imagen: zoom con rueda; PDF: iframe).
 */
function agendaAttachmentLightbox() {
    return {
        open: false,
        src: '',
        alt: '',
        previewKind: 'image',
        downloadUrl: '',
        scale: 1,
        minScale: 0.25,
        maxScale: 6,

        contextOpen: false,
        contextX: 0,
        contextY: 0,
        contextDownloadUrl: '',

        openLightbox(src, alt = '', kind = 'image', downloadUrl = '') {
            this.openAgendaAttachment({
                src,
                alt,
                kind,
                downloadUrl: downloadUrl || src,
            });
        },

        openAgendaAttachment(detail) {
            if (!detail?.src) {
                return;
            }

            this.closeImageContextMenu();
            this.src = detail.src;
            this.alt = detail.alt || '';
            this.previewKind = detail.kind === 'pdf' ? 'pdf' : 'image';
            this.downloadUrl = detail.downloadUrl || detail.src;
            this.scale = 1;
            this.open = true;
            document.body.classList.add('overflow-hidden');
        },

        closeLightbox() {
            this.open = false;
            this.src = '';
            this.alt = '';
            this.previewKind = 'image';
            this.downloadUrl = '';
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

        downloadPreview() {
            if (!this.downloadUrl) {
                return;
            }
            const a = document.createElement('a');
            a.href = this.downloadUrl;
            a.rel = 'noopener';
            if (this.previewKind === 'pdf') {
                a.target = '_blank';
            }
            document.body.appendChild(a);
            a.click();
            a.remove();
        },

        wheelZoom(event) {
            if (!this.open || this.previewKind !== 'image') {
                return;
            }
            event.preventDefault();
            const step = event.deltaY > 0 ? -0.15 : 0.15;
            this.scale = Math.min(this.maxScale, Math.max(this.minScale, this.scale + step));
        },
    };
}

window.sjAgendaAttachmentLightbox = () => agendaAttachmentLightbox();
