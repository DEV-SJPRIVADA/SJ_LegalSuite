/**
 * Dashboard disciplinario: donas por etapa, mapa Colombia y gráfica por tipo de falta.
 */

function chartForeColor(dark) {
    return dark ? '#94a3b8' : '#64748b';
}

function mountApex(el, opts, store, key) {
    if (!el || !window.ApexCharts) {
        return null;
    }

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

function waitForWidth(el, mountFn, maxTries = 72) {
    let tries = 0;
    const attempt = () => {
        tries++;
        if (!el || !el.isConnected) {
            if (tries < maxTries) {
                requestAnimationFrame(attempt);
            }
            return;
        }
        const w = Math.floor(Math.max(el.offsetWidth || 0, el.getBoundingClientRect?.().width || 0));
        if (w < 32 && tries < maxTries) {
            requestAnimationFrame(attempt);
            return;
        }
        mountFn(Math.max(96, w || 140));
    };
    requestAnimationFrame(() => requestAnimationFrame(attempt));
}

function donutChartHeight() {
    return typeof window !== 'undefined' && window.matchMedia('(min-width: 1024px)').matches ? 168 : 148;
}

function baseDonutOptions(chartDark, chartW, chartH, fg, hair) {
    return {
        chart: {
            type: 'donut',
            height: chartH,
            width: chartW,
            offsetY: 0,
            parentHeightOffset: 0,
            fontFamily: 'Figtree, ui-sans-serif, system-ui',
            foreColor: fg,
            background: 'transparent',
        },
        theme: { mode: chartDark ? 'dark' : 'light' },
        stroke: { width: 1, colors: [hair, hair] },
        legend: { show: false },
        dataLabels: { enabled: false },
        plotOptions: {
            pie: {
                offsetY: -8,
                customScale: 1,
                expandOnClick: false,
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            color: chartDark ? '#cbd5e1' : '#64748b',
                            fontSize: '11px',
                            fontWeight: 600,
                            offsetY: -4,
                        },
                        value: {
                            show: true,
                            color: chartDark ? '#f8fafc' : '#0f172a',
                            fontSize: '22px',
                            fontWeight: 700,
                            offsetY: 2,
                        },
                        total: {
                            show: true,
                            showAlways: true,
                            color: chartDark ? '#cbd5e1' : '#64748b',
                            fontSize: '11px',
                            fontWeight: 600,
                        },
                    },
                },
            },
        },
        tooltip: {
            theme: chartDark ? 'dark' : 'light',
            y: { formatter: (val) => `${val} caso(s)` },
        },
    };
}

function mountTotalDonut(el, config, store) {
    const chartDark = config.chartDark;
    const wTotal = Number(config.workflow?.total ?? 0);
    const totalNeon = { from: '#fcd34d', to: '#b45309', shadow: '#fcd34d' };
    const fg = chartForeColor(chartDark);
    const hair = chartDark ? 'rgba(15,23,42,0.28)' : 'rgba(255,255,255,0.55)';
    const chartH = donutChartHeight();

    waitForWidth(el, (chartW) => {
        const opts = baseDonutOptions(chartDark, chartW, chartH, fg, hair);
        opts.chart.dropShadow = chartDark
            ? { enabled: true, top: 3, blur: 10, opacity: 0.32, color: totalNeon.shadow }
            : { enabled: false };
        opts.labels = ['Casos', ''];
        opts.series = [wTotal, 0];
        opts.colors = [totalNeon.from, totalNeon.from];
        opts.fill = {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                type: 'horizontal',
                shadeIntensity: chartDark ? 0.72 : 0.55,
                opacityFrom: 1,
                opacityTo: chartDark ? 0.92 : 0.92,
                gradientToColors: [totalNeon.to, totalNeon.to],
            },
        };
        opts.plotOptions.pie.donut.labels.total.label = '100%';
        opts.plotOptions.pie.donut.labels.total.formatter = () => String(wTotal);
        mountApex(el, opts, store, 'donut-total');
    });
}

function mountStageDonut(el, stage, config, store) {
    const chartDark = config.chartDark;
    const palette = config.stagePalette?.[stage.letter] ?? { from: '#818cf8', to: '#4338ca', shadow: '#818cf8' };
    const fg = chartForeColor(chartDark);
    const hair = chartDark ? 'rgba(15,23,42,0.28)' : 'rgba(255,255,255,0.55)';
    const restFill = chartDark ? 'rgba(51,65,85,0.55)' : '#e2e8f0';
    const restFillTo = chartDark ? 'rgba(30,41,59,0.85)' : '#cbd5e1';
    const chartH = donutChartHeight();
    const active = Number(stage.count ?? 0);
    const rest = Number(stage.rest ?? 0);
    const pct = `${stage.percent_label ?? '0'}%`;

    waitForWidth(el, (chartW) => {
        const opts = baseDonutOptions(chartDark, chartW, chartH, fg, hair);
        opts.chart.dropShadow = chartDark
            ? { enabled: true, top: 3, blur: 10, opacity: 0.32, color: palette.shadow }
            : { enabled: false };
        opts.labels = ['En etapa', 'Resto'];
        opts.series = [active, rest];
        opts.colors = [palette.from, restFill];
        opts.fill = {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                type: 'horizontal',
                shadeIntensity: chartDark ? 0.72 : 0.55,
                opacityFrom: 1,
                opacityTo: chartDark ? 0.92 : 0.92,
                gradientToColors: [palette.to, restFillTo],
            },
        };
        opts.plotOptions.pie.donut.labels.total.label = pct;
        opts.plotOptions.pie.donut.labels.total.formatter = () => String(active);
        mountApex(el, opts, store, `donut-${stage.letter}`);
    });
}

export function disciplinaryDashboard(config) {
    return {
        chartDark: Boolean(config.chartDark),
        charts: {},
        highlightedMunicipality: null,

        init() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.mountWorkflowDonuts();
                });
            });
        },

        destroy() {
            destroyCharts(this.charts);
        },

        mountWorkflowDonuts() {
            const root = this.$refs.workflowDonuts;
            if (!root || !config.workflow?.stages) {
                return;
            }

            const totalEl = root.querySelector('[data-workflow-donut="total"]');
            if (totalEl) {
                mountTotalDonut(totalEl, config, this.charts);
            }

            config.workflow.stages.forEach((stage) => {
                const el = root.querySelector(`[data-workflow-donut="${stage.letter}"]`);
                if (el) {
                    mountStageDonut(el, stage, config, this.charts);
                }
            });
        },

        focusMunicipality(code) {
            const el = document.getElementById('disciplinary-colombia-map');
            const map = el?.__disciplinaryColombiaLeafletMap;
            const marker = el?.__disciplinaryColombiaMapMarkersByCode?.[String(code)];
            if (!map || !marker) {
                return;
            }

            this.highlightedMunicipality = String(code);
            map.flyTo(marker.getLatLng(), Math.max(map.getZoom(), 8), { duration: 0.65 });
            marker.openTooltip();
        },
    };
}

window.disciplinaryDashboard = disciplinaryDashboard;

export default disciplinaryDashboard;
