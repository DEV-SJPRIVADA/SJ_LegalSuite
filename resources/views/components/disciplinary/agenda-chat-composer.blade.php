@props([
    'bodyModel' => 'agendaLawyerBody',
    'uploadsProperty' => 'agendaLawyerUploads',
    'sendAction' => 'postAgendaLawyer',
    'removeUploadMethod' => 'removeAgendaLawyerUploadAt',
    'uploads' => [],
    'placeholder' => 'Escriba un mensaje…',
    'inputId' => 'agenda-composer-body',
    'errorField' => 'agendaLawyerBody',
])

@php
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
@endphp

<div {{ $attributes->merge(['class' => 'border-t border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-white/10 dark:bg-indigo-950/40']) }}
    x-data="window.sjDisciplinaryAgendaComposer({ uploadsProperty: @js($uploadsProperty) })"
    x-on:dragover.prevent="dragOver = true"
    x-on:dragleave.prevent="dragOver = false"
    x-on:drop.prevent="dropFiles($event)"
    :class="dragOver ? 'ring-2 ring-inset ring-indigo-400/60 dark:ring-indigo-400/40' : ''">

    @if (is_array($uploads) && count(array_filter($uploads)) > 0)
        <div class="mb-2 flex flex-wrap gap-2">
            @foreach ($uploads as $index => $file)
                @if ($file instanceof TemporaryUploadedFile)
                    @php
                        $previewUrl = $file->temporaryUrl();
                        $previewName = $file->getClientOriginalName();
                        $isImage = str_starts_with((string) $file->getMimeType(), 'image/');
                        $previewKind = $isImage ? 'image' : 'pdf';
                    @endphp
                    <div class="relative shrink-0" wire:key="agenda-upload-preview-{{ $index }}-{{ $file->getFilename() }}">
                        <button type="button"
                            title="{{ $previewName }} — clic para ampliar"
                            class="group block overflow-hidden rounded-md border border-slate-200 ring-indigo-500/0 transition hover:ring-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-white/15"
                            x-on:click="$dispatch('open-agenda-lightbox', { src: @js($previewUrl), alt: @js($previewName), kind: @js($previewKind), downloadUrl: @js($previewUrl) })">
                            @if ($isImage)
                                <img src="{{ $previewUrl }}" alt=""
                                    class="pointer-events-none h-14 w-14 object-cover transition group-hover:brightness-95 dark:group-hover:brightness-110">
                            @else
                                <span class="flex h-14 w-14 flex-col items-center justify-center bg-white px-1 dark:bg-dash-lift">
                                    <svg class="h-6 w-6 text-red-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2 5 5h-4V4zM8 12h8v2H8v-2zm0 4h5v2H8v-2z"/>
                                    </svg>
                                    <span class="mt-0.5 max-w-full truncate text-[9px] font-semibold text-slate-600 dark:text-slate-300">PDF</span>
                                </span>
                            @endif
                        </button>
                        <button type="button"
                            wire:click="{{ $removeUploadMethod }}({{ $index }})"
                            x-on:click.stop
                            class="absolute -right-1.5 -top-1.5 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-slate-800 text-[10px] font-bold text-white hover:bg-slate-900 dark:bg-slate-200 dark:text-slate-900"
                            title="Quitar adjunto"
                            aria-label="Quitar adjunto">
                            ×
                        </button>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <div class="flex items-end gap-2 rounded-lg border border-slate-200/90 bg-white/95 py-1.5 pl-1.5 pr-1 dark:border-white/15 dark:bg-dash-lift/90">
        <button type="button"
            x-on:click="openPicker()"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-indigo-600 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/60"
            title="Adjuntar imagen o PDF"
            aria-label="Adjuntar archivo">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.5L8.25 18.75l-3.75-3.75" />
            </svg>
        </button>
        <input type="file"
            x-ref="agendaFiles"
            multiple
            accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
            class="sr-only"
            x-on:change="pickFiles($event)">

        <label for="{{ $inputId }}" class="sr-only">{{ $placeholder }}</label>
        <textarea id="{{ $inputId }}"
            wire:model="{{ $bodyModel }}"
            rows="1"
            placeholder="{{ $placeholder }}"
            x-on:paste="pasteFiles($event)"
            class="min-h-[2.25rem] max-h-28 min-w-0 flex-1 resize-none border-0 bg-transparent py-1.5 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0 dark:text-white dark:placeholder:text-slate-500"></textarea>

        <button type="button"
            wire:click="{{ $sendAction }}"
            wire:loading.attr="disabled"
            wire:target="{{ $sendAction }}"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
            title="Enviar mensaje"
            aria-label="Enviar mensaje">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3.478 2.405a.75.75 0 0 0-.926.94l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.405Z" />
            </svg>
        </button>
    </div>

    @error($errorField)
        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
    @error($uploadsProperty)
        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
    @error($uploadsProperty . '.*')
        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
