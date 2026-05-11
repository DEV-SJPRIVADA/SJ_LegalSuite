/**
 * Alpine x-data factory for the planeación agenda reply: paste images, pick via icon (Livewire $uploadMultiple append).
 */
function disciplinaryPlanningComposer() {
    return {
        maxBytes: 5 * 1024 * 1024,
        maxFiles: 6,

        getWire(fromEl) {
            const el = fromEl ?? this.$el;
            const root = el?.closest?.('[wire\\:id]');
            if (!root || !window.Livewire) {
                return null;
            }

            const id = root.getAttribute('wire:id');

            return window.Livewire.find(id) ?? null;
        },

        openPicker() {
            this.$refs.planningFiles?.click();
        },

        pickPlanningImages(event) {
            const input = event.target;
            const picked = [...(input.files || [])].filter(
                (f) => f.type.startsWith('image/') && f.size <= this.maxBytes,
            );
            input.value = '';
            if (!picked.length) {
                return;
            }
            this.uploadPlanning(picked);
        },

        pastePlanningImages(event) {
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
                if (f && f.size <= this.maxBytes) {
                    out.push(f);
                }
            }
            if (!out.length) {
                return;
            }
            event.preventDefault();
            this.uploadPlanning(out, event.target);
        },

        uploadPlanning(files, sourceEl) {
            const $w = this.getWire(sourceEl);
            if (!$w) {
                return;
            }
            const cur = $w.$get('agendaPlanningUploads') || [];
            const n = Array.isArray(cur) ? cur.filter(Boolean).length : 0;
            if (n + files.length > this.maxFiles) {
                return;
            }
            $w.$uploadMultiple(
                'agendaPlanningUploads',
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

window.sjDisciplinaryPlanningComposer = () => disciplinaryPlanningComposer();
