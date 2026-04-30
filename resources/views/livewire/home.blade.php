<div>
    {{-- Encabezado --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Inicio</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">
                        Hola, {{ explode(' ', auth()->user()->name)[0] }}
                    </h1>
                    <p class="text-sm text-slate-600 mt-1">
                        Resumen general del sistema y alertas que requieren tu atención.
                    </p>
                </div>
                <div class="text-xs text-slate-500">
                    {{ now()->locale('es')->translatedFormat('l, d \\d\\e F \\d\\e Y') }}
                </div>
            </div>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Tarjetas: alertas --}}
            <section>
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <x-app-sidebar-icon name="bell" class="h-4 w-4 text-amber-500" />
                    Alertas
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-home.alert-card
                        :count="$summary['vencidos']['count']"
                        title="Plazos vencidos"
                        subtitle="Etapas con deadline pasado"
                        icon="clock"
                        color="rose"
                        :items="$summary['vencidos']['items']" />
                    <x-home.alert-card
                        :count="$summary['proximos']['count']"
                        title="Próximos a vencer"
                        subtitle="Plazo en 3 días o menos"
                        icon="flag"
                        color="amber"
                        :items="$summary['proximos']['items']" />
                    <x-home.alert-card
                        :count="$summary['sin_asignar']['count']"
                        title="Sin abogado"
                        subtitle="Casos sin asignar"
                        icon="inbox"
                        color="indigo"
                        :items="$summary['sin_asignar']['items']" />
                    <x-home.alert-card
                        :count="$summary['pendientes_decision']['count']"
                        title="Pend. decisión"
                        subtitle="Esperando resolución"
                        icon="scale"
                        color="sky"
                        :items="$summary['pendientes_decision']['items']" />
                </div>
            </section>

            {{-- Gráfica + Acceso rápido a módulos --}}
            <section class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                {{-- Gráfica de tendencia: ocupa 2/3 en lg y 3/4 en xl --}}
                <div class="lg:col-span-2 xl:col-span-3 bg-white rounded-lg shadow-sm ring-1 ring-slate-200 p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <x-app-sidebar-icon name="chart-bar" class="h-4 w-4 text-indigo-500" />
                        Tendencia mensual de casos abiertos
                    </h3>

                    @if (collect($trend)->sum('total') === 0)
                        <p class="text-sm text-slate-500 py-12 text-center">Aún no hay datos suficientes para graficar.</p>
                    @else
                        <div wire:ignore
                             x-data="{
                                chart: null,
                                init() {
                                    this.chart = new ApexCharts(this.$refs.target, {
                                        chart: { type: 'area', height: 320, toolbar: { show: false }, sparkline: { enabled: false } },
                                        stroke: { curve: 'smooth', width: 2 },
                                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
                                        dataLabels: { enabled: false },
                                        colors: ['#6366f1'],
                                        series: [{ name: 'Casos abiertos', data: @js(collect($trend)->pluck('total')->all()) }],
                                        xaxis: { categories: @js(collect($trend)->pluck('month')->all()) },
                                        grid: { borderColor: '#e2e8f0' },
                                    });
                                    this.chart.render();
                                }
                             }">
                            <div x-ref="target"></div>
                        </div>
                    @endif
                </div>

                {{-- Acceso rápido --}}
                <div class="bg-white rounded-lg shadow-sm ring-1 ring-slate-200 p-5 flex flex-col">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Acceso rápido</h3>

                    <div class="space-y-2 flex-1">
                        @can('viewDashboard', \App\Models\Disciplinary\DisciplinaryCase::class)
                            <a href="{{ route('disciplinary.dashboard') }}" wire:navigate
                               class="block rounded-lg ring-1 ring-slate-200 p-3 hover:ring-indigo-300 hover:bg-indigo-50/50 transition group">
                                <div class="flex items-start gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center flex-shrink-0">
                                        <x-app-sidebar-icon name="chart-bar" class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900 group-hover:text-indigo-700">Dashboard</p>
                                        <p class="text-xs text-slate-500">KPIs y gráficas disciplinarias</p>
                                    </div>
                                </div>
                            </a>
                            <a href="{{ route('disciplinary.cases.index') }}" wire:navigate
                               class="block rounded-lg ring-1 ring-slate-200 p-3 hover:ring-indigo-300 hover:bg-indigo-50/50 transition group">
                                <div class="flex items-start gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                                        <x-app-sidebar-icon name="scale" class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900 group-hover:text-indigo-700">Disciplinarios</p>
                                        <p class="text-xs text-slate-500">Listado y gestión de casos</p>
                                    </div>
                                </div>
                            </a>
                        @endcan
                    </div>

                    <p class="text-xs text-slate-500 mt-4 leading-relaxed border-t border-slate-100 pt-3">
                        Más módulos disponibles próximamente.
                    </p>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js"></script>
    @endpush
</div>
