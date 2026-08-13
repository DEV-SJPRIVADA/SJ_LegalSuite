@push('module-nav')
    <x-settings.nav active="questions" />
@endpush

<div class="mx-auto flex min-h-[calc(100dvh-3.25rem)] w-full max-w-6xl flex-col px-4 py-6 sm:px-6 lg:px-8">
  <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ajustes · Acta FO-GJ-04</p>
          <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Preguntas de diligencia</h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
            Catálogo de preguntas que el abogado puede seleccionar al diligenciar el acta de descargos.
            Eliminar una pregunta no altera actas ya guardadas o generadas.
          </p>
      </div>
      <button type="button" wire:click="openCreateModal"
          class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
          + Nueva pregunta
      </button>
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
          <th class="w-16 px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Orden</th>
          <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Pregunta</th>
          <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-200">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-white/5">
        @forelse ($questions as $question)
          <tr wire:key="diligence-q-{{ $question->id }}">
            <td class="px-4 py-3 tabular-nums text-slate-500 dark:text-slate-400">{{ $question->sort_order }}</td>
            <td class="px-4 py-3 text-slate-900 dark:text-white">{{ $question->text }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap items-center justify-end gap-1.5">
                <button type="button" wire:click="moveUp({{ $question->id }})"
                    class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:text-slate-200 dark:ring-white/20"
                    title="Subir">↑</button>
                <button type="button" wire:click="moveDown({{ $question->id }})"
                    class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:text-slate-200 dark:ring-white/20"
                    title="Bajar">↓</button>
                <button type="button" wire:click="openEditModal({{ $question->id }})"
                    class="rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-700">
                  Editar
                </button>
                <button type="button" wire:click="deleteQuestion({{ $question->id }})"
                    wire:confirm="¿Eliminar esta pregunta del catálogo? Las actas ya generadas no se modifican."
                    class="rounded-md px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-300 hover:bg-red-50 dark:text-red-300 dark:ring-red-500/40">
                  Eliminar
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="3" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
              Aún no hay preguntas. Cree la primera para que los abogados puedan seleccionarlas en el FO-GJ-04.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if ($showFormModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" wire:keydown.escape.window="closeFormModal">
      <div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-xl dark:bg-dash-ink">
        <div class="mb-4 flex items-start justify-between gap-3">
          <h2 class="text-lg font-bold text-slate-900 dark:text-white">
            {{ $editingId ? 'Editar pregunta' : 'Nueva pregunta' }}
          </h2>
          <button type="button" wire:click="closeFormModal" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">✕</button>
        </div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Texto de la pregunta</label>
        <textarea wire:model="questionText" rows="4"
            class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"
            placeholder="Ej. Reconoce los hechos descritos en la citación"></textarea>
        @error('questionText')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        <div class="mt-4 flex justify-end gap-2">
          <button type="button" wire:click="closeFormModal" class="rounded-md px-4 py-2 text-sm font-semibold ring-1 ring-slate-300">Cancelar</button>
          <button type="button" wire:click="saveQuestion" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Guardar</button>
        </div>
      </div>
    </div>
  @endif
</div>
