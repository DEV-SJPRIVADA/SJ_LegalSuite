@php
    $btn = $variant === 'dark'
        ? 'h-9 rounded-lg px-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white sm:px-3'
        : 'h-9 rounded-lg px-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 sm:px-3';
@endphp
<button wire:click="logout"
        type="button"
        class="inline-flex items-center gap-1.5 {{ $btn }}"
        title="Cerrar sesión"
        aria-label="Cerrar sesión">
    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
    </svg>
    <span class="hidden sm:inline">Salir</span>
</button>
