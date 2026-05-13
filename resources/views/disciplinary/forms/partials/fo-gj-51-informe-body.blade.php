@php
    use App\Models\ColombianMunicipality;

    $municipalitiesGrouped = $municipalitiesGrouped ?? ColombianMunicipality::groupedByDepartmentForSelect();
    /** @var string|null $prefillWorkerName */
    /** @var string|null $prefillWorkerDocument */
    $pdfUploadErrors = $errors->has('informe_file')
        || $errors->has('informe_worker_name')
        || $errors->has('informe_worker_document');
    /** @var bool $openPdfUploadModal */
    $openPdfUploadModal = $openPdfUploadModal ?? false;
    $pdfModalOpenInitially = $pdfUploadErrors || $openPdfUploadModal;
    /** @var string $pdfIframeName */
    $pdfIframeName = $pdfIframeName ?? 'fo51_pdf_iframe';
    /** Dentro del modal FO: sin segunda tarjeta ring/sombra sobre el mismo marco exterior */
    $embedInModal = ($embedInModal ?? false) === true;
    $evidenceModalOpenInitially = collect($errors->keys())->contains(fn (string $k): bool => str_starts_with($k, 'evidence_images'));
@endphp

@if ($municipalitiesGrouped->isEmpty())
    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
        <p class="font-semibold">No hay municipios en el catálogo (DIVIPOLA).</p>
        <p class="mt-1 text-amber-900/90 dark:text-amber-50/90">
            Sin datos importados, el desplegable <strong>CIUDAD</strong> no mostrará opciones. Quien tenga permiso de ajustes debe importar el archivo oficial en
            @can('settings.manage-territory')
                <a href="{{ route('settings.territory-import') }}" class="font-semibold underline underline-offset-2 hover:text-amber-950 dark:hover:text-white">Ajustes → Territorio</a>.
            @else
                <span class="font-semibold">Ajustes → Territorio</span> (pida a un administrador que cargue el listado DIVIPOLA).
            @endcan
        </p>
    </div>
@endif

<div
    class="relative"
    x-data="Object.assign({}, window.evidenceTilesState(), { pdfModalOpen: @js($pdfModalOpenInitially), evidenceModalOpen: @js($evidenceModalOpenInitially) })"
    @keydown.escape.window="pdfModalOpen = false; evidenceModalOpen = false">

    {{-- Oculto: recibe el POST del PDF para que la página lista no navegue fuera del modal --}}
    <iframe name="{{ $pdfIframeName }}" title="Ventana interna PDF" class="fixed -left-[9999px] h-px w-px opacity-0 pointer-events-none" aria-hidden="true"></iframe>

    <div @class([
        'overflow-x-auto',
        'rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card sm:p-6' => ! $embedInModal,
        'rounded-none bg-transparent p-0 shadow-none ring-0 dark:bg-transparent dark:shadow-none' => $embedInModal,
    ])>
        <form method="post" action="{{ route('disciplinary.forms.informe.process') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <x-disciplinary.forms.fo-gj-51-preview
                :workerName="old('fo51_worker_name', $prefillWorkerName ?? '')"
                :workerDocument="old('fo51_worker_document', $prefillWorkerDocument ?? '')"
                :municipalitiesGrouped="$municipalitiesGrouped"
            />
            @error('fo51_worker_name')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('fo51_worker_document')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <button type="submit" name="fo51_action" value="pdf" formtarget="{{ $pdfIframeName }}"
                    class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-dash-lift dark:ring-1 dark:ring-white/15 dark:hover:bg-dash-lift/90">
                    Generar PDF (carta)
                </button>
                <button type="submit" name="fo51_action" value="enviar"
                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                    Enviar a revisión (dirección)
                </button>
                @unless($embedInModal)
                    <button type="button" @click.prevent="pdfModalOpen = true"
                        class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-white/15 dark:bg-white/10 dark:text-white dark:hover:bg-white/15">
                        Cargar informe en PDF
                    </button>
                @endunless
                @unless(auth()->user()->isDisciplinaryFieldOperator())
                    <a href="{{ route('disciplinary.formats.index') }}" class="inline-flex items-center text-xs font-semibold text-indigo-700 underline decoration-dotted underline-offset-2 hover:text-indigo-900 sm:self-center dark:text-cyan-400 dark:hover:text-cyan-300">
                        Catálogo de formatos
                    </a>
                @endunless
                <span class="text-xs text-slate-500 dark:text-dash-muted sm:max-w-xl sm:basis-full">
                    Si el trabajador no existía en el catálogo, se crea un registro mínimo con nombre y cédula declarados para futuros procesos.
                </span>
            </div>

            <div class="rounded-lg ring-1 ring-slate-200 bg-slate-50/80 px-4 py-3 dark:bg-white/[0.05] dark:ring-white/10">
                <label class="flex flex-wrap items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" x-model="evidenceModalOpen"
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-white/25 dark:bg-transparent">
                    <span class="text-sm font-semibold text-slate-800 dark:text-white">Cargar evidencia</span>
                    <span class="text-xs text-slate-600 dark:text-slate-400">Hasta 10 imágenes opcionales con el envío a revisión.</span>
                </label>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 pl-8">Desmarque la casilla para omitir evidencias; puede abrir de nuevo para cambiar los archivos antes de enviar.</p>
                <div class="mt-3 space-y-1 border-t border-slate-200/80 pt-3 dark:border-white/10">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Imágenes seleccionadas
                    </p>
                    <div class="flex gap-2 overflow-x-auto rounded-lg border border-slate-200/80 bg-white/80 p-2 dark:border-white/10 dark:bg-dash-ink/50">
                        <template x-for="(url, idx) in urls" :key="idx">
                            <div x-show="url" class="relative h-12 w-12 shrink-0 overflow-hidden rounded-md bg-slate-200/70 dark:bg-black/40">
                                <img :src="url" alt="" class="h-full w-full object-cover">
                            </div>
                        </template>
                        <p x-show="! urls.filter(Boolean).length" class="text-[11px] text-slate-500 dark:text-slate-500">
                            Aún no hay imágenes seleccionadas.
                        </p>
                    </div>
                </div>
            </div>

            @error('evidence_images')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            {{-- Modal evidencias (mismo formulario que «Enviar a revisión») --}}
            <div x-show="evidenceModalOpen"
                x-transition.opacity.duration.200ms
                x-cloak
                class="fixed inset-0 z-[72] flex items-center justify-center bg-black/50 p-4 dark:bg-black/60"
                style="display: none;"
                role="dialog"
                aria-modal="true"
                aria-labelledby="evidence-modal-title"
                x-on:click.self="evidenceModalOpen = false">
                <div x-show="evidenceModalOpen"
                    x-transition
                    @click.stop
                    class="flex max-h-[min(92vh,780px)] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
                    <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200/80 px-5 py-4 dark:border-white/10">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-300/90">Cargar evidencia</p>
                            <h2 id="evidence-modal-title" class="mt-1 text-lg font-bold text-slate-900 dark:text-white">Evidencia fotográfica</h2>
                            <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">
                                Seleccione la imagen o tome la foto. Hasta 10 archivos (JPEG, PNG, WebP, GIF). No son obligatorias.
                            </p>
                        </div>
                        <button type="button" @click="evidenceModalOpen = false"
                            class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-white/10 dark:hover:text-white"
                            aria-label="Cerrar">
                            ✕
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                            @for ($i = 0; $i < 10; $i++)
                                <div class="relative aspect-[5/4] overflow-hidden rounded-xl border border-emerald-400/25 bg-gradient-to-br from-emerald-900/25 via-slate-900/20 to-slate-900/50 shadow-inner ring-1 ring-emerald-500/10 dark:from-emerald-950/50 dark:via-dash-void/80 dark:to-slate-950/90 dark:ring-emerald-400/20">
                                    <input type="file"
                                        id="evidence_in_{{ $i }}"
                                        name="evidence_images[]"
                                        accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp"
                                        class="sr-only"
                                        x-on:change="setPreview({{ $i }}, $event)" />
                                    <label for="evidence_in_{{ $i }}"
                                        class="absolute inset-0 flex cursor-pointer flex-col items-center justify-center gap-1 p-2 text-center">
                                        <span class="sr-only">Seleccionar imagen {{ $i + 1 }}</span>
                                        <img x-show="urls[{{ $i }}]"
                                            x-bind:src="urls[{{ $i }}]"
                                            alt=""
                                            class="absolute inset-0 h-full w-full object-cover" />
                                        <span x-show="! urls[{{ $i }}]"
                                            class="select-none text-3xl font-extralight leading-none text-white/90 drop-shadow-sm dark:text-emerald-100/90">+</span>
                                    </label>
                                    <button type="button"
                                        x-show="urls[{{ $i }}]"
                                        x-on:click.prevent.stop="clear({{ $i }})"
                                        class="absolute right-1 top-1 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-rose-600 text-xs font-bold text-white shadow-md ring-1 ring-white/20 hover:bg-rose-700"
                                        title="Quitar imagen">
                                        ×
                                    </button>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div class="flex shrink-0 justify-end border-t border-slate-200/80 px-5 py-4 dark:border-white/10">
                        <button type="button" @click="evidenceModalOpen = false"
                            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                            Listo
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Modal: cargar PDF externo (z alto si está dentro del modal principal) --}}
    <div x-show="pdfModalOpen"
        x-transition.opacity.duration.200ms
        x-cloak
        class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4 dark:bg-black/60"
        style="display: none;"
        role="dialog"
        aria-modal="true"
        aria-labelledby="pdf-modal-title"
        x-on:click.self="pdfModalOpen = false">
        <div x-show="pdfModalOpen"
            x-transition
            @click.stop
            class="max-h-[min(92vh,640px)] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-6 shadow-xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="pdf-modal-title" class="text-lg font-bold text-slate-900 dark:text-white">Informe en PDF (externo)</h2>
                    <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">
                        Adjunte el PDF y registre nombre y cédula del trabajador; el archivo no se lee automáticamente.
                    </p>
                </div>
                <button type="button" @click="pdfModalOpen = false"
                    class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-white/10 dark:hover:text-white"
                    aria-label="Cerrar">
                    ✕
                </button>
            </div>

            <form method="post" action="{{ route('disciplinary.forms.informe.process') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="fo51_action" value="cargar">

                <div>
                    <label for="modal_informe_worker_name" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-dash-muted">
                        Nombre completo del trabajador informado</label>
                    <input id="modal_informe_worker_name" name="informe_worker_name" type="text" maxlength="500" required value="{{ old('informe_worker_name', $prefillWorkerName ?? '') }}"
                        autocomplete="off"
                        class="mt-2 block w-full rounded-md border-slate-300 bg-white text-sm shadow-sm dark:border-white/15 dark:bg-dash-lift dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Ej. Juan Carlos Pérez Martínez" />
                    @error('informe_worker_name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="modal_informe_worker_document" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-dash-muted">
                        Número de documento (cédula)</label>
                    <input id="modal_informe_worker_document" name="informe_worker_document" type="text" maxlength="32" required value="{{ old('informe_worker_document', $prefillWorkerDocument ?? '') }}"
                        autocomplete="off"
                        class="mt-2 block w-full rounded-md border-slate-300 bg-white text-sm font-mono shadow-sm dark:border-white/15 dark:bg-dash-lift dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Ej. 1234567890" />
                    @error('informe_worker_document')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="modal_informe_file" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-dash-muted">
                        Archivo PDF</label>
                    <input id="modal_informe_file" type="file" name="informe_file" accept="application/pdf,.pdf" required
                        class="mt-2 block w-full text-sm text-slate-600 file:mr-4 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-slate-800 dark:text-slate-300 dark:file:bg-dash-lift dark:file:ring-1 dark:file:ring-white/15 dark:hover:file:bg-dash-lift/90" />
                    @error('informe_file')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 pt-2">
                    <button type="button" @click="pdfModalOpen = false"
                        class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                        Cargar PDF y enviar a revisión
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
