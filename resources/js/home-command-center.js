/**
 * Command center de inicio (super admin): gráficas Apex compactas + mapa Colombia + panel de alertas.
 */

function chartForeColor(dark) {
    return dark ? '#94a3b8' : '#64748b';
}

function mountApex(el, opts, store) {
    if (!el || !window.ApexCharts) {
        return null;
    }

    const key = el.dataset.chartKey;
    if (key && store[key]) {
        try {
            store[key].destroy();
        } catch {
            //
        }
        delete store[key];
    }

    const chart = new window.ApexCharts(el, opts);
    chart.render();
    el._apexChart = chart;
    if (key) {
        store[key] = chart;
    }

    requestAnimationFrame(() => {
        try {
            chart.resize();
        } catch {
            //
        }
    });

    return chart;
}

function destroyCharts(store) {
    Object.keys(store).forEach((key) => {
        try {
            store[key]?.destroy();
        } catch {
            //
        }
        delete store[key];
    });
}

export function homeCommandCenter(config) {
    return {
        chartDark: Boolean(config.chartDark),
        activeBucket: null,
        charts: {},
        summary: config.summary ?? {},
        highlightedMunicipality: null,

        init() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.mountTrend();
                    this.mountStages();
                    this.mountLawyers();
                    this.mountHomeMap();
                });
            });
        },

        destroy() {
            destroyCharts(this.charts);
            this.teardownHomeMap();
        },

        openBucket(bucket) {
            this.activeBucket = this.activeBucket === bucket ? null : bucket;
        },

        bucketItems(bucket) {
            return this.summary?.[bucket]?.items ?? [];
        },

        bucketCount(bucket) {
            return Number(this.summary?.[bucket]?.count ?? 0);
        },

        teardownHomeMap() {
            const el = this.$refs.homeMap;
            if (el?.__disciplinaryColombiaMapTeardown) {
                el.__disciplinaryColombiaMapTeardown();
            }
        },

        mountHomeMap() {
            const el = this.$refs.homeMap;
            if (!el || el.dataset.colombiaMapMounted === '1') {
                return;
            }

            import('./disciplinary-colombia-map.js')
                .then((m) => {
                    const live = this.$refs.homeMap;
                    if (!live || !live.isConnected || live.dataset.colombiaMapMounted === '1') {
                        return;
                    }
                    return m.mountDisciplinaryColombiaMap(live);
                })
                .catch((err) => {
                    console.error('[home-colombia-map]', err);
                });
        },

        focusMunicipality(code) {
            const el = this.$refs.homeMap;
            const map = el?.__disciplinaryColombiaLeafletMap;
            const marker = el?.__disciplinaryColombiaMapMarkersByCode?.[String(code)];
            if (!map || !marker) {
                return;
            }

            this.highlightedMunicipality = String(code);
            const targetZoom = Math.max(map.getZoom(), 8);
            map.flyTo(marker.getLatLng(), targetZoom, { duration: 0.65 });
            marker.openTooltip();
        },

        mountTrend() {
            const el = this.$refs.trendChart;
            if (!el || !config.trend?.length) {
                return;
            }

            const dark = this.chartDark;
            const values = config.trend.map((r) => r.total);
            const labels = config.trend.map((r) => r.month);
            const w = Math.max(200, el.offsetWidth || 320);

            mountApex(el, {
                chart: {
                    type: 'area',
                    height: 118,
                    width: w,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    fontFamily: 'Figtree, ui-sans-serif, system-ui',
                    foreColor: chartForeColor(dark),
                    background: 'transparent',
                    sparkline: { enabled: false },
                },
                theme: { mode: dark ? 'dark' : 'light' },
                stroke: { curve: 'smooth', width: 2.5 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 0.65,
                        opacityFrom: dark ? 0.45 : 0.35,
                        opacityTo: 0.02,
                        stops: [0, 90, 100],
                    },
                },
                colors: [dark ? '#22d3ee' : '#4f46e5'],
                series: [{ name: 'Aperturas', data: values }],
                grid: {
                    borderColor: dark ? 'rgba(148,163,184,0.12)' : '#e2e8f0',
                    strokeDashArray: 4,
                    padding: { top: 4, right: 8, bottom: 0, left: 4 },
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: labels,
                    labels: { style: { fontSize: '10px', fontWeight: 600 } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        formatter: (v) => Math.round(v),
                        style: { fontSize: '10px' },
                    },
                    tickAmount: 3,
                },
                tooltip: {
                    theme: dark ? 'dark' : 'light',
                    y: { formatter: (v) => `${v} caso(s)` },
                },
            }, this.charts);
        },

        mountStages() {
            const el = this.$refs.stagesChart;
            const stages = config.workflow?.stages ?? [];
            if (!el || stages.length === 0) {
                return;
            }

            const dark = this.chartDark;
            const categories = stages.map((s) => `Etapa ${s.letter}`);
            const values = stages.map((s) => s.count);
            const colors = ['#818cf8', '#fb923c', '#22d3ee', '#e879f9', '#f472b6', '#34d399'];
            const w = Math.max(200, el.offsetWidth || 320);

            mountApex(el, {
                chart: {
                    type: 'bar',
                    height: 130,
                    width: w,
                    toolbar: { show: false },
                    fontFamily: 'Figtree, ui-sans-serif, system-ui',
                    foreColor: chartForeColor(dark),
                    background: 'transparent',
                },
                theme: { mode: dark ? 'dark' : 'light' },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 6,
                        barHeight: '62%',
                        distributed: true,
                    },
                },
                colors: colors.slice(0, values.length),
                series: [{ name: 'Casos', data: values }],
                grid: {
                    borderColor: dark ? 'rgba(148,163,184,0.12)' : '#e2e8f0',
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: false } },
                    padding: { left: 4, right: 12 },
                },
                dataLabels: {
                    enabled: true,
                    formatter: (v) => (v > 0 ? v : ''),
                    style: { fontSize: '10px', fontWeight: 700 },
                    offsetX: 4,
                },
                xaxis: {
                    categories,
                },
                yaxis: {
                    labels: {
                        style: { fontSize: '10px' },
                    },
                },
                legend: { show: false },
                tooltip: { theme: dark ? 'dark' : 'light' },
            }, this.charts);
        },

        mountLawyers() {
            const el = this.$refs.lawyersChart;
            const rows = (config.lawyerWorkload ?? []).filter((r) => r.total > 0);
            if (!el || rows.length === 0) {
                return;
            }

            const dark = this.chartDark;
            const categories = rows.map((r) => r.lawyer_name);
            const values = rows.map((r) => r.total);
            const w = Math.max(200, el.offsetWidth || 320);

            mountApex(el, {
                chart: {
                    type: 'bar',
                    height: Math.min(88, 28 + rows.length * 16),
                    width: w,
                    toolbar: { show: false },
                    fontFamily: 'Figtree, ui-sans-serif, system-ui',
                    foreColor: chartForeColor(dark),
                    background: 'transparent',
                },
                theme: { mode: dark ? 'dark' : 'light' },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 5,
                        barHeight: '65%',
                    },
                },
                colors: [dark ? '#c084fc' : '#7c3aed'],
                series: [{ name: 'Casos asignados', data: values }],
                grid: {
                    borderColor: dark ? 'rgba(148,163,184,0.12)' : '#e2e8f0',
                    padding: { left: 4, right: 12 },
                },
                dataLabels: {
                    enabled: true,
                    formatter: (v) => (v > 0 ? v : ''),
                    style: { fontSize: '10px', fontWeight: 700 },
                    offsetX: 4,
                },
                xaxis: { categories },
                yaxis: {
                    labels: {
                        style: { fontSize: '10px' },
                        maxWidth: 120,
                    },
                },
                tooltip: { theme: dark ? 'dark' : 'light' },
            }, this.charts);
        },
    };
}

window.homeCommandCenter = homeCommandCenter;

export default homeCommandCenter;
