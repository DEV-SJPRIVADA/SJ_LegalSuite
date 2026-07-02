{{-- Requiere ancestro con x-data="sjAgendaAttachmentLightbox()" --}}
<div x-show="open"
    x-cloak
    x-transition
    class="fixed inset-0 z-[90] flex items-center justify-center bg-black/80 p-4 backdrop-blur-[1px]"
    role="dialog"
    aria-modal="true"
    :aria-label="alt || 'Adjunto ampliado'"
    x-on:click.self="closeLightbox()"
    x-on:keydown.escape.window="closeLightbox()">
    <div class="relative flex max-h-[92vh] max-w-[96vw] flex-col items-center" x-on:click.stop>
        <button type="button"
            class="absolute -top-10 right-0 rounded-md bg-white/10 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20 hover:bg-white/20"
            x-on:click="closeLightbox()">
            Cerrar (Esc)
        </button>
        <div class="overflow-hidden rounded-lg ring-1 ring-white/15" x-on:wheel="wheelZoom($event)">
            <img x-bind:src="src"
                x-bind:alt="alt"
                x-bind:style="'transform: scale(' + scale + '); transform-origin: center center;'"
                class="max-h-[85vh] max-w-[92vw] select-none object-contain"
                draggable="false">
        </div>
        <p class="mt-2 max-w-lg truncate text-center text-xs text-white/80" x-text="alt"></p>
    </div>
</div>

<div x-show="contextOpen"
    x-cloak
    class="fixed z-[95] min-w-[12rem] rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15"
    :style="'left:' + contextX + 'px;top:' + contextY + 'px'"
    x-on:click.outside="closeImageContextMenu()">
    <button type="button"
        class="block w-full px-4 py-2 text-left text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-white/10"
        x-on:click="downloadFromContextMenu()">
        Descargar
    </button>
</div>
