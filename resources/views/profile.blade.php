<x-app-layout>
    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Cuenta</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Mi perfil</h1>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow-sm rounded-lg ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-sm rounded-lg ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card">
                <div class="max-w-xl">
                    <livewire:profile.update-signature-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-sm rounded-lg ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-sm rounded-lg ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
