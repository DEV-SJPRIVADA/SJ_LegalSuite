/**
 * Alpine x-data factory: búsqueda de municipio DIVIPOLA (FO-GJ-51).
 * Asignado en window para usar desde Blade con @js().
 */
function normalizeSearch(s) {
    return String(s || '')
        .normalize('NFD')
        .replace(/\p{M}/gu, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

/**
 * @param {Array<{code: string, name: string, dept: string}>} items
 * @param {string} initialCode
 * @param {{ required?: boolean }} opts
 */
function disciplinaryFo51MunicipalityCombo(items, initialCode = '', opts = {}) {
    const list = Array.isArray(items) ? items : [];
    const required = !!opts.required;

    return {
        items: list,
        code: typeof initialCode === 'string' ? initialCode : '',
        query: '',
        open: false,
        highlightedIndex: -1,
        required,

        init() {
            const found = this.items.find((i) => i.code === this.code);
            if (found) {
                this.query = found.name;
            }
        },

        get filtered() {
            const raw = (this.query || '').trim();
            const q = normalizeSearch(raw).replace(/ /g, '');
            if (q.length < 1) {
                return [];
            }
            const digits = raw.replace(/\D/g, '');
            const out = [];
            for (const it of this.items) {
                const nameN = normalizeSearch(it.name).replace(/ /g, '');
                const deptN = normalizeSearch(it.dept || '').replace(/ /g, '');
                const hay = `${nameN} ${deptN} ${it.code}`;
                const match =
                    nameN.includes(q) ||
                    deptN.includes(q) ||
                    hay.includes(q) ||
                    it.code.toLowerCase().includes(q) ||
                    (digits.length >= 2 && it.code.includes(digits));
                if (match) {
                    out.push(it);
                }
                if (out.length >= 120) {
                    break;
                }
            }
            // Desempate: orden alfabético por nombre
            out.sort((a, b) => String(a.name).localeCompare(String(b.name), 'es'));
            return out;
        },

        openList() {
            this.open = true;
            this.syncHighlight();
        },

        syncHighlight() {
            this.highlightedIndex = this.filtered.length > 0 ? 0 : -1;
        },

        closeList() {
            this.open = false;
            this.highlightedIndex = -1;
        },

        selectItem(it) {
            this.code = it.code;
            this.query = it.name;
            this.closeList();
        },

        onInput() {
            const prevCode = this.code;
            const prevName = this.items.find((i) => i.code === prevCode)?.name;
            if (this.query !== prevName) {
                this.code = '';
            }
            this.open = true;
            this.syncHighlight();
        },

        onBlur() {
            window.setTimeout(() => this.closeList(), 160);
        },

        onKeydown(e) {
            if (e.key === 'Escape') {
                this.closeList();
                e.preventDefault();

                return;
            }
            if (!this.open && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
                this.openList();
                e.preventDefault();

                return;
            }
            if (!this.open) {
                return;
            }
            const max = this.filtered.length - 1;
            if (e.key === 'ArrowDown') {
                this.highlightedIndex = Math.min(this.highlightedIndex + 1, max);
                e.preventDefault();
            } else if (e.key === 'ArrowUp') {
                this.highlightedIndex = Math.max(this.highlightedIndex - 1, 0);
                e.preventDefault();
            } else if (e.key === 'Enter') {
                const it = this.filtered[this.highlightedIndex];
                if (it) {
                    this.selectItem(it);
                }
                e.preventDefault();
            }
        },
    };
}

window.disciplinaryFo51MunicipalityCombo = disciplinaryFo51MunicipalityCombo;
