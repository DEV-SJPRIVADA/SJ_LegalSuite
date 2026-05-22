/**
 * Autocompletar empleado por número de documento (FO-GJ-51).
 */
function disciplinaryFo51EmployeeCombo(searchUrl, initialDocument = '', initialName = '', initialCargo = '', initialEmployeeId = '') {
    return {
        searchUrl,
        query: initialDocument || '',
        employeeId: initialEmployeeId ? String(initialEmployeeId) : '',
        workerName: initialName || '',
        workerCargo: initialCargo || '',
        municipalityCode: '',
        open: false,
        highlightedIndex: -1,
        filtered: [],
        debounceTimer: null,

        init() {
            this.$watch('query', () => this.scheduleSearch());
            if (this.query.length >= 2) {
                this.scheduleSearch();
            }
        },

        scheduleSearch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.fetchMatches(), 280);
        },

        async fetchMatches() {
            const q = this.query.trim();
            if (q.length < 2) {
                this.filtered = [];
                this.open = false;

                return;
            }
            try {
                const res = await fetch(`${this.searchUrl}?q=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    this.filtered = [];

                    return;
                }
                const data = await res.json();
                this.filtered = data.items || [];
                this.open = true;
                this.highlightedIndex = this.filtered.length ? 0 : -1;
            } catch {
                this.filtered = [];
            }
        },

        openList() {
            if (this.query.trim().length >= 2) {
                this.fetchMatches();
            }
        },

        sanitizeDocument(value) {
            return String(value ?? '').replace(/\D+/g, '');
        },

        onInput() {
            this.query = this.sanitizeDocument(this.query);
            this.employeeId = '';
            if (!this.query.trim()) {
                this.workerName = '';
                this.workerCargo = '';
                this.municipalityCode = '';
                this.dispatchEmployeeSelected();
            }
            this.scheduleSearch();
        },

        onBlur() {
            setTimeout(() => {
                this.open = false;
            }, 180);
        },

        selectItem(item) {
            this.query = item.document_number;
            this.employeeId = String(item.id);
            this.workerName = item.full_name || '';
            this.workerCargo = item.job_title || '';
            this.municipalityCode = item.municipality_code || '';
            this.open = false;
            this.filtered = [];
            this.dispatchEmployeeSelected();
        },

        dispatchEmployeeSelected() {
            this.$dispatch('fo51-employee-selected', {
                municipalityCode: this.municipalityCode,
            });
        },

        onKeydown(e) {
            if (!this.open || !this.filtered.length) {
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.highlightedIndex = Math.min(this.highlightedIndex + 1, this.filtered.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.highlightedIndex = Math.max(this.highlightedIndex - 1, 0);
            } else if (e.key === 'Enter' && this.highlightedIndex >= 0) {
                e.preventDefault();
                this.selectItem(this.filtered[this.highlightedIndex]);
            } else if (e.key === 'Escape') {
                this.open = false;
            }
        },
    };
}

window.disciplinaryFo51EmployeeCombo = disciplinaryFo51EmployeeCombo;
