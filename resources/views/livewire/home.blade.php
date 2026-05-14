@php
    $chartDark = ($uiTheme ?? 'light') === 'dark';
@endphp
<div>
    <div class="py-8 sm:py-10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
                <x-dashboard.page-heading
                    eyebrow="Inicio · SJ LegalSuite"
                    title="Hola, {{ explode(' ', auth()->user()->name)[0] }}"
                    description="Resumen operativo con datos en vivo del sistema y alertas que requieren tu atención." />

                <div class="text-right">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-indigo-600 dark:text-cyan-400/90">Hoy</p>
                    <p class="text-sm text-slate-600 mt-1 dark:text-slate-300">{{ now()->locale('es')->translatedFormat('l, d \\d\\e F \\d\\e Y') }}</p>
                </div>
            </div>

            <section class="mb-10">
                <h2 class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 mb-4 flex items-center gap-2 dark:text-dash-muted">
                    <span class="h-2 w-2 rounded-full bg-indigo-500 dark:bg-fuchsia-500 {{ $chartDark ? 'shadow-dash-glow-fuchsia' : '' }}"></span>
                    Alertas en tiempo real
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-home.alert-card
                        variant="{{ $chartDark ? 'dash' : 'light' }}"
                        :count="$summary['vencidos']['count']"
                        title="Plazos vencidos"
                        subtitle="Etapas con deadline pasado"
                        icon="clock"
                        color="rose"
                        :items="$summary['vencidos']['items']" />
                    <x-home.alert-card
                        variant="{{ $chartDark ? 'dash' : 'light' }}"
                        :count="$summary['proximos']['count']"
                        title="Próximos a vencer"
                        subtitle="Plazo en 3 días o menos"
                        icon="flag"
                        color="amber"
                        :items="$summary['proximos']['items']" />
                    <x-home.alert-card
                        variant="{{ $chartDark ? 'dash' : 'light' }}"
                        :count="$summary['sin_asignar']['count']"
                        title="Sin abogado"
                        subtitle="Casos sin asignar"
                        icon="inbox"
                        color="indigo"
                        :items="$summary['sin_asignar']['items']" />
                    <x-home.alert-card
                        variant="{{ $chartDark ? 'dash' : 'light' }}"
                        :count="$summary['pendientes_decision']['count']"
                        title="Pend. decisión"
                        subtitle="Esperando resolución"
                        icon="scale"
                        color="sky"
                        :items="$summary['pendientes_decision']['items']" />
                </div>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <x-dashboard.card class="lg:col-span-2 xl:col-span-3" title="Tendencia mensual · casos abiertos"
                    subtitle="Últimos 6 meses · aperturas registradas (disciplinario). Barras con tus datos en vivo.">
                    @if (collect($trend)->sum('total') === 0)
                        <p class="text-sm text-slate-500 py-14 text-center dark:text-dash-muted">Aún no hay datos suficientes para graficar.</p>
                    @else
                        @php
                            $trendLabels = collect($trend)->pluck('month')->all();
                            $trendValues = collect($trend)->pluck('total')->all();
                            $n = count($trendValues);
                            $barNeonTop = ['#22d3ee', '#f472b6', '#fb923c', '#c084fc', '#34d399', '#38bdf8', '#fcd34d', '#e879f9', '#2dd4bf', '#818cf8'];
                            $barNeonBottom = ['#0e7490', '#9f1239', '#c2410c', '#6d28d9', '#047857', '#0369a1', '#b45309', '#a21caf', '#0f766e', '#3730a3'];
                            $colorsFrom = array_slice($barNeonTop, 0, $n);
                            $colorsTo = array_slice($barNeonBottom, 0, $n);
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-dash-muted">Por mes · aperturas</p>
                            <span class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold tabular-nums text-indigo-900 dark:border-transparent dark:bg-gradient-to-r dark:from-fuchsia-500/15 dark:to-cyan-500/15 dark:text-fuchsia-200 dark:ring-1 dark:ring-fuchsia-400/25 dark:shadow-[0_0_20px_-8px_rgba(217,70,239,0.55)]">
                                Total {{ number_format(collect($trend)->sum('total')) }} en el periodo
                            </span>
                        </div>
                        <div wire:ignore class="relative"
                             x-data="{
                                chart: null,
                                init() {
                                    const chartDark = @json($chartDark);
                                    const values = @js($trendValues);
                                    const labels = @js($trendLabels);
                                    const colorsFrom = @js($colorsFrom);
                                    const colorsTo = @js($colorsTo);
                                    const fg = chartDark ? '#94a3b8' : '#64748b';
                                    const grid = chartDark ? 'rgba(148,163,184,0.1)' : '#e2e8f0';
                                    const opts = {
                                        chart: {
                                            type: 'bar',
                                            height: 352,
                                            toolbar: { show: false },
                                            zoom: { enabled: false },
                                            fontFamily: 'Figtree, ui-sans-serif, system-ui',
                                            foreColor: fg,
                                            background: 'transparent',
                                            dropShadow: chartDark ? {
                                                enabled: true,
                                                top: 14,
                                                left: 0,
                                                blur: 18,
                                                opacity: 0.35,
                                                color: '#e879f9',
                                            } : { enabled: false },
                                        },
                                        theme: { mode: chartDark ? 'dark' : 'light' },
                                        grid: {
                                            borderColor: grid,
                                            strokeDashArray: 4,
                                            padding: { top: 36, right: 12, bottom: 4, left: 8 },
                                        },
                                        plotOptions: {
                                            bar: {
                                                borderRadius: 14,
                                                columnWidth: '62%',
                                                distributed: true,
                                                rangeBarOverlap: false,
                                            },
                                        },
                                        colors: colorsFrom,
                                        fill: {
                                            type: 'gradient',
                                            gradient: {
                                                shade: 'dark',
                                                type: 'vertical',
                                                shadeIntensity: chartDark ? 0.85 : 0.55,
                                                opacityFrom: 1,
                                                opacityTo: chartDark ? 0.82 : 0.88,
                                                stops: [0, 55, 100],
                                                inverseColors: false,
                                                gradientToColors: colorsTo,
                                            },
                                        },
                                        states: {
                                            hover: { filter: { type: 'lighten', value: 0.12 } },
                                            active: { filter: { type: 'none', value: 0 } },
                                        },
                                        dataLabels: {
                                            enabled: true,
                                            formatter: (val) => (val > 0 ? val : ''),
                                            offsetY: -26,
                                            style: {
                                                fontSize: '12px',
                                                fontWeight: 700,
                                                colors: chartDark ? ['#f8fafc'] : ['#0f172a'],
                                            },
                                            dropShadow: chartDark ? {
                                                enabled: true,
                                                top: 1,
                                                blur: 4,
                                                opacity: 0.55,
                                                color: '#000',
                                            } : { enabled: false },
                                        },
                                        series: [{ name: 'Aperturas del mes', data: values }],
                                        xaxis: {
                                            categories: labels,
                                            labels: {
                                                style: { colors: chartDark ? '#9ca3c9' : '#475569', fontSize: '11px', fontWeight: 600 },
                                            },
                                            axisBorder: { show: false },
                                            axisTicks: { show: false },
                                        },
                                        yaxis: {
                                            labels: {
                                                style: { colors: chartDark ? '#8b93b3' : '#64748b' },
                                                formatter: (val) => Math.round(val),
                                            },
                                            tickAmount: 5,
                                        },
                                        tooltip: {
                                            theme: chartDark ? 'dark' : 'light',
                                            y: {
                                                formatter: (val) => val + ' caso(s)',
                                            },
                                        },
                                    };
                                    this.chart = new ApexCharts(this.$refs.target, opts);
                                    this.chart.render();
                                },
                             }">
                            <div x-ref="target" class="min-h-[360px]"></div>
                        </div>
                    @endif
                </x-dashboard.card>

                @php
                    $disciplinaryCaseModel = \App\Models\Disciplinary\DisciplinaryCase::class;
                @endphp
                <x-dashboard.card title="Acceso rápido" subtitle="Módulos activos">
                    <div class="space-y-3">
                        @can('viewDashboard', $disciplinaryCaseModel)
                            <a href="{{ route('disciplinary.dashboard') }}" wire:navigate
                               class="group flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 transition hover:border-indigo-300 hover:bg-white hover:shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-cyan-400/45 dark:hover:shadow-dash-glow-cyan">
                                <div class="h-10 w-10 rounded-xl bg-indigo-100 text-indigo-600 ring-1 ring-indigo-200 flex items-center justify-center dark:bg-gradient-to-br dark:from-cyan-400/25 dark:to-fuchsia-500/20 dark:text-cyan-200 dark:ring-cyan-400/30">
                                    <x-app-sidebar-icon name="chart-bar" class="h-5 w-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 group-hover:text-indigo-700 dark:text-white dark:group-hover:text-cyan-200">Dashboard disciplinario</p>
                                    <p class="text-xs text-slate-500 mt-0.5 dark:text-dash-muted">KPIs y distribución por falta y ciudad</p>
                                </div>
                            </a>
                        @endcan
                        @can('viewAny', $disciplinaryCaseModel)
                            <a href="{{ route('disciplinary.cases.index') }}" wire:navigate
                               class="group flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 transition hover:border-indigo-300 hover:bg-white hover:shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-fuchsia-400/45 dark:hover:shadow-dash-glow-fuchsia">
                                <div class="h-10 w-10 rounded-xl bg-rose-100 text-rose-600 ring-1 ring-rose-200 flex items-center justify-center dark:bg-gradient-to-br dark:from-fuchsia-500/25 dark:to-orange-400/15 dark:text-fuchsia-200 dark:ring-fuchsia-400/30">
                                    <x-app-sidebar-icon name="scale" class="h-5 w-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 group-hover:text-indigo-700 dark:text-white dark:group-hover:text-fuchsia-200">Casos disciplinarios</p>
                                    <p class="text-xs text-slate-500 mt-0.5 dark:text-dash-muted">Listado, filtros y gestión del proceso</p>
                                </div>
                            </a>
                        @endcan
                    </div>

                    <p class="text-xs text-slate-500 mt-5 leading-relaxed border-t border-slate-100 pt-4 dark:border-white/10 dark:text-slate-500">
                        Más módulos jurídicos se sumarán al mismo tablero cuando estén disponibles.
                    </p>
                </x-dashboard.card>
            </section>
        </div>
    </div>

</div>
