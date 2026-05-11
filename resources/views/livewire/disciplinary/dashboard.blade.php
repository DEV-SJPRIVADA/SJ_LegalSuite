@php
    $chartDark = ($uiTheme ?? 'light') === 'dark';
@endphp
<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="py-4 sm:py-6">
        <div class="mx-auto w-full max-w-[1600px] px-4 sm:px-6 lg:px-8 xl:max-w-[min(100%,1920px)] 2xl:px-10">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 sm:mb-6">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-fuchsia-400/90">
                    Disciplinarios · Dashboard
                </p>

                <x-dashboard.button href="{{ route('disciplinary.cases.index') }}" variant="ghost" class="shrink-0">
                    Ver listado de casos →
                </x-dashboard.button>
            </div>

            @if ((int) $workflowDonuts['total'] === 0)
                <x-dashboard.card class="mb-8">
                    <p class="text-sm text-slate-500 dark:text-dash-muted">Aún no hay casos que coincidan con su alcance.</p>
                </x-dashboard.card>
            @else
                    @php
                        $wTotal = (int) $workflowDonuts['total'];
                        $totalNeon = ['from' => '#fcd34d', 'to' => '#b45309', 'shadow' => '#fcd34d'];
                        $stagePalette = [
                            'A' => ['from' => '#818cf8', 'to' => '#4338ca', 'shadow' => '#818cf8', 'letter' => 'text-indigo-400'],
                            'B' => ['from' => '#fb923c', 'to' => '#9a3412', 'shadow' => '#fb923c', 'letter' => 'text-orange-400'],
                            'C' => ['from' => '#22d3ee', 'to' => '#155e75', 'shadow' => '#22d3ee', 'letter' => 'text-cyan-400'],
                            'D' => ['from' => '#e879f9', 'to' => '#86198f', 'shadow' => '#e879f9', 'letter' => 'text-fuchsia-400'],
                            'E' => ['from' => '#f472b6', 'to' => '#9f1239', 'shadow' => '#f472b6', 'letter' => 'text-pink-400'],
                            'F' => ['from' => '#34d399', 'to' => '#166534', 'shadow' => '#34d399', 'letter' => 'text-emerald-400'],
                        ];
                    @endphp
                <div
                    class="mb-6 overflow-visible rounded-2xl border border-slate-200 bg-white px-2 pt-1.5 pb-0 shadow-sm ring-1 ring-slate-200 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-dash-card dark:backdrop-blur-sm dark:ring-0 sm:px-3 sm:pt-2 sm:pb-0"
                    aria-label="Distribución de casos por etapa del flujo"
                >
                    <div class="grid w-full min-w-0 grid-cols-2 items-start gap-3 overflow-visible sm:grid-cols-3 sm:gap-4 md:grid-cols-4 lg:grid-cols-7 lg:gap-2 xl:gap-3">
                        <div class="flex min-w-0 w-full flex-col">
                            <p class="mb-1 shrink-0 px-0.5 text-center leading-snug" title="Total de casos en su alcance">
                                <span class="block text-[10px] font-bold uppercase tracking-wide text-amber-600 dark:text-amber-300 sm:text-[11px]">Total</span>
                                <span class="mt-0.5 block text-[9px] font-medium text-slate-500 dark:text-dash-muted sm:text-[10px]">Alcance</span>
                            </p>
                            <div wire:ignore class="w-full min-w-0 overflow-visible [&_.apexcharts-canvas]:!overflow-visible [&_.apexcharts-inner]:!overflow-visible [&_.apexcharts-svg]:!overflow-visible [&_svg]:!overflow-visible">
                                <div
                                    x-data="{
                                    chart: null,
                                    init() {
                                        const chartDark = @json($chartDark);
                                        const lg = typeof window !== 'undefined' && window.matchMedia('(min-width: 1024px)').matches;
                                        const chartH = lg ? 208 : 176;
                                        const fg = chartDark ? '#94a3b8' : '#64748b';
                                        const donutLblVal = chartDark ? '#f8fafc' : '#0f172a';
                                        const donutLblTot = chartDark ? '#cbd5e1' : '#64748b';
                                        const wTotalVal = @js($wTotal);
                                        const hair = chartDark ? 'rgba(15,23,42,0.28)' : 'rgba(255,255,255,0.55)';
                                        const strokeCols = [hair, hair];
                                        const tFrom = @js($totalNeon['from']);
                                        const tTo = @js($totalNeon['to']);
                                        const tShadow = @js($totalNeon['shadow']);
                                        this.chart = new ApexCharts(this.$refs.el, {
                                            chart: {
                                                type: 'donut',
                                                height: chartH,
                                                width: '100%',
                                                offsetY: 0,
                                                fontFamily: 'Figtree, ui-sans-serif, system-ui',
                                                foreColor: fg,
                                                background: 'transparent',
                                                dropShadow: chartDark ? {
                                                    enabled: true,
                                                    top: 3,
                                                    blur: 10,
                                                    opacity: 0.32,
                                                    color: tShadow,
                                                } : { enabled: false },
                                            },
                                            theme: { mode: chartDark ? 'dark' : 'light' },
                                            labels: ['Casos', ''],
                                            series: [@js($wTotal), 0],
                                            colors: [tFrom, tFrom],
                                            fill: {
                                                type: 'gradient',
                                                gradient: {
                                                    shade: 'dark',
                                                    type: 'horizontal',
                                                    shadeIntensity: chartDark ? 0.72 : 0.55,
                                                    opacityFrom: 1,
                                                    opacityTo: chartDark ? 0.92 : 0.92,
                                                    gradientToColors: [tTo, tTo],
                                                },
                                            },
                                            plotOptions: {
                                                pie: {
                                                    offsetY: -4,
                                                    customScale: 0.97,
                                                    expandOnClick: false,
                                                    donut: {
                                                        size: '70%',
                                                        labels: {
                                                            show: true,
                                                            name: {
                                                                show: true,
                                                                color: donutLblTot,
                                                                fontSize: '12px',
                                                                fontWeight: 600,
                                                                offsetY: -4,
                                                            },
                                                            value: {
                                                                show: true,
                                                                color: donutLblVal,
                                                                fontSize: '24px',
                                                                fontWeight: 700,
                                                                offsetY: 6,
                                                            },
                                                            total: {
                                                                show: true,
                                                                showAlways: true,
                                                                label: '100%',
                                                                color: donutLblTot,
                                                                fontSize: '12px',
                                                                fontWeight: 600,
                                                                formatter: function () {
                                                                    return String(wTotalVal);
                                                                },
                                                            },
                                                        },
                                                    },
                                                },
                                            },
                                            stroke: { width: 1, colors: strokeCols },
                                            legend: { show: false },
                                            dataLabels: { enabled: false },
                                            tooltip: {
                                                theme: chartDark ? 'dark' : 'light',
                                                y: { formatter: (val) => val + ' caso(s)' },
                                            },
                                        });
                                        this.chart.render();
                                    },
                                 }"
                                    wire:key="workflow-donut-total"
                                >
                                    <div x-ref="el" class="h-[176px] w-full min-w-0 lg:h-[208px]"></div>
                                </div>
                            </div>
                        </div>

                        @foreach ($workflowDonuts['stages'] as $st)
                            @php
                                $letter = $st['letter'];
                                $pal = $stagePalette[$letter];
                                $from = $pal['from'];
                                $to = $pal['to'];
                                $shadow = $pal['shadow'];
                                $letterClass = $pal['letter'];
                                $active = (int) $st['count'];
                                $rest = (int) $st['rest'];
                                $pctLbl = $st['percent_label'];
                                $restFill = $chartDark ? 'rgba(51,65,85,0.55)' : '#e2e8f0';
                                $restFillTo = $chartDark ? 'rgba(30,41,59,0.85)' : '#cbd5e1';
                            @endphp
                            <div class="flex min-w-0 w-full flex-col" wire:key="workflow-donut-{{ $letter }}">
                                <p class="mb-1 shrink-0 px-0.5 text-center leading-snug" title="{{ $st['title'] }} (etapa {{ $letter }})">
                                    <span class="block text-[10px] font-bold tabular-nums sm:text-[11px] {{ $letterClass }}">{{ $letter }}</span>
                                    <span class="mt-0.5 line-clamp-2 block text-[9px] font-medium text-slate-600 dark:text-slate-400 sm:text-[10px]">{{ $st['title'] }}</span>
                                </p>
                                <div wire:ignore class="w-full min-w-0 overflow-visible [&_.apexcharts-canvas]:!overflow-visible [&_.apexcharts-inner]:!overflow-visible [&_.apexcharts-svg]:!overflow-visible [&_svg]:!overflow-visible">
                                    <div
                                        x-data="{
                                        chart: null,
                                        init() {
                                            const chartDark = @json($chartDark);
                                            const lg = typeof window !== 'undefined' && window.matchMedia('(min-width: 1024px)').matches;
                                            const chartH = lg ? 208 : 176;
                                            const fg = chartDark ? '#94a3b8' : '#64748b';
                                            const donutLblTot = chartDark ? '#cbd5e1' : '#64748b';
                                            const donutLblVal = chartDark ? '#f8fafc' : '#0f172a';
                                            const hair = chartDark ? 'rgba(15,23,42,0.28)' : 'rgba(255,255,255,0.55)';
                                            const strokeCols = [hair, hair];
                                            const rest = @js($rest);
                                            const active = @js($active);
                                            const pct = @js($pctLbl.'%');
                                            const cFrom = @js($from);
                                            const cTo = @js($to);
                                            const cShadow = @js($shadow);
                                            const restFill = @js($restFill);
                                            const restFillTo = @js($restFillTo);
                                            this.chart = new ApexCharts(this.$refs.el, {
                                                chart: {
                                                    type: 'donut',
                                                    height: chartH,
                                                    width: '100%',
                                                    offsetY: 0,
                                                    fontFamily: 'Figtree, ui-sans-serif, system-ui',
                                                    foreColor: fg,
                                                    background: 'transparent',
                                                    dropShadow: chartDark ? {
                                                        enabled: true,
                                                        top: 3,
                                                        blur: 10,
                                                        opacity: 0.32,
                                                        color: cShadow,
                                                    } : { enabled: false },
                                                },
                                                theme: { mode: chartDark ? 'dark' : 'light' },
                                                labels: ['En etapa', 'Resto'],
                                                series: [active, rest],
                                                colors: [cFrom, restFill],
                                                fill: {
                                                    type: 'gradient',
                                                    gradient: {
                                                        shade: 'dark',
                                                        type: 'horizontal',
                                                        shadeIntensity: chartDark ? 0.72 : 0.55,
                                                        opacityFrom: 1,
                                                        opacityTo: chartDark ? 0.92 : 0.92,
                                                        gradientToColors: [cTo, restFillTo],
                                                    },
                                                },
                                                plotOptions: {
                                                    pie: {
                                                        offsetY: -4,
                                                        customScale: 0.97,
                                                        expandOnClick: false,
                                                        donut: {
                                                            size: '70%',
                                                            labels: {
                                                                show: true,
                                                                name: {
                                                                    show: true,
                                                                    color: donutLblTot,
                                                                    fontSize: '12px',
                                                                    fontWeight: 600,
                                                                    offsetY: -4,
                                                                },
                                                                value: {
                                                                    show: true,
                                                                    color: donutLblVal,
                                                                    fontSize: '24px',
                                                                    fontWeight: 700,
                                                                    offsetY: 6,
                                                                },
                                                                total: {
                                                                    show: true,
                                                                    showAlways: true,
                                                                    label: pct,
                                                                    color: donutLblTot,
                                                                    fontSize: '12px',
                                                                    fontWeight: 600,
                                                                    formatter: function () {
                                                                        return String(active);
                                                                    },
                                                                },
                                                            },
                                                        },
                                                    },
                                                },
                                                stroke: { width: 1, colors: strokeCols },
                                                legend: { show: false },
                                                dataLabels: { enabled: false },
                                                tooltip: {
                                                    theme: chartDark ? 'dark' : 'light',
                                                    y: { formatter: (val) => val + ' caso(s)' },
                                                },
                                            });
                                            this.chart.render();
                                        },
                                     }"
                                    >
                                        <div x-ref="el" class="h-[176px] w-full min-w-0 lg:h-[208px]"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

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
