@php
    $btn = $variant === 'dark'
        ? 'rounded-lg border border-white/15 bg-white/5 px-3 py-1.5 text-sm font-medium text-slate-100 hover:bg-white/10 hover:border-cyan-400/35 transition'
        : 'rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200 transition';
@endphp
<button wire:click="logout"
        type="button"
        class="inline-flex items-center gap-2 {{ $btn }}">
    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
    </svg>
    Salir
</button>
