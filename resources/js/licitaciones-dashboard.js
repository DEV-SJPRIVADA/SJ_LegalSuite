/**
 * Dashboard de Licitaciones: vencimientos + progreso (ApexCharts).
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

export function licitacionesDashboard(config) {
    return {
        chartDark: Boolean(config.chartDark),
        charts: {},
        vencimientos: config.vencimientos ?? { labels: [], series: [] },
        solicitudesEstado: config.solicitudesEstado ?? { labels: [], series: [], colors: [] },
        licitacionesEstado: config.licitacionesEstado ?? { labels: [], series: [] },

        init() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.mountVencimientos();
                    this.mountSolicitudes();
                    this.mountLicitaciones();
                });
            });
        },

        destroy() {
            destroyCharts(this.charts);
        },

        mountVencimientos() {
            const el = this.$refs.chartVencimientos;
            if (!el) {
                return;
            }

            const dark = this.chartDark;
            const hasData = (this.vencimientos.series ?? []).some((n) => Number(n) > 0);

            mountApex(el, {
                chart: {
                    type: 'area',
                    height: 260,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                    background: 'transparent',
                },
                series: [{
                    name: 'Vencimientos',
                    data: this.vencimientos.series ?? [],
                }],
                colors: ['#f7a823'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.45,
                        opacityTo: 0.05,
                        stops: [0, 90, 100],
                    },
                },
                stroke: { curve: 'smooth', width: 2.5 },
                dataLabels: { enabled: false },
                grid: {
                    borderColor: dark ? 'rgba(255,255,255,0.08)' : '#e2e8f0',
                    strokeDashArray: 4,
                },
                xaxis: {
                    categories: this.vencimientos.labels ?? [],
                    labels: { style: { colors: chartForeColor(dark), fontSize: '11px' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                    labels: {
                        style: { colors: chartForeColor(dark), fontSize: '11px' },
                        formatter: (v) => Math.round(v),
                    },
                },
                tooltip: {
                    theme: dark ? 'dark' : 'light',
                    y: { formatter: (v) => `${v} solicitud(es)` },
                },
                noData: {
                    text: hasData ? undefined : 'Sin vencimientos en los próximos 14 días',
                    style: { color: chartForeColor(dark) },
                },
            }, this.charts);
        },

        mountSolicitudes() {
            const el = this.$refs.chartSolicitudes;
            if (!el) {
                return;
            }

            const dark = this.chartDark;
            const series = this.solicitudesEstado.series ?? [];
            const total = series.reduce((a, b) => a + Number(b || 0), 0);

            mountApex(el, {
                chart: {
                    type: 'donut',
                    height: 260,
                    fontFamily: 'inherit',
                    background: 'transparent',
                },
                series,
                labels: this.solicitudesEstado.labels ?? [],
                colors: this.solicitudesEstado.colors ?? ['#1e2743', '#f7a823', '#059669'],
                stroke: { width: 0 },
                legend: {
                    position: 'bottom',
                    fontSize: '11px',
                    labels: { colors: chartForeColor(dark) },
                },
                dataLabels: { enabled: total > 0 },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                name: { show: true, fontSize: '12px', color: chartForeColor(dark) },
                                value: {
                                    show: true,
                                    fontSize: '20px',
                                    fontWeight: 700,
                                    color: dark ? '#f8fafc' : '#1e2743',
                                },
                                total: {
                                    show: true,
                                    label: 'En curso',
                                    fontSize: '11px',
                                    color: chartForeColor(dark),
                                    formatter: () => String(total),
                                },
                            },
                        },
                    },
                },
                tooltip: { theme: dark ? 'dark' : 'light' },
                noData: {
                    text: 'Sin solicitudes',
                    style: { color: chartForeColor(dark) },
                },
            }, this.charts);
        },

        mountLicitaciones() {
            const el = this.$refs.chartLicitaciones;
            if (!el) {
                return;
            }

            const dark = this.chartDark;

            mountApex(el, {
                chart: {
                    type: 'bar',
                    height: 260,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                    background: 'transparent',
                },
                series: [{
                    name: 'Procesos',
                    data: this.licitacionesEstado.series ?? [],
                }],
                colors: ['#1e2743'],
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '52%',
                        distributed: true,
                    },
                },
                dataLabels: { enabled: false },
                legend: { show: false },
                grid: {
                    borderColor: dark ? 'rgba(255,255,255,0.08)' : '#e2e8f0',
                    strokeDashArray: 4,
                },
                xaxis: {
                    categories: this.licitacionesEstado.labels ?? [],
                    labels: {
                        style: { colors: chartForeColor(dark), fontSize: '10px' },
                        rotate: -25,
                        trim: true,
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                    labels: {
                        style: { colors: chartForeColor(dark), fontSize: '11px' },
                        formatter: (v) => Math.round(v),
                    },
                },
                tooltip: {
                    theme: dark ? 'dark' : 'light',
                    y: { formatter: (v) => `${v} licitación(es)` },
                },
            }, this.charts);
        },
    };
}

window.licitacionesDashboard = licitacionesDashboard;

export default licitacionesDashboard;
