<x-app-layout>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Disciplinarios · FO-GJ-51</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Informe disciplinario</h1>
            <p class="mt-2 text-sm text-slate-600 max-w-3xl dark:text-slate-300">
                Diligencie el informe y genere el PDF en tamaño carta para archivo o impresión.
                Volver al <a href="{{ route('disciplinary.formats.index') }}" class="font-semibold text-indigo-700 underline decoration-dotted underline-offset-2 hover:text-indigo-900 dark:text-cyan-400 dark:hover:text-cyan-300">catálogo de formatos</a>.
            </p>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="overflow-x-auto rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card sm:p-6">
                <form method="post" action="{{ route('disciplinary.forms.informe.pdf') }}" class="space-y-6">
                    @csrf
                    <x-disciplinary.forms.fo-gj-51-preview />
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-dash-lift dark:ring-1 dark:ring-white/15 dark:hover:bg-dash-lift/90">
                            Generar PDF (carta)
                        </button>
                        <span class="text-xs text-slate-500 dark:text-dash-muted">El PDF se descarga con los datos ingresados.</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
