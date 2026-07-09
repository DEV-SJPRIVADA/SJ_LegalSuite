@php
    use App\Models\Disciplinary\DisciplinaryCase;

    $chartDark = ($uiTheme ?? 'light') === 'dark';
    $firstName = explode(' ', auth()->user()->name)[0];
    $kpis = $kpis ?? [];
    $workflow = $workflow ?? ['total' => 0, 'stages' => []];
    $lawyerWorkload = $lawyerWorkload ?? [];
    $caseMapPins = $caseMapPins ?? [];
    $topMunicipalities = $topMunicipalities ?? [];
    $casesWithoutMunicipalityCount = (int) ($casesWithoutMunicipalityCount ?? 0);
    $criticalAlerts = (int) ($dashboard['criticalAlertCount'] ?? 0);
    $totalAlerts = (int) ($dashboard['totalAlerts'] ?? 0);

    $alertBuckets = [
        ['key' => 'vencidos', 'title' => 'Plazos vencidos', 'subtitle' => 'Deadline pasado', 'icon' => 'clock', 'color' => 'rose'],
        ['key' => 'proximos', 'title' => 'Próximos a vencer', 'subtitle' => '≤ 3 días', 'icon' => 'flag', 'color' => 'amber'],
        ['key' => 'sin_asignar', 'title' => 'Sin abogado', 'subtitle' => 'Sin titular', 'icon' => 'inbox', 'color' => 'indigo'],
        ['key' => 'pendientes_decision', 'title' => 'Pend. decisión', 'subtitle' => 'En resolución', 'icon' => 'scale', 'color' => 'sky'],
    ];

    $moduleRoadmap = [
        'Licitaciones', 'Tutelas', 'Demandas', 'Neg. colectiva', 'Investigaciones',
        'Cartera', 'Req. legales', 'Contratos', 'Pólizas', 'Auditoría',
    ];

    $chartConfig = [
        'chartDark' => $chartDark,
        'trend' => $trend,
        'workflow' => $workflow,
        'lawyerWorkload' => $lawyerWorkload,
        'summary' => $summary,
    ];
@endphp

<div class="home-command-center mx-auto flex h-[calc(100dvh-3.25rem)] max-h-[calc(100dvh-3.25rem)] w-full max-w-[1600px] flex-col overflow-hidden px-3 py-2 sm:px-5 sm:py-3 lg:px-6"
    x-data="homeCommandCenter(@js($chartConfig))"
    x-init="init()"
    @destroy.window="destroy()">

    {{-- Cabecera compacta --}}
    <header class="mb-2 flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-white/10 pb-2 dark:border-white/10">
        <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-fuchsia-400/90">Dirección jurídica · Command center</p>
            <div class="mt-0.5 flex flex-wrap items-baseline gap-x-3 gap-y-0.5">
                <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl">
                    Hola, {{ $firstName }}
                </h1>
                <span class="text-xs text-slate-500 dark:text-dash-muted">
                    <span class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">{{ number_format((int) ($kpis['total'] ?? 0)) }}</span> casos
                    · <span class="font-semibold tabular-nums text-amber-600 dark:text-amber-300">{{ number_format((int) ($kpis['en_proceso'] ?? 0)) }}</span> en proceso
                    @if ($totalAlerts > 0)
                        · <span class="font-semibold tabular-nums text-rose-600 dark:text-rose-300">{{ $totalAlerts }}</span> alertas
                    @endif
                </span>
            </div>
        </div>
        <p class="text-right text-[11px] text-slate-500 dark:text-dash-muted">
            {{ now()->locale('es')->translatedFormat('l, d \\d\\e M \\d\\e Y') }}
        </p>
    </header>

    {{-- KPI alertas (chips) --}}
    <div class="mb-2 grid shrink-0 grid-cols-2 gap-2 lg:grid-cols-4">
        @foreach ($alertBuckets as $bucket)
            <x-home.kpi-chip
                :count="$summary[$bucket['key']]['count'] ?? 0"
                :title="$bucket['title']"
                :subtitle="$bucket['subtitle']"
                :icon="$bucket['icon']"
                :color="$bucket['color']"
                x-bind:class="activeBucket === @js($bucket['key']) ? 'ring-2' : ''"
                x-on:click="openBucket(@js($bucket['key']))" />
        @endforeach
    </div>

    {{-- Cuerpo principal --}}
    <div class="flex min-h-0 flex-1 flex-col gap-2">
        <div class="grid min-h-0 flex-1 grid-cols-1 gap-2 lg:grid-cols-12 lg:gap-3">

            {{-- Columna izquierda: etapas + tendencia --}}
            <div class="flex min-h-0 flex-col gap-2 lg:col-span-4">
                <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm ring-1 ring-slate-200 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-dash-card dark:ring-white/5 backdrop-blur-sm">
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <h2 class="text-[10px] font-bold uppercase tracking-[0.14em] text-dash-muted">Casos por etapa del flujo</h2>
                        <span class="rounded-md bg-white/5 px-2 py-0.5 text-[10px] font-bold tabular-nums text-cyan-300 ring-1 ring-cyan-400/20">
                            {{ number_format((int) ($workflow['total'] ?? 0)) }} total
                        </span>
                    </div>
                    @if ((int) ($workflow['total'] ?? 0) === 0)
                        <p class="flex flex-1 items-center justify-center text-xs text-slate-500">Sin casos en el alcance.</p>
                    @else
                        <div wire:ignore class="min-h-0 flex-1">
                            <div x-ref="stagesChart" data-chart-key="stages" data-apex-chart-root class="h-[130px] w-full min-w-0"></div>
                        </div>
                    @endif
                </section>

                <section class="shrink-0 rounded-xl border border-slate-200 bg-white p-3 shadow-sm ring-1 ring-slate-200 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-dash-card dark:ring-white/5 backdrop-blur-sm">
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <h2 class="text-[10px] font-bold uppercase tracking-[0.14em] text-dash-muted">Tendencia · aperturas (6 meses)</h2>
                        <span class="text-[10px] font-semibold tabular-nums text-fuchsia-300/90">
                            {{ number_format(collect($trend)->sum('total')) }} en periodo
                        </span>
                    </div>
                    @if (collect($trend)->sum('total') === 0)
                        <p class="py-6 text-center text-xs text-slate-500">Sin aperturas en el periodo.</p>
                    @else
                        <div wire:ignore>
                            <div x-ref="trendChart" data-chart-key="trend" data-apex-chart-root class="h-[118px] w-full min-w-0"></div>
                        </div>
                    @endif
                </section>
            </div>

            {{-- Columna central: mapa por ciudad --}}
            <section class="flex min-h-0 flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm ring-1 ring-slate-200 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-dash-card dark:ring-white/5 backdrop-blur-sm lg:col-span-5">
                <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-[10px] font-bold uppercase tracking-[0.14em] text-dash-muted">Casos por ciudad</h2>
                        <p class="mt-0.5 text-[10px] text-slate-500 dark:text-dash-muted">
                            Pins por municipio (DIVIPOLA) · zoom ≥8 muestra límites municipales
                        </p>
                    </div>
                    <span class="rounded-md bg-white/5 px-2 py-0.5 text-[10px] font-bold tabular-nums text-cyan-300 ring-1 ring-cyan-400/20">
                        {{ number_format(count($caseMapPins)) }} municipio(s)
                    </span>
                </div>

                <div class="flex min-h-0 flex-1 gap-2">
                    <div wire:ignore class="relative min-h-[188px] min-w-0 flex-1">
                        <div x-ref="homeMap"
                             class="h-full min-h-[188px] w-full rounded-xl border border-slate-200/80 bg-slate-950/40 ring-1 ring-cyan-500/15 dark:border-white/10 dark:bg-black/30 dark:ring-fuchsia-500/20 z-0"
                             data-pins='@json($caseMapPins)'
                             data-chart-dark="{{ $chartDark ? '1' : '0' }}"
                             data-compact="1"
                             data-geo-dept="{{ route('disciplinary.map-geo', ['file' => 'gadm41_COL_1.json'], absolute: false) }}"
                             data-geo-mun="{{ route('disciplinary.map-geo', ['file' => 'gadm41_COL_2.json'], absolute: false) }}"
                             role="presentation"
                             aria-label="Mapa de casos por municipio en Colombia">
                        </div>
                        @if (count($caseMapPins) === 0)
                            <div class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-xl bg-slate-950/50 px-4 text-center backdrop-blur-[1px] dark:bg-black/45">
                                <p class="max-w-xs text-[11px] leading-relaxed text-slate-200">
                                    Sin casos georreferenciados en su alcance.
                                    @can('viewAny', \App\Models\User::class)
                                        <a href="{{ route('settings.territory-import') }}" wire:navigate class="pointer-events-auto font-semibold text-cyan-300 underline decoration-cyan-400/40 underline-offset-2 hover:text-cyan-200">Ver DIVIPOLA</a>
                                    @endcan
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="flex w-[7.5rem] shrink-0 flex-col sm:w-36">
                        <p class="mb-1 text-[9px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-dash-muted">Top municipios</p>
                        @if ($topMunicipalities === [])
                            <p class="flex flex-1 items-center text-[10px] leading-snug text-slate-500">Sin datos con coordenadas.</p>
                        @else
                            <ul class="min-h-0 flex-1 space-y-0.5 overflow-y-auto pr-0.5">
                                @foreach ($topMunicipalities as $index => $mun)
                                    <li>
                                        <button type="button"
                                            x-on:click="focusMunicipality(@js($mun['code']))"
                                            x-bind:class="highlightedMunicipality === @js($mun['code']) ? 'border-cyan-400/50 bg-cyan-500/10 text-cyan-200' : 'border-transparent text-slate-400 hover:border-white/10 hover:bg-white/5 hover:text-slate-200'"
                                            class="flex w-full items-start gap-1.5 rounded-md border px-1.5 py-1 text-left transition">
                                            <span class="mt-0.5 w-3 shrink-0 text-[9px] font-bold tabular-nums text-fuchsia-400/90">{{ $index + 1 }}</span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[10px] font-medium leading-tight">{{ $mun['label'] }}</span>
                                                <span class="text-[9px] tabular-nums text-slate-500">{{ number_format($mun['count']) }} caso(s)</span>
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                <div class="mt-2 grid grid-cols-3 gap-2 border-t border-white/10 pt-2">
                    <div class="rounded-lg bg-white/[0.03] px-2 py-1.5 text-center ring-1 ring-white/5">
                        <p class="text-[9px] font-bold uppercase tracking-wide text-dash-muted">Pendientes</p>
                        <p class="text-lg font-bold tabular-nums text-amber-300">{{ number_format((int) ($kpis['pendientes'] ?? 0)) }}</p>
                    </div>
                    <div class="rounded-lg bg-white/[0.03] px-2 py-1.5 text-center ring-1 ring-white/5">
                        <p class="text-[9px] font-bold uppercase tracking-wide text-dash-muted">En proceso</p>
                        <p class="text-lg font-bold tabular-nums text-cyan-300">{{ number_format((int) ($kpis['en_proceso'] ?? 0)) }}</p>
                    </div>
                    <div class="rounded-lg bg-white/[0.03] px-2 py-1.5 text-center ring-1 ring-white/5">
                        <p class="text-[9px] font-bold uppercase tracking-wide text-dash-muted">Finalizados</p>
                        <p class="text-lg font-bold tabular-nums text-emerald-300">{{ number_format((int) ($kpis['finalizados'] ?? 0)) }}</p>
                    </div>
                </div>

                @if ($casesWithoutMunicipalityCount > 0)
                    <p class="mt-1.5 text-[10px] text-slate-500 dark:text-dash-muted">
                        {{ number_format($casesWithoutMunicipalityCount) }} caso(s) sin municipio DIVIPOLA asignado.
                    </p>
                @endif
            </section>

        {{-- Columna derecha: acciones + roadmap --}}
        <aside class="flex min-h-0 flex-col gap-2 lg:col-span-3">
            <section class="shrink-0 rounded-xl border border-slate-200 bg-white p-3 shadow-sm ring-1 ring-slate-200 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-dash-card dark:ring-white/5 backdrop-blur-sm">
                <h2 class="mb-2 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-dash-muted">Acceso rápido</h2>
                <div class="grid grid-cols-1 gap-2">
                    @can('viewDashboard', DisciplinaryCase::class)
                        <a href="{{ route('disciplinary.dashboard') }}" wire:navigate
                            class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-sm font-semibold text-slate-800 transition hover:border-cyan-300 hover:bg-cyan-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:border-cyan-400/40 dark:hover:bg-cyan-500/10">
                            <x-app-sidebar-icon name="chart-bar" class="h-4 w-4 text-cyan-400" />
                            Dashboard disciplinario
                        </a>
                    @endcan
                    @can('viewAny', DisciplinaryCase::class)
                        <a href="{{ route('disciplinary.cases.index') }}" wire:navigate
                            class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-sm font-semibold text-slate-800 transition hover:border-fuchsia-300 hover:bg-fuchsia-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:border-fuchsia-400/40 dark:hover:bg-fuchsia-500/10">
                            <x-app-sidebar-icon name="scale" class="h-4 w-4 text-fuchsia-400" />
                            Casos disciplinarios
                        </a>
                    @endcan
                    @can('viewAny', \App\Models\User::class)
                        <a href="{{ route('users.index') }}" wire:navigate
                            class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-sm font-semibold text-slate-800 transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:border-indigo-400/40 dark:hover:bg-indigo-500/10">
                            <x-app-sidebar-icon name="user-cog" class="h-4 w-4 text-indigo-400" />
                            Usuarios
                        </a>
                    @endcan
                </div>
            </section>

            {{-- Panel alertas (scroll interno único) --}}
            <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm ring-1 ring-slate-200 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-dash-card dark:ring-white/5 backdrop-blur-sm">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h2 class="text-[10px] font-bold uppercase tracking-[0.14em] text-dash-muted">Detalle de alertas</h2>
                    <span class="text-[10px] text-slate-500" x-show="!activeBucket">Seleccione un indicador</span>
                    <button type="button" x-show="activeBucket" x-cloak @click="activeBucket = null"
                        class="text-[10px] font-semibold text-cyan-400 hover:text-cyan-300">Cerrar</button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto pr-1 text-xs" x-show="!activeBucket">
                    <p class="text-slate-500 leading-relaxed">
                        Pulse un indicador superior para ver expedientes vinculados.
                        @if ($criticalAlerts > 0)
                            <span class="mt-1 block font-semibold text-rose-400">{{ $criticalAlerts }} situación(es) crítica(s).</span>
                        @endif
                    </p>
                </div>
                <template x-for="bucket in @js(array_column($alertBuckets, 'key'))" :key="bucket">
                    <ul class="min-h-0 flex-1 space-y-1 overflow-y-auto pr-1" x-show="activeBucket === bucket" x-cloak>
                        <template x-if="bucketItems(bucket).length === 0">
                            <li class="py-6 text-center text-slate-500">Sin registros en esta categoría.</li>
                        </template>
                        <template x-for="(item, idx) in bucketItems(bucket)" :key="idx">
                            <li>
                                <a :href="item.route ?? '#'" wire:navigate
                                    class="block truncate rounded-md px-2 py-1.5 text-slate-300 ring-1 ring-transparent transition hover:bg-white/5 hover:text-cyan-200 hover:ring-white/10">
                                    <span x-show="item.due_at" class="mr-1 font-mono text-[10px] text-fuchsia-400/90" x-text="item.due_at"></span>
                                    <span x-text="item.label"></span>
                                </a>
                            </li>
                        </template>
                        <li x-show="bucketCount(bucket) > bucketItems(bucket).length" class="pt-1 text-[10px] text-slate-500">
                            y más expedientes…
                        </li>
                    </ul>
                </template>
            </section>

            <section class="shrink-0 rounded-xl border border-dashed border-slate-200 bg-slate-50/50 px-3 py-2 dark:border-white/10 dark:bg-white/[0.02]">
                <p class="mb-1.5 text-[9px] font-bold uppercase tracking-[0.14em] text-slate-500">Módulos en roadmap</p>
                <div class="flex flex-wrap gap-1">
                    @foreach ($moduleRoadmap as $label)
                        <span class="rounded-md bg-white/5 px-1.5 py-0.5 text-[9px] font-medium text-slate-500 ring-1 ring-white/10">{{ $label }}</span>
                    @endforeach
                </div>
            </section>
        </aside>
        </div>

        @if ($lawyerWorkload !== [])
            <section class="shrink-0 rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm ring-1 ring-slate-200 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-dash-card dark:ring-white/5 backdrop-blur-sm">
                <h2 class="mb-1 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-dash-muted">Carga por abogado titular</h2>
                <div wire:ignore>
                    <div x-ref="lawyersChart" data-chart-key="lawyers" data-apex-chart-root class="w-full min-w-0"></div>
                </div>
            </section>
        @endif
    </div>
</div>
