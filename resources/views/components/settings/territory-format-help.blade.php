<details class="group rounded-lg border border-slate-200 bg-slate-50/50 dark:border-white/10 dark:bg-white/[0.02]">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2 text-xs font-semibold text-slate-700 marker:content-none dark:text-slate-200">
        <span class="inline-flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5 text-slate-400 transition group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            Formato del archivo DIVIPOLA
        </span>
        <span class="text-[10px] font-normal text-slate-500 dark:text-slate-400">Excel / CSV</span>
    </summary>
    <div class="space-y-3 border-t border-slate-200 px-3 py-3 text-xs text-slate-600 dark:border-white/10 dark:text-slate-400">
        <ul class="list-disc space-y-1 pl-4">
            <li>Excel: hoja <strong class="text-slate-800 dark:text-slate-200">Municipios</strong> (se ignoran otras hojas).</li>
            <li>Datos desde la <strong class="text-slate-800 dark:text-slate-200">fila 3</strong> (filas 1–2 pueden ser título).</li>
            <li>CSV: codificación <strong class="text-slate-800 dark:text-slate-200">UTF-8 sin BOM</strong>, mismo orden de columnas.</li>
            <li>Upsert por código de municipio de <strong class="text-slate-800 dark:text-slate-200">5 dígitos</strong>.</li>
        </ul>
        <div class="overflow-x-auto rounded-lg ring-1 ring-slate-200 dark:ring-white/10">
            <table class="min-w-full text-[11px]">
                <thead class="bg-slate-100 text-left font-bold uppercase tracking-wide text-slate-500 dark:bg-white/5 dark:text-slate-400">
                    <tr>
                        <th class="px-2 py-1.5">Col.</th>
                        <th class="px-2 py-1.5">Campo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                    @foreach ([
                        ['A', 'Código departamento (2 dígitos)'],
                        ['B', 'Nombre departamento'],
                        ['C', 'Código municipio (5 dígitos)'],
                        ['D', 'Nombre municipio'],
                        ['E', 'Tipo'],
                        ['F', 'Longitud'],
                        ['G', 'Latitud'],
                        ['H', 'Nota'],
                    ] as [$col, $label])
                        <tr wire:key="fmt-{{ $col }}">
                            <td class="px-2 py-1.5 font-mono font-semibold text-slate-700 dark:text-slate-300">{{ $col }}</td>
                            <td class="px-2 py-1.5">{{ $label }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</details>
