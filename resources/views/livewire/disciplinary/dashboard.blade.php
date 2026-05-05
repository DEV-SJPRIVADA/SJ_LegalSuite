@php
    $chartDark = ($uiTheme ?? 'light') === 'dark';
@endphp
<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="py-8 sm:py-10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
                <x-dashboard.page-heading
                    eyebrow="Disciplinarios · Dashboard"
                    title="Indicadores del módulo"
                    description="Todos los valores se calculan en vivo según tus permisos y asignaciones." />

                <x-dashboard.button href="{{ route('disciplinary.cases.index') }}" variant="ghost" class="shrink-0">
                    Ver listado de casos →
                </x-dashboard.button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <x-dashboard.stat label="Total disciplinarios" :value="$kpis['total']" accent="cyan" badge="Cartera" />
                <x-dashboard.stat label="Pendientes" :value="$kpis['pendientes']" accent="orange" badge="Atención" />
                <x-dashboard.stat label="En proceso" :value="$kpis['en_proceso']" accent="fuchsia" badge="Activos" />
                <x-dashboard.stat label="Finalizados" :value="$kpis['finalizados']" accent="emerald" badge="Cerrados" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8">
                <x-dashboard.card title="Casos por tipo de falta" subtitle="Ranking según registros · barras con degradado">
                    @if (collect($byFault)->sum('total') === 0)
                        <p class="text-sm text-slate-500 dark:text-dash-muted">Aún no hay datos para graficar.</p>
                    @else
                        @php
                            $faultLabels = collect($byFault)->pluck('name')->all();
                            $faultValues = collect($byFault)->pluck('total')->all();
                            $fn = count($faultValues);
                            $faultNeonTop = ['#fb923c', '#22d3ee', '#f472b6', '#a78bfa', '#34d399', '#fcd34d', '#38bdf8', '#e879f9', '#2dd4bf', '#818cf8'];
                            $faultNeonBot = ['#9a3412', '#155e75', '#9f1239', '#5b21b6', '#166534', '#b45309', '#075985', '#86198f', '#134e4a', '#312e81'];
                            $faultFrom = [];
                            $faultTo = [];
                            for ($i = 0; $i < $fn; $i++) {
                                $faultFrom[] = $faultNeonTop[$i % count($faultNeonTop)];
                                $faultTo[] = $faultNeonBot[$i % count($faultNeonBot)];
                            }
                        @endphp
                        <div class="flex justify-end mb-2">
                            <span class="inline-flex rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1 text-[11px] font-bold tabular-nums text-orange-900 ring-1 ring-orange-100 dark:border-transparent dark:bg-orange-500/10 dark:text-orange-200 dark:ring-orange-400/25">
                                {{ number_format(collect($byFault)->sum('total')) }} casos en gráfico
                            </span>
                        </div>
                        <div wire:ignore
                             x-data="{
                                chart: null,
                                init() {
                                    const chartDark = @json($chartDark);
                                    const data = @js($faultValues);
                                    const cats = @js($faultLabels);
                                    const colorsFrom = @js($faultFrom);
                                    const colorsTo = @js($faultTo);
                                    const fg = chartDark ? '#94a3b8' : '#64748b';
                                    const grid = chartDark ? 'rgba(148,163,184,0.1)' : '#e2e8f0';
                                    const lbl = chartDark ? '#8b93b3' : '#475569';
                                    this.chart = new ApexCharts(this.$refs.target, {
                                        chart: {
                                            type: 'bar',
                                            height: 320,
                                            toolbar: { show: false },
                                            fontFamily: 'Figtree, ui-sans-serif, system-ui',
                                            foreColor: fg,
                                            background: 'transparent',
                                            dropShadow: chartDark ? {
                                                enabled: true,
                                                top: 10,
                                                left: 2,
                                                blur: 16,
                                                opacity: 0.35,
                                                color: '#fb923c',
                                            } : { enabled: false },
                                        },
                                        theme: { mode: chartDark ? 'dark' : 'light' },
                                        grid: { borderColor: grid, strokeDashArray: 4 },
                                        plotOptions: {
                                            bar: {
                                                horizontal: true,
                                                borderRadius: 10,
                                                barHeight: '78%',
                                                distributed: true,
                                            },
                                        },
                                        colors: colorsFrom,
                                        fill: {
                                            type: 'gradient',
                                            gradient: {
                                                shade: 'dark',
                                                type: 'horizontal',
                                                shadeIntensity: chartDark ? 0.75 : 0.55,
                                                opacityFrom: 1,
                                                opacityTo: chartDark ? 0.82 : 0.88,
                                                stops: [0, 90, 100],
                                                inverseColors: false,
                                                gradientToColors: colorsTo,
                                            },
                                        },
                                        states: {
                                            hover: { filter: { type: 'lighten', value: 0.1 } },
                                        },
                                        dataLabels: {
                                            enabled: true,
                                            style: { colors: chartDark ? ['#f8fafc'] : ['#0f172a'], fontSize: '11px', fontWeight: 700 },
                                            formatter: (val) => val,
                                        },
                                        series: [{ name: 'Casos', data }],
                                        xaxis: {
                                            categories: cats,
                                            labels: { style: { colors: lbl, maxWidth: 180 } },
                                        },
                                        yaxis: {
                                            labels: { style: { colors: lbl }, maxWidth: 160 },
                                        },
                                        tooltip: {
                                            theme: chartDark ? 'dark' : 'light',
                                            y: { formatter: (val) => val + ' caso(s)' },
                                        },
                                    });
                                    this.chart.render();
                                },
                             }">
                            <div x-ref="target"></div>
                        </div>
                    @endif
                </x-dashboard.card>

                <x-dashboard.card title="Casos por ciudad" subtitle="Distribución proporcional · anillos con degradado">
                    @if (count($byCity) === 0)
                        <p class="text-sm text-slate-500 dark:text-dash-muted">Aún no hay datos para graficar.</p>
                    @else
                        @php
                            $cityLabels = collect($byCity)->pluck('city')->all();
                            $cityValues = collect($byCity)->pluck('total')->all();
                            $cn = count($cityValues);
                            $donutTop = ['#22d3ee', '#e879f9', '#fb923c', '#a78bfa', '#34d399', '#f472b6', '#38bdf8', '#fcd34d'];
                            $donutBot = ['#0e7490', '#86198f', '#c2410c', '#5b21b6', '#14532d', '#9f1239', '#0369a1', '#b45309'];
                            $donutFrom = [];
                            $donutTo = [];
                            for ($i = 0; $i < $cn; $i++) {
                                $donutFrom[] = $donutTop[$i % count($donutTop)];
                                $donutTo[] = $donutBot[$i % count($donutBot)];
                            }
                        @endphp
                        <div wire:ignore
                             x-data="{
                                chart: null,
                                init() {
                                    const chartDark = @json($chartDark);
                                    const labels = @js($cityLabels);
                                    const series = @js($cityValues);
                                    const colorsFrom = @js($donutFrom);
                                    const colorsTo = @js($donutTo);
                                    const fg = chartDark ? '#94a3b8' : '#64748b';
                                    const legendLbl = chartDark ? '#cbd5e1' : '#475569';
                                    const donutLblName = chartDark ? '#cbd5e1' : '#475569';
                                    const donutLblVal = chartDark ? '#f8fafc' : '#0f172a';
                                    const donutLblTot = chartDark ? '#8b93b3' : '#64748b';
                                    const strokeCols = chartDark ? ['rgba(7,8,20,0.95)'] : ['#ffffff'];
                                    this.chart = new ApexCharts(this.$refs.target, {
                                        chart: {
                                            type: 'donut',
                                            height: 320,
                                            fontFamily: 'Figtree, ui-sans-serif, system-ui',
                                            foreColor: fg,
                                            background: 'transparent',
                                            dropShadow: chartDark ? {
                                                enabled: true,
                                                top: 8,
                                                blur: 14,
                                                opacity: 0.28,
                                                color: '#a78bfa',
                                            } : { enabled: false },
                                        },
                                        theme: { mode: chartDark ? 'dark' : 'light' },
                                        labels,
                                        series,
                                        colors: colorsFrom,
                                        fill: {
                                            type: 'gradient',
                                            gradient: {
                                                shade: 'dark',
                                                type: 'horizontal',
                                                shadeIntensity: chartDark ? 0.7 : 0.55,
                                                opacityFrom: 1,
                                                opacityTo: chartDark ? 0.9 : 0.92,
                                                gradientToColors: colorsTo,
                                            },
                                        },
                                        plotOptions: {
                                            pie: {
                                                donut: {
                                                    size: '68%',
                                                    labels: {
                                                        show: true,
                                                        name: { color: donutLblName },
                                                        value: { fontWeight: 700, color: donutLblVal },
                                                        total: {
                                                            show: true,
                                                            label: 'Total',
                                                            color: donutLblTot,
                                                            formatter: function (w) {
                                                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                                            },
                                                        },
                                                    },
                                                },
                                            },
                                        },
                                        stroke: { width: chartDark ? 3 : 2, colors: strokeCols },
                                        legend: {
                                            position: 'bottom',
                                            labels: { colors: legendLbl },
                                            markers: { strokeWidth: 0 },
                                        },
                                        tooltip: {
                                            theme: chartDark ? 'dark' : 'light',
                                            y: { formatter: (val) => val + ' caso(s)' },
                                        },
                                    });
                                    this.chart.render();
                                },
                             }">
                            <div x-ref="target"></div>
                        </div>
                    @endif
                </x-dashboard.card>
            </div>

            <x-dashboard.card title="Carga por abogado" :subtitle="count($lawyerWorkload).' usuarios con casos asignados'">
                <div class="overflow-x-auto -mx-2">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-[11px] uppercase tracking-wider border-b border-slate-200 text-slate-500 dark:border-white/10 dark:text-dash-muted">
                                <th class="py-3 px-4 text-left font-semibold text-slate-700 dark:text-slate-100">Abogado</th>
                                <th class="py-3 px-4 text-right font-semibold text-slate-700 dark:text-slate-100">Total</th>
                                <th class="py-3 px-4 text-right font-semibold text-orange-700 dark:text-orange-300/90">Pend.</th>
                                <th class="py-3 px-4 text-right font-semibold text-cyan-700 dark:text-cyan-300/90">Proc.</th>
                                <th class="py-3 px-4 text-right font-semibold text-emerald-700 dark:text-emerald-300/90">Fin.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 dark:divide-white/[0.06] dark:text-slate-200">
                            @forelse ($lawyerWorkload as $row)
                                <tr class="hover:bg-slate-50 transition dark:hover:bg-white/[0.03]">
                                    <td class="py-3 px-4 font-medium text-slate-900 dark:text-white">{{ $row['lawyer_name'] }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="tabular-nums font-semibold text-indigo-700 dark:text-fuchsia-300">{{ $row['total'] }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-right tabular-nums text-orange-700 dark:text-orange-200/95">{{ $row['pendientes'] }}</td>
                                    <td class="py-3 px-4 text-right tabular-nums text-cyan-700 dark:text-cyan-200/95">{{ $row['en_proceso'] }}</td>
                                    <td class="py-3 px-4 text-right tabular-nums text-emerald-700 dark:text-emerald-200/95">{{ $row['finalizados'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-500 dark:text-dash-muted">
                                        Sin asignaciones registradas todavía.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-dashboard.card>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js"></script>
    @endpush
</div>
