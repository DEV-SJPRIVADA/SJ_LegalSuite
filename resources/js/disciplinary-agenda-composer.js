/**
 * Composer del hilo agenda (abogado / planeación): clip, paste, drag-drop, Livewire $uploadMultiple.
 */
function disciplinaryAgendaComposer(config = {}) {
    const uploadsProperty = config.uploadsProperty ?? 'agendaPlanningUploads';
    const maxBytes = config.maxBytes ?? 10 * 1024 * 1024;
    const maxFiles = config.maxFiles ?? 6;
    const acceptMimePrefixes = ['image/', 'application/pdf'];

    return {
        uploadsProperty,
        maxBytes,
        maxFiles,
        dragOver: false,

        getWire(fromEl) {
            const el = fromEl ?? this.$el;
            const root = el?.closest?.('[wire\\:id]');
            if (!root || !window.Livewire) {
                return null;
            }

            return window.Livewire.find(root.getAttribute('wire:id')) ?? null;
        },

        isAllowedFile(file) {
            if (!file || file.size > this.maxBytes) {
                return false;
            }

            return acceptMimePrefixes.some((prefix) => file.type.startsWith(prefix));
        },

        openPicker() {
            this.$refs.agendaFiles?.click();
        },

        pickFiles(event) {
            const picked = [...(event.target.files || [])].filter((f) => this.isAllowedFile(f));
            event.target.value = '';
            if (!picked.length) {
                return;
            }
            this.uploadFiles(picked, event.target);
        },

        pasteFiles(event) {
            const out = [];
            const items = event.clipboardData?.items;
            if (!items) {
                return;
            }
            for (const item of items) {
                if (!item.type.startsWith('image/')) {
                    continue;
                }
                const f = item.getAsFile();
                if (f && this.isAllowedFile(f)) {
                    out.push(f);
                }
            }
            if (!out.length) {
                return;
            }
            event.preventDefault();
            this.uploadFiles(out, event.target);
        },

        dropFiles(event) {
            this.dragOver = false;
            const picked = [...(event.dataTransfer?.files || [])].filter((f) => this.isAllowedFile(f));
            if (!picked.length) {
                return;
            }
            event.preventDefault();
            this.uploadFiles(picked, event.target);
        },

        uploadFiles(files, sourceEl) {
            const $w = this.getWire(sourceEl);
            if (!$w) {
                return;
            }
            const cur = $w.$get(this.uploadsProperty) || [];
            const n = Array.isArray(cur) ? cur.filter(Boolean).length : 0;
            if (n + files.length > this.maxFiles) {
                return;
            }
            $w.$uploadMultiple(
                this.uploadsProperty,
                files,
                () => {},
                () => {},
                () => {},
                () => {},
                true,
            );
        },
    };
}

window.sjDisciplinaryAgendaComposer = (config) => disciplinaryAgendaComposer(config);
window.sjDisciplinaryPlanningComposer = (config) => disciplinaryAgendaComposer(config);
