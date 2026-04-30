<div>
    {{-- Sub-nav del módulo --}}
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    {{-- Encabezado de la página --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Disciplinarios · Dashboard</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Indicadores del módulo</h1>
            </div>
            <a href="{{ route('disciplinary.cases.index') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Ver listado de casos →
            </a>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- KPIs --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-disciplinary.kpi-card label="Total disciplinarios" :value="$kpis['total']" color="slate" />
                <x-disciplinary.kpi-card label="Pendientes" :value="$kpis['pendientes']" color="amber" />
                <x-disciplinary.kpi-card label="En proceso" :value="$kpis['en_proceso']" color="blue" />
                <x-disciplinary.kpi-card label="Finalizados" :value="$kpis['finalizados']" color="emerald" />
            </div>

            {{-- Gráficas --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Casos por tipo de falta</h3>
                    @if (collect($byFault)->sum('total') === 0)
                        <p class="text-sm text-slate-500">Aún no hay datos para graficar.</p>
                    @else
                        <div wire:ignore
                            x-data="{
                                chart: null,
                                init() {
                                    this.chart = new ApexCharts(this.$refs.target, {
                                        chart: { type: 'bar', height: 320, toolbar: { show: false } },
                                        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                                        dataLabels: { enabled: true },
                                        colors: ['#6366f1'],
                                        series: [{ name: 'Casos', data: @js(collect($byFault)->pluck('total')->all()) }],
                                        xaxis: { categories: @js(collect($byFault)->pluck('name')->all()) },
                                    });
                                    this.chart.render();
                                }
                            }">
                            <div x-ref="target"></div>
                        </div>
                    @endif
                </div>

                <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Casos por ciudad</h3>
                    @if (count($byCity) === 0)
                        <p class="text-sm text-slate-500">Aún no hay datos para graficar.</p>
                    @else
                        <div wire:ignore
                            x-data="{
                                chart: null,
                                init() {
                                    this.chart = new ApexCharts(this.$refs.target, {
                                        chart: { type: 'donut', height: 320 },
                                        labels: @js(collect($byCity)->pluck('city')->all()),
                                        series: @js(collect($byCity)->pluck('total')->all()),
                                        legend: { position: 'bottom' },
                                    });
                                    this.chart.render();
                                }
                            }">
                            <div x-ref="target"></div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Carga por abogado --}}
            <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">Carga por abogado</h3>
                    <span class="text-xs text-slate-500">{{ count($lawyerWorkload) }} usuarios</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-xs uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-3 text-left font-semibold">Abogado</th>
                                <th class="px-6 py-3 text-right font-semibold">Total</th>
                                <th class="px-6 py-3 text-right font-semibold">Pendientes</th>
                                <th class="px-6 py-3 text-right font-semibold">En proceso</th>
                                <th class="px-6 py-3 text-right font-semibold">Finalizados</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200 text-sm">
                            @forelse ($lawyerWorkload as $row)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3 font-medium text-slate-900">{{ $row['lawyer_name'] }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <span class="font-semibold">{{ $row['total'] }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-right text-amber-600">{{ $row['pendientes'] }}</td>
                                    <td class="px-6 py-3 text-right text-blue-600">{{ $row['en_proceso'] }}</td>
                                    <td class="px-6 py-3 text-right text-emerald-600">{{ $row['finalizados'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-6 text-center text-slate-500">
                                        Sin abogados registrados todavía.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js"></script>
    @endpush
</div>
