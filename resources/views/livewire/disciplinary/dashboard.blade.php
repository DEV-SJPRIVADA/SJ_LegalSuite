<div>
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard disciplinario</h2>
            <a href="{{ route('disciplinary.cases.index') }}" wire:navigate
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none">
                Ver disciplinarios →
            </a>
        </div>
    </header>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- KPIs --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-disciplinary.kpi-card label="Total disciplinarios" :value="$kpis['total']" color="slate" />
                <x-disciplinary.kpi-card label="Pendientes" :value="$kpis['pendientes']" color="amber" />
                <x-disciplinary.kpi-card label="En proceso" :value="$kpis['en_proceso']" color="blue" />
                <x-disciplinary.kpi-card label="Finalizados" :value="$kpis['finalizados']" color="emerald" />
            </div>

            {{-- Gráficas --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- Por tipo de falta --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Casos por tipo de falta</h3>
                    @if (collect($byFault)->sum('total') === 0)
                        <p class="text-sm text-gray-500">Aún no hay datos para graficar.</p>
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

                {{-- Por ciudad --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Casos por ciudad</h3>
                    @if (count($byCity) === 0)
                        <p class="text-sm text-gray-500">Aún no hay datos para graficar.</p>
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

            {{-- Tabla carga por abogado --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Carga por abogado</h3>
                    <span class="text-xs text-gray-500">{{ count($lawyerWorkload) }} usuarios</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr class="text-xs uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3 text-left font-semibold">Abogado</th>
                                <th class="px-6 py-3 text-right font-semibold">Total</th>
                                <th class="px-6 py-3 text-right font-semibold">Pendientes</th>
                                <th class="px-6 py-3 text-right font-semibold">En proceso</th>
                                <th class="px-6 py-3 text-right font-semibold">Finalizados</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            @forelse ($lawyerWorkload as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ $row['lawyer_name'] }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <span class="font-semibold">{{ $row['total'] }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-right text-amber-600">{{ $row['pendientes'] }}</td>
                                    <td class="px-6 py-3 text-right text-blue-600">{{ $row['en_proceso'] }}</td>
                                    <td class="px-6 py-3 text-right text-emerald-600">{{ $row['finalizados'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-6 text-center text-gray-500">
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
