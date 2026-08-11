@push('module-nav')
    <x-settings.nav active="articles" />
@endpush

<div class="mx-auto flex min-h-[calc(100dvh-3.25rem)] w-full max-w-6xl flex-col px-4 py-6 sm:px-6 lg:px-8">
  <div class="mb-6">
      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ajustes · Citación FO-GJ-03</p>
      <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Artículos y numerales</h1>
      <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
        Configure por falta los artículos del Reglamento Interno de Trabajo y, opcionalmente, los numerales sugeridos.
        Si deja los numerales vacíos, el abogado los digitará al diligenciar la citación.
      </p>
  </div>

  @if (session('success'))
    <div class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/30 dark:text-emerald-100">
      {{ session('success') }}
    </div>
  @endif

  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/[0.04]">
    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-white/10">
      <thead class="bg-slate-50 dark:bg-white/[0.03]">
        <tr>
          <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Código</th>
          <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Falta</th>
          <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Plantilla</th>
          <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-200">Acción</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-white/5">
        @foreach ($faults as $fault)
          @php
            $templateArticles = $fault->citationTemplate?->articles ?? collect();
            $articleCount = $templateArticles->count();
            $numeralCount = $templateArticles->sum(fn ($link) => $link->numerals->count());
          @endphp
          <tr wire:key="fault-template-{{ $fault->id }}">
            <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $fault->code }}</td>
            <td class="px-4 py-3 text-slate-900 dark:text-white">{{ $fault->name }}</td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
              @if ($articleCount === 0)
                <span class="text-slate-400 dark:text-slate-500">Sin configurar</span>
              @else
                {{ $articleCount }} artículo{{ $articleCount === 1 ? '' : 's' }}
                @if ($numeralCount > 0)
                  · {{ $numeralCount }} numeral{{ $numeralCount === 1 ? '' : 'es' }}
                @else
                  · numerales libres
                @endif
              @endif
            </td>
            <td class="px-4 py-3 text-right">
              <button type="button" wire:click="openManageModal({{ $fault->id }})"
                class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                Gestionar
              </button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if ($showManageModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" wire:keydown.escape.window="closeManageModal">
      <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl dark:bg-dash-ink">
        @php $editingFault = $faults->firstWhere('id', $editingFaultId); @endphp
        <div class="mb-4 flex items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Plantilla de citación</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $editingFault?->name }}</p>
          </div>
          <button type="button" wire:click="closeManageModal" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">✕</button>
        </div>

        <div class="space-y-3">
          @foreach ($editingBlocks as $index => $row)
            <div wire:key="edit-article-{{ $index }}" class="grid gap-2 rounded-lg border border-slate-200 p-3 dark:border-white/10 sm:grid-cols-12">
              <div class="sm:col-span-3">
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Artículo</label>
                <input type="text" wire:model="editingBlocks.{{ $index }}.article_number" placeholder="74"
                  class="mt-1 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
              </div>
              <div class="sm:col-span-8">
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Numerales (opcional)</label>
                <input type="text" wire:model="editingBlocks.{{ $index }}.numerals" placeholder="1, 3, 6.1"
                  class="mt-1 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
              </div>
              <div class="flex items-end justify-end sm:col-span-1">
                <button type="button" wire:click="removeEditingArticleRow({{ $index }})"
                  class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950/40">
                  Quitar
                </button>
              </div>
            </div>
          @endforeach

          <button type="button" wire:click="addEditingArticleRow"
            class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-300">
            + Agregar artículo
          </button>
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-2">
          <button type="button" wire:click="closeManageModal"
            class="rounded-md px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">
            Cancelar
          </button>
          <button type="button" wire:click="saveFaultTemplate"
            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            Guardar plantilla
          </button>
        </div>
        </div>
    </div>
  @endif
</div>
