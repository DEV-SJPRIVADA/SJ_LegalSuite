<div class="inline-flex h-9 items-center rounded-lg bg-slate-100/80 p-0.5 dark:bg-white/[0.06]" role="group" aria-label="Tema de interfaz">
    <button type="button"
            wire:click="setTheme('light')"
            title="Tema claro"
            aria-pressed="{{ $current === 'light' ? 'true' : 'false' }}"
            class="inline-flex h-8 items-center gap-1.5 rounded-md px-2 text-xs font-semibold transition sm:px-2.5
                   {{ $current === 'light'
                      ? 'bg-white text-slate-900 shadow-sm dark:bg-white dark:text-dash-void'
                      : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
        <span class="hidden md:inline">Claro</span>
    </button>
    <button type="button"
            wire:click="setTheme('dark')"
            title="Tema oscuro"
            aria-pressed="{{ $current === 'dark' ? 'true' : 'false' }}"
            class="inline-flex h-8 items-center gap-1.5 rounded-md px-2 text-xs font-semibold transition sm:px-2.5
                   {{ $current === 'dark'
                      ? 'bg-slate-800 text-white shadow-sm dark:bg-white/15 dark:text-white'
                      : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
        </svg>
        <span class="hidden md:inline">Oscuro</span>
    </button>
</div>
