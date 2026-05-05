<div class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-100/90 p-0.5 shadow-sm dark:border-white/15 dark:bg-white/[0.08]" role="group" aria-label="Tema de interfaz">
    <button type="button"
            wire:click="setTheme('light')"
            title="Tema claro"
            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition sm:px-3
                   {{ $current === 'light'
                      ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-indigo-200 dark:bg-white dark:text-dash-void dark:ring-white/40'
                      : 'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white' }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
        <span class="hidden sm:inline">Claro</span>
    </button>
    <button type="button"
            wire:click="setTheme('dark')"
            title="Tema oscuro"
            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition sm:px-3
                   {{ $current === 'dark'
                      ? 'bg-gradient-to-r from-cyan-600 to-fuchsia-600 text-white shadow-md shadow-fuchsia-500/25 ring-1 ring-white/25'
                      : 'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white' }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
        </svg>
        <span class="hidden sm:inline">Oscuro</span>
    </button>
</div>
