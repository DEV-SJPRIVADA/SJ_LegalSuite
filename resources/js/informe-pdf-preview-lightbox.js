/**
 * Zoom de evidencias en el modal de vista previa PDF (informes pendientes).
 */
window.sjInformePdfPreviewLightbox = function informePdfPreviewLightbox() {
    return {
        zoomOpen: false,
        zoomSrc: '',
        zoomAlt: '',
        zoomScale: 1,
        minZoom: 0.25,
        maxZoom: 6,

        openZoom(src, alt) {
            this.zoomSrc = src;
            this.zoomAlt = alt || '';
            this.zoomScale = 1;
            this.zoomOpen = true;
            document.body.classList.add('overflow-hidden');
        },

        closeZoom() {
            this.zoomOpen = false;
            this.zoomSrc = '';
            this.zoomAlt = '';
            this.zoomScale = 1;
            document.body.classList.remove('overflow-hidden');
        },

        wheelZoom(event) {
            if (!this.zoomOpen) {
                return;
            }
            event.preventDefault();
            const step = event.deltaY > 0 ? -0.15 : 0.15;
            this.zoomScale = Math.min(this.maxZoom, Math.max(this.minZoom, this.zoomScale + step));
        },
    };
};
